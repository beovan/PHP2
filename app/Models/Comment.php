<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    protected $fillable = ['content', 'user_id', 'post_id', 'parent_id'];

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
