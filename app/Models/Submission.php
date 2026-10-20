<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model {
    public $timestamps = false;
    protected $fillable = [
        'user_id', 'assignment_id', 'exam_id', 'title', 'code', 'files',
        'output_html', 'output_errors', 'execution_status', 'execution_time_ms', 'memory_used_kb',
        'submitted_at', 'score', 'teacher_feedback', 'graded_at', 'graded_by',
    ];
    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'files' => 'array',
        'score' => 'float',
    ];

    /** Returns the entry filename (first key of files, or 'index.php' if it exists). */
    public function entryFile(): string {
        if (empty($this->files)) return '';
        $keys = array_keys($this->files);
        return in_array('index.php', $keys) ? 'index.php' : $keys[0];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function assignment() { return $this->belongsTo(Assignment::class); }
    public function exam() { return $this->belongsTo(Exam::class); }
    public function grader() { return $this->belongsTo(User::class, 'graded_by'); }

    public function isGraded(): bool { return $this->score !== null; }
    public function isPending(): bool { return $this->execution_status === 'pending'; }
    public function isSuccess(): bool { return $this->execution_status === 'success'; }
}
