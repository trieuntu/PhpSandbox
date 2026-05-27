<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model {
    protected $fillable = ['class_id', 'title', 'content', 'is_pinned', 'created_by'];
    protected $casts = ['is_pinned' => 'boolean'];

    public function class() { return $this->belongsTo(Classes::class, 'class_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function isGlobal(): bool { return $this->class_id === null; }
}
