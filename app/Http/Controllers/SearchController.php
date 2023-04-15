<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use Illuminate\Http\Request;
use App\Models\User;
use DB;
use JWTAuth;

class SearchController extends Controller
{
    //get and search for users
    public function searchGetUsers(Request $request)
    {
        $auth_user = JWTAuth::user()->id;

        $q = $request->q;

        // return response()->json([
        //     "success" => $q
        // ], 200);

        $users = DB::select("
                    SELECT
                        id,
                        name,
                        user_profile_img(id) AS profile_img,
                        user_breed_name(id) AS breed_name,
                        user_posts_count(id) AS posts_count,
                        user_friends_count(id) AS friends_count
                    FROM
                        users
                    WHERE
                        id != ? AND (name LIKE '%" . $q . "%')", [$auth_user]);

        foreach ($users as $user) {
            $friend = Friend::
            where("request_from", $auth_user)
            ->where("request_to", $user->id)
            ->orwhere("request_from", $user->id)
            ->where("request_to", $auth_user)
            ->first();

            $user->xxxx = $friend;
            if ($friend === null) {
                $user->friend_status = -1;
            } else {
                $user->friend_status = 0;
                if ($friend->status === 1 || $friend->status === true)
                    $user->friend_status = 1;
            }
        }

        if ($users)
            return response()->json([
                "success" => true,
                "users" => $users,
                "message" => "users retrieved successfully"
            ], 200);
        return response()->json([
            "success" => false,
            "code" => -1,
            "message" => "error to get users"
        ], 200);
    }
}