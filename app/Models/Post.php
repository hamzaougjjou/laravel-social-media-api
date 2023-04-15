<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\GroupsPosts;

class Post extends Model
{
    use HasFactory;
    protected $fillable = [
        'description',
        'user_id',
        'is_group_post',
        'type',
        'file_id',
    ];

    public function user()
    {
        return $this -> belongsTo(User::class);
    }
    public function GroupsPosts()
    {
        return $this->belongsToMany(GroupsPosts::class);
    }
    
}
