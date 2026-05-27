<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SandboxState extends Model {
    protected $table = 'sandbox_state';
    public $timestamps = false;
    protected $fillable = ['user_id', 'context_type', 'context_id', 'session_data', 'cookie_data', 'last_used_at'];
    protected $casts = ['session_data' => 'array', 'cookie_data' => 'array', 'last_used_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}
