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

    public function durationFormatted(): string {
        if (!$this->started_at || !$this->submitted_at) return '—';
        $seconds = abs($this->started_at->diffInSeconds($this->submitted_at));
        if ($seconds < 60) return "{$seconds} giây";
        $minutes = floor($seconds / 60);
        $remSec = $seconds % 60;
        if ($minutes < 60) {
            return "{$minutes} phút" . ($remSec > 0 ? " {$remSec}s" : '');
        }
        $hours = floor($minutes / 60);
        $remMin = $minutes % 60;
        return "{$hours} giờ" . ($remMin > 0 ? " {$remMin}p" : '');
    }
}
