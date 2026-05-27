<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'student_id', 'name', 'email', 'password', 'role', 'is_active',
        'sandbox_db_name', 'sandbox_db_user', 'sandbox_db_pass', 'last_active_at',
    ];

    protected $hidden = ['password', 'remember_token', 'sandbox_db_pass'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_active_at'    => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    public function enrollments() { return $this->hasMany(ClassEnrollment::class); }
    public function classes() { return $this->belongsToMany(Classes::class, 'class_enrollments', 'user_id', 'class_id'); }
    public function submissions() { return $this->hasMany(Submission::class); }
    public function sandboxState() { return $this->hasMany(SandboxState::class); }
    public function activityLogs() { return $this->hasMany(ActivityLog::class); }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isStudent(): bool { return $this->role === 'student'; }
    public function hasSandboxDatabase(): bool { return $this->sandbox_db_name !== null; }
}
