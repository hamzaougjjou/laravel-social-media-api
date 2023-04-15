<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Notification;
use App\Models\User;
use App\Models\File;
use App\Models\Group;
use App\Http\Controllers\TimeConverter;
use JWTAuth;
use DB;

class NotificationController extends TimeConverter
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //get all notification for ahth user 
    {
        $auth_user = JWTAuth::user()->id;
        $notifications = Notification::where('user_id', $auth_user)->orderBy('created_at', 'DESC')->get();
        foreach ($notifications as $notification) {
            # code...
            $content = json_decode($notification->content);
            $notification->content = $content;
            $notification->time = TimeConverter::secondsToTime(time() - strtotime($notification->created_at));;

            if ( $notification->type === "group_accept") {

                $group = Group::find($content->group_id);
                $notification->group = [
                    'id' => $group->id,
                    'name' => $group->name,
                    'profile_img' => File::find($group->profile_img)->file
                ];

            } else {
                $user = User::find($content->user_id);
                $notification->user = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'profile_img' => File::find($user->profile_img)->file
                ];
            }
        }
        //return response
        return response()->json([
            "success" => true,
            "message" => "notifications retrieved successfully",
            "notifications" => $notifications
        ], 200);
    }

    public function setNotificationRead()
    {

        $auth_user = JWTAuth::user()->id;
        $notifications = DB::table('notifications')
            ->where('user_id', $auth_user)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            "success" => true,
            "message" => "notifications read status updated successfully"
        ], 200);
    }

    public function destroy($notificationId)
    {
        $notification = Notification::find($notificationId);
        $notification->delete();
        return response()->json([
            "success" => true,
            "message" => "notification deleted successfully"
        ], 200);
    }
}
