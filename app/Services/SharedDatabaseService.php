<?php
namespace App\Services;

use App\Models\SharedDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class SharedDatabaseService
{
    private function getAdminConnection(string $dbName = ''): \PDO
    {
        $host = config('sandbox.db_host', env('DB_HOST', 'mysql'));
        $user = config('sandbox.db_admin_user');
        $pass = config('sandbox.db_admin_pass');
        $dsn  = $dbName
            ? "mysql:host={$host};port=3306;dbname={$dbName};charset=utf8mb4"
            : "mysql:host={$host};port=3306;charset=utf8mb4";

        return new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]);
    }

    /**
     * Create two shared MySQL users (ro + rw) if they don't already exist.
     */
    public function ensureSharedUsers(): void
    {
        $pdo   = $this->getAdminConnection();
        $roPas = config('sandbox.shared_ro_pass');
        $rwPas = config('sandbox.shared_rw_pass');

        $pdo->exec("CREATE USER IF NOT EXISTS 'sbx_shared_ro'@'%' IDENTIFIED BY '{$roPas}'");
        $pdo->exec("CREATE USER IF NOT EXISTS 'sbx_shared_rw'@'%' IDENTIFIED BY '{$rwPas}'");
        $pdo->exec("FLUSH PRIVILEGES");
    }

    /**
     * Grant read / read-write access on a specific shared DB to both shared users.
     */
    private function grantToSharedUsers(string $slug): void
    {
        $pdo    = $this->getAdminConnection();
        $dbName = 'sandbox_shared_' . $slug;

        $pdo->exec("GRANT SELECT ON `{$dbName}`.* TO 'sbx_shared_ro'@'%'");
        $pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON `{$dbName}`.* TO 'sbx_shared_rw'@'%'");
        $pdo->exec("FLUSH PRIVILEGES");
    }

    /**
     * Create the shared DB, import SQL, store record.
     */
    public function create(
        string $slug,
        string $displayName,
        ?string $description,
        string $permission,
        UploadedFile $sqlFile
    ): SharedDatabase {
        $this->ensureSharedUsers();

        $dbName = 'sandbox_shared_' . $slug;
        $pdo    = $this->getAdminConnection();
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $this->grantToSharedUsers($slug);
        $this->importSQL($slug, $sqlFile->get());

        $tablesInfo = $this->getTablesInfo($slug);

        return SharedDatabase::create([
            'slug'         => $slug,
            'display_name' => $displayName,
            'description'  => $description,
            'permission'   => $permission,
            'tables_info'  => $tablesInfo,
            'created_by'   => Auth::id(),
        ]);
    }

    /**
     * Drop and recreate the DB, then re-import the SQL file.
     */
    public function reimport(SharedDatabase $sharedDb, UploadedFile $sqlFile): void
    {
        $dbName = 'sandbox_shared_' . $sharedDb->slug;
        $pdo    = $this->getAdminConnection();
        $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
        $pdo->exec("CREATE DATABASE `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $this->grantToSharedUsers($sharedDb->slug);
        $this->importSQL($sharedDb->slug, $sqlFile->get());

        $sharedDb->update(['tables_info' => $this->getTablesInfo($sharedDb->slug)]);
    }

    /**
     * Drop the MySQL DB and delete the record.
     */
    public function drop(SharedDatabase $sharedDb): void
    {
        $dbName = 'sandbox_shared_' . $sharedDb->slug;
        $pdo    = $this->getAdminConnection();
        $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
        $sharedDb->delete();
    }

    /**
     * Execute SQL statements into the shared DB (using admin connection).
     */
    private function importSQL(string $slug, string $sqlContent): void
    {
        $this->validateSQL($sqlContent);

        $pdo        = $this->getAdminConnection('sandbox_shared_' . $slug);
        $statements = $this->splitSQL($sqlContent);

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt !== '') {
                $pdo->exec($stmt);
            }
        }
    }

    /**
     * Return [{name, rows, size_kb}] for all tables in the shared DB.
     */
    public function getTablesInfo(string $slug): array
    {
        $dbName = 'sandbox_shared_' . $slug;

        try {
            $pdo = $this->getAdminConnection($dbName);
            $stmt = $pdo->query(
                "SELECT TABLE_NAME AS name,
                        TABLE_ROWS AS rows,
                        ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 1) AS size_kb
                 FROM   information_schema.TABLES
                 WHERE  TABLE_SCHEMA = DATABASE()
                 ORDER  BY TABLE_NAME"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Refresh tables_info from live MySQL.
     */
    public function refreshTablesInfo(SharedDatabase $sharedDb): void
    {
        $sharedDb->update(['tables_info' => $this->getTablesInfo($sharedDb->slug)]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Basic security check on uploaded SQL — reject dangerous DDL.
     *
     * @throws \InvalidArgumentException
     */
    private function validateSQL(string $sql): void
    {
        $lower = strtolower($sql);

        $forbidden = [
            'drop database',
            'create user',
            'drop user',
            'grant ',
            'revoke ',
            'alter user',
            'set global',
            'set @@global',
        ];

        foreach ($forbidden as $pattern) {
            if (str_contains($lower, $pattern)) {
                throw new \InvalidArgumentException(
                    "SQL file contains a forbidden statement: \"" . strtoupper($pattern) . "\". " .
                    "Only DDL (CREATE TABLE, INSERT, etc.) is allowed."
                );
            }
        }
    }

    /**
     * Split an SQL dump into individual statements, handling string literals
     * and comments so we don't break on semicolons inside strings.
     */
    private function splitSQL(string $sql): array
    {
        $statements = [];
        $current    = '';
        $len        = strlen($sql);
        $i          = 0;
        $inString   = false;
        $stringChar = '';
        $inComment  = false;
        $inLineComment = false;

        while ($i < $len) {
            $ch   = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($inLineComment) {
                if ($ch === "\n") $inLineComment = false;
                $i++;
                continue;
            }

            if ($inComment) {
                if ($ch === '*' && $next === '/') {
                    $inComment = false;
                    $i += 2;
                } else {
                    $i++;
                }
                continue;
            }

            if (!$inString) {
                if ($ch === '-' && $next === '-') {
                    $inLineComment = true;
                    $i += 2;
                    continue;
                }
                if ($ch === '/' && $next === '*') {
                    $inComment = true;
                    $i += 2;
                    continue;
                }
                if ($ch === '#') {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
                if ($ch === ';') {
                    $statements[] = $current;
                    $current      = '';
                    $i++;
                    continue;
                }
                if ($ch === "'" || $ch === '"' || $ch === '`') {
                    $inString   = true;
                    $stringChar = $ch;
                }
            } else {
                if ($ch === '\\' && ($stringChar === "'" || $stringChar === '"')) {
                    $current .= $ch . $next;
                    $i += 2;
                    continue;
                }
                if ($ch === $stringChar) {
                    $inString = false;
                }
            }

            $current .= $ch;
            $i++;
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }
}
