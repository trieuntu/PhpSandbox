<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassEnrollment extends Model {
    public $timestamps = false;
    protected $fillable = ['class_id', 'user_id', 'enrolled_at'];
    protected $casts = ['enrolled_at' => 'datetime'];

    public function class() { return $this->belongsTo(Classes::class, 'class_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
