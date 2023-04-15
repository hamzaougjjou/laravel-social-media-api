<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\File;

class Group extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        "description",
        "profile_img",
        "cover_img"
    ];

    /**
     * Get the file associated with the Group
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }


}