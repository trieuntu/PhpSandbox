<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model {
    protected $fillable = ['uuid', 'path', 'mime', 'original_name', 'size', 'user_id'];
}
