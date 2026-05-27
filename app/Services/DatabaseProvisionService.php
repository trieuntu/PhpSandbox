<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseProvisionService {
    public function provision(User $user): void {
        if ($user->sandbox_db_name) {
            return; // Already provisioned
        }
        
        $dbName = 'sandbox_user_' . $user->id;
        $dbUser = 'sbx_u' . $user->id;
        $dbPass = Str::random(32);
        
        $adminPdo = $this->getAdminConnection();
        
        // Create database
        $adminPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
        
        // Create user and grant permissions
        $adminPdo->exec("CREATE USER IF NOT EXISTS '{$dbUser}'@'%' IDENTIFIED BY '{$dbPass}'");
        $adminPdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'%'");
        $adminPdo->exec("GRANT SELECT ON `sandbox_shared`.* TO '{$dbUser}'@'%'");
        $adminPdo->exec("FLUSH PRIVILEGES");
        
        // Update user record
        $user->update([
            'sandbox_db_name' => $dbName,
            'sandbox_db_user' => $dbUser,
            'sandbox_db_pass' => encrypt($dbPass),
        ]);
    }
    
    public function deprovision(User $user): void {
        if (!$user->sandbox_db_name) return;
        
        $adminPdo = $this->getAdminConnection();
        $dbName = $user->sandbox_db_name;
        $dbUser = $user->sandbox_db_user;
        
        $adminPdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
        $adminPdo->exec("DROP USER IF EXISTS '{$dbUser}'@'%'");
        $adminPdo->exec("FLUSH PRIVILEGES");
        
        $user->update(['sandbox_db_name' => null, 'sandbox_db_user' => null, 'sandbox_db_pass' => null]);
    }
    
    private function getAdminConnection(): \PDO {
        $host = config('sandbox.db_host', env('DB_HOST', '127.0.0.1'));
        $user = config('sandbox.db_admin_user');
        $pass = config('sandbox.db_admin_pass');
        
        return new \PDO(
            "mysql:host={$host};port=3306;charset=utf8mb4",
            $user,
            $pass,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }
}
