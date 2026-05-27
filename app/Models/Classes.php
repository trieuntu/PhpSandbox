<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model {
    protected $table = 'classes';
    protected $fillable = ['name', 'description', 'created_by', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function enrollments() { return $this->hasMany(ClassEnrollment::class, 'class_id'); }
    public function students() { return $this->belongsToMany(User::class, 'class_enrollments', 'class_id', 'user_id'); }
    public function assignments() { return $this->hasMany(Assignment::class, 'class_id'); }
    public function exams() { return $this->hasMany(Exam::class, 'class_id'); }
    public function announcements() { return $this->hasMany(Announcement::class, 'class_id'); }
}
