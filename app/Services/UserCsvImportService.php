<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserCsvImportService
{
    public function __construct(
        protected DatabaseProvisionService $dbService
    ) {}

    /**
     * Import users from a CSV file.
     *
     * @param string $filePath
     * @param callable|null $onProgress function(string $type, string $message): void
     * @return array{total: int, imported: int, skipped: int, errors: array<string>}
     */
    public function import(string $filePath, ?callable $onProgress = null): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            Log::error("CSV import failed: file not found or unreadable at {$filePath}");
            return [
                'total' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ["Không thể đọc file: {$filePath}"],
            ];
        }

        $rawContent = file_get_contents($filePath);
        if ($rawContent === false || trim($rawContent) === '') {
            return [
                'total' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['File CSV rỗng.'],
            ];
        }

        // Strip UTF-8 BOM if present
        if (str_starts_with($rawContent, "\xEF\xBB\xBF")) {
            $rawContent = substr($rawContent, 3);
        }

        // Detect line endings and split
        $lines = preg_split("/\r\n|\n|\r/", trim($rawContent));
        if (empty($lines)) {
            return [
                'total' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Không tìm thấy dữ liệu trong file CSV.'],
            ];
        }

        // Detect delimiter (, or ; or \t) from the first non-empty line
        $delimiter = $this->detectDelimiter($lines[0]);

        // Parse first row
        $firstRow = str_getcsv($lines[0], $delimiter, '"', '\\');
        $firstRow = array_map(fn($v) => trim((string)$v), $firstRow);

        $hasHeader = $this->isHeaderRow($firstRow);
        $headerMap = $hasHeader ? $this->mapHeaders($firstRow) : null;
        $dataStartIndex = $hasHeader ? 1 : 0;

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $total = count($lines) - $dataStartIndex;

        Log::info("Starting CSV import ({$total} rows, hasHeader=" . ($hasHeader ? 'yes' : 'no') . ", delimiter='{$delimiter}')");

        for ($i = $dataStartIndex; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }

            $rowNum = $i + 1;
            $row = str_getcsv($line, $delimiter, '"', '\\');
            $row = array_map(fn($v) => trim((string)$v), $row);

            // Extract student attributes
            $record = $hasHeader
                ? $this->extractFromHeaderRow($row, $headerMap)
                : $this->extractFromPositionalRow($row);

            if (!$record) {
                $err = "Dòng {$rowNum}: Không thể phân tích dữ liệu dòng.";
                $errors[] = $err;
                $skipped++;
                Log::warning($err . " Nội dung: " . $line);
                if ($onProgress) $onProgress('skipped', $err);
                continue;
            }

            $email = strtolower($record['email'] ?? '');
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $err = "Dòng {$rowNum}: Email không hợp lệ ('" . ($record['email'] ?? '') . "').";
                $errors[] = $err;
                $skipped++;
                Log::warning($err);
                if ($onProgress) $onProgress('skipped', $err);
                continue;
            }

            $studentId = $record['student_id'] ?: null;
            $password = $record['password'] ?: ($studentId ?: 'password123');

            // Name fields
            $namePayload = [];
            if (!empty($record['first_name']) || !empty($record['last_name'])) {
                $namePayload['first_name'] = $record['first_name'] ?? '';
                $namePayload['last_name'] = $record['last_name'] ?? '';
            } elseif (!empty($record['name'])) {
                $namePayload['name'] = $record['name'];
            } else {
                // If neither name nor first/last was provided, use part of email
                $namePayload['name'] = explode('@', $email)[0];
            }

            try {
                $user = User::firstOrCreate(
                    ['email' => $email],
                    $namePayload + [
                        'student_id' => $studentId,
                        'password' => Hash::make($password),
                        'role' => 'student',
                        'is_active' => true,
                    ]
                );

                if ($user->wasRecentlyCreated) {
                    try {
                        $this->dbService->provision($user);
                    } catch (\Throwable $e) {
                        Log::error("Failed to provision sandbox database for user {$user->email} (#{$user->id}): " . $e->getMessage());
                    }
                    $imported++;
                    if ($onProgress) $onProgress('imported', $user->email);
                } else {
                    $skipped++;
                    if ($onProgress) $onProgress('skipped', "{$user->email} (đã tồn tại)");
                }
            } catch (\Throwable $e) {
                $err = "Dòng {$rowNum} ({$email}): Lỗi lưu cơ sở dữ liệu - " . $e->getMessage();
                $errors[] = $err;
                $skipped++;
                Log::error($err);
                if ($onProgress) $onProgress('error', $err);
            }
        }

        Log::info("CSV import completed: {$imported} imported, {$skipped} skipped, " . count($errors) . " errors.");

        return [
            'total' => $total,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Detect if a row is a header row or a data row.
     */
    protected function isHeaderRow(array $row): bool
    {
        // 1. If any column contains an email, it's definitely a DATA row.
        foreach ($row as $cell) {
            if (filter_var($cell, FILTER_VALIDATE_EMAIL) || str_contains($cell, '@')) {
                return false;
            }
        }

        // 2. Check if row contains recognized header names
        $recognizedHeaders = [
            'email', 'mail', 'thudientu',
            'firstname', 'ten', 'givenname',
            'lastname', 'ho', 'hodem', 'hovatendem', 'surname', 'familyname',
            'name', 'hovaten', 'hoten', 'fullname', 'tensinhvien',
            'studentid', 'mssv', 'masinhvien', 'masosinhvien', 'id', 'mathe',
            'password', 'matkhau', 'pass',
        ];

        $matchCount = 0;
        foreach ($row as $cell) {
            $norm = $this->normalizeString($cell);
            if (in_array($norm, $recognizedHeaders, true)) {
                $matchCount++;
            }
        }

        // If at least one known header matched, consider it a header row
        return $matchCount > 0;
    }

    /**
     * Map header cells to recognized fields.
     *
     * @return array<int, string>
     */
    protected function mapHeaders(array $row): array
    {
        $map = [];
        foreach ($row as $idx => $cell) {
            $norm = $this->normalizeString($cell);
            if (in_array($norm, ['email', 'mail', 'thudientu'], true)) {
                $map[$idx] = 'email';
            } elseif (in_array($norm, ['lastname', 'ho', 'hodem', 'hovatendem', 'surname', 'familyname'], true)) {
                $map[$idx] = 'last_name';
            } elseif (in_array($norm, ['firstname', 'ten', 'givenname'], true)) {
                $map[$idx] = 'first_name';
            } elseif (in_array($norm, ['name', 'hovaten', 'hoten', 'fullname', 'tensinhvien'], true)) {
                $map[$idx] = 'name';
            } elseif (in_array($norm, ['studentid', 'mssv', 'masinhvien', 'masosinhvien', 'id', 'mathe'], true)) {
                $map[$idx] = 'student_id';
            } elseif (in_array($norm, ['password', 'matkhau', 'pass'], true)) {
                $map[$idx] = 'password';
            }
        }
        return $map;
    }

    /**
     * Extract fields from a row using header map.
     */
    protected function extractFromHeaderRow(array $row, array $headerMap): ?array
    {
        $data = [
            'last_name' => '',
            'first_name' => '',
            'name' => '',
            'email' => '',
            'student_id' => '',
            'password' => '',
        ];

        foreach ($headerMap as $idx => $field) {
            if (isset($row[$idx])) {
                $data[$field] = trim($row[$idx]);
            }
        }

        return $data;
    }

    /**
     * Extract fields from a positional row without headers.
     * Supports NTU portal export:
     * - 5 columns: [last_name, first_name, email, student_id, password]
     * - 4 columns: [last_name, first_name, email, student_id] OR [name, email, student_id, password]
     * - 3 columns: [name, email, student_id]
     */
    protected function extractFromPositionalRow(array $row): ?array
    {
        $count = count($row);
        if ($count < 2) {
            return null;
        }

        // Find email index
        $emailIdx = -1;
        foreach ($row as $idx => $cell) {
            if (filter_var($cell, FILTER_VALIDATE_EMAIL) || str_contains($cell, '@')) {
                $emailIdx = $idx;
                break;
            }
        }

        $lastName = '';
        $firstName = '';
        $name = '';
        $email = '';
        $studentId = '';
        $password = '';

        if ($emailIdx === 2 && $count >= 4) {
            // [0: last_name, 1: first_name, 2: email, 3: student_id, 4: password (optional)]
            $lastName = $row[0] ?? '';
            $firstName = $row[1] ?? '';
            $email = $row[2] ?? '';
            $studentId = $row[3] ?? '';
            $password = $row[4] ?? '';
        } elseif ($emailIdx === 1) {
            // [0: name, 1: email, 2: student_id (optional), 3: password (optional)]
            $name = $row[0] ?? '';
            $email = $row[1] ?? '';
            $studentId = $row[2] ?? '';
            $password = $row[3] ?? '';
        } elseif ($emailIdx === 0) {
            // [0: email, 1: name / last_name, 2: student_id, ...]
            $email = $row[0] ?? '';
            $name = $row[1] ?? '';
            $studentId = $row[2] ?? '';
            $password = $row[3] ?? '';
        } elseif ($count === 5) {
            // Default 5 columns fallback
            $lastName = $row[0] ?? '';
            $firstName = $row[1] ?? '';
            $email = $row[2] ?? '';
            $studentId = $row[3] ?? '';
            $password = $row[4] ?? '';
        } elseif ($count === 4) {
            // Default 4 columns: name, email, student_id, password
            $name = $row[0] ?? '';
            $email = $row[1] ?? '';
            $studentId = $row[2] ?? '';
            $password = $row[3] ?? '';
        } else {
            return null;
        }

        return [
            'last_name' => $lastName,
            'first_name' => $firstName,
            'name' => $name,
            'email' => $email,
            'student_id' => $studentId,
            'password' => $password,
        ];
    }

    protected function detectDelimiter(string $sampleLine): string
    {
        $delimiters = [',', ';', "\t"];
        $maxCount = 0;
        $bestDelimiter = ',';

        foreach ($delimiters as $delim) {
            $count = count(str_getcsv($sampleLine, $delim, '"', '\\'));
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        return $bestDelimiter;
    }

    /**
     * Remove accents, spaces, special chars and lowercase for header normalization.
     */
    protected function normalizeString(string $str): string
    {
        $str = mb_strtolower(trim($str), 'UTF-8');
        // Simple transliteration of Vietnamese accents
        $accents = [
            'a' => ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ'],
            'e' => ['è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ'],
            'i' => ['ì','í','ị','ỉ','ĩ'],
            'o' => ['ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ'],
            'u' => ['ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ'],
            'y' => ['ỳ','ý','ỵ','ỷ','ỹ'],
            'd' => ['đ'],
        ];
        foreach ($accents as $nonAccent => $accentList) {
            $str = str_replace($accentList, $nonAccent, $str);
        }
        return preg_replace('/[^a-z0-9]/', '', $str) ?? '';
    }
}
