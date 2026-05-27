<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model {
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['key', 'value', 'updated_by', 'updated_at'];

    public static function get(string $key, $default = null) {
        return static::find($key)?->value ?? $default;
    }

    public static function set(string $key, $value, ?int $updatedBy = null): void {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $updatedBy, 'updated_at' => now()]
        );
    }
}
