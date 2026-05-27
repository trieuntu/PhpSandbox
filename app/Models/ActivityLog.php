<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model {
    public $timestamps = false;
    protected $fillable = ['user_id', 'action', 'description', 'metadata', 'ip_address', 'user_agent', 'created_at'];
    protected $casts = ['metadata' => 'array', 'created_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}
