<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\model\User;
use App\model\Group;

class File extends Model
{
    use HasFactory;
    protected $fillable = [
        "type",
        "size",
        "file"
    ];

        /**
         * Get the user that owns the File
         *
         * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
         */
        public function user()
        {
            return $this->belongsTo(User::class, 'profile_img','cover_img');
        }
}

