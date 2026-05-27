<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model {
    protected $fillable = ['class_id', 'title', 'description', 'instructions', 'due_at', 'is_active', 'created_by'];
    protected $casts = ['due_at' => 'datetime', 'is_active' => 'boolean'];

    public function class() { return $this->belongsTo(Classes::class, 'class_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function submissions() { return $this->hasMany(Submission::class); }
}
