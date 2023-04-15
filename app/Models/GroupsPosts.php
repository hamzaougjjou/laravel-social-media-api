<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Post;
class GroupsPosts extends Model
{
    use HasFactory;
    protected $fillable = [
        "post_id",
        "group_id",
        "accepted"
    ];

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }
}