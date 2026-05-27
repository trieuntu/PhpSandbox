<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model {
    protected $fillable = ['class_id', 'title', 'description', 'instructions', 'starts_at', 'ends_at', 'time_limit_minutes', 'is_active', 'created_by'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];

    public function class() { return $this->belongsTo(Classes::class, 'class_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function attempts() { return $this->hasMany(ExamAttempt::class); }
    public function submissions() { return $this->hasMany(Submission::class); }

    public function isOpen(): bool {
        $now = now();
        return $this->is_active && $this->starts_at <= $now && $this->ends_at >= $now;
    }
}
