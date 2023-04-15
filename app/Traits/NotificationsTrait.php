<?php

namespace App\Traits;

use App\Models\Notification;

trait NotificationsTrait
{
    public function createNotification($noticeTo, $content, $type)
    {
        //content should be a json
        $content = json_encode($content);
        try {
            $notice = Notification::create([
            'user_id' => $noticeTo,
            "content" => $content,
            'type' => $type ,
            ]);
            return true;
        } catch (\Throwable $th) {
            //throw $th;
            return false;
        }

    }

}