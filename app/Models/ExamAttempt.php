<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model {
    public $timestamps = false;
    protected $fillable = ['exam_id', 'user_id', 'started_at', 'submitted_at', 'expires_at'];
    protected $casts = ['started_at' => 'datetime', 'submitted_at' => 'datetime', 'expires_at' => 'datetime'];

    public function exam() { return $this->belongsTo(Exam::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function isExpired(): bool {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isSubmitted(): bool {
        return $this->submitted_at !== null;
    }
}
