<?php

namespace App\Traits;

use App\Models\FilesReferences;

trait FilesRefTrait
{
    public function saveFilesRef($userId, $fileId, $type)
    {
        $file_ref = FilesReferences::create([
            'user_id' => $userId,
            'file_id' => $fileId,
            'type' => $type
        ]);
        if ($file_ref)
            return true;
        return false;

    }

}