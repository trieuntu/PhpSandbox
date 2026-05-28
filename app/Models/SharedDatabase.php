<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedDatabase extends Model
{
    protected $fillable = [
        'slug', 'display_name', 'description', 'permission', 'tables_info', 'created_by',
    ];

    protected $casts = [
        'tables_info' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMysqlDbName(): string
    {
        return 'sandbox_shared_' . $this->slug;
    }

    public function getPermissionLabelAttribute(): string
    {
        return match ($this->permission) {
            'readonly'  => 'Chỉ đọc',
            'readwrite' => 'Đọc & Ghi',
            default     => 'Tắt',
        };
    }

    public function getPermissionColorAttribute(): string
    {
        return match ($this->permission) {
            'readonly'  => 'blue',
            'readwrite' => 'green',
            default     => 'gray',
        };
    }
}
