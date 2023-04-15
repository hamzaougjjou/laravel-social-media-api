<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Friend;
use App\Models\User;
use Validator;
use JWTAuth;
use DB;
use App\Traits\NotificationsTrait;

class FriendsController extends Controller
{
    use NotificationsTrait;
    public function allFriends()
    {
        $auth_user = JWTAuth::user()->id;
        $friends = DB::select("CALL user_online_friends( ? , ? )", [$auth_user, time()]);
        return response()->json([
            "success" => true,
            "friends" => $friends,
            "status" => 200
        ]);
    }
    public function requests()
    {
        $auth_user = JWTAuth::user()->id;
        $requests = DB::select("CALL user_friend_requests( ? )", [$auth_user]);
        
        return response()->json([
            "success" => true,
            "requests" => $requests,
            "status" => 200
        ]);
    }
    public function sendRequest(Request $request)
    {
        $auth_user = JWTAuth::user()->id;
        // check if user exist in db
        $req_to_id_exist = User::where('id', '=', $request->request_to)->first();
        if ($req_to_id_exist === null) {
            return response()->json([
                "success" => false,
                "data" => null,
                "message" => 'user not found'
            ], 404);
        }
        //check if request already exists
        $request_from_exist = Friend::where('request_from', '=', $auth_user)
            ->where('request_to', '=', $request->request_to)
            ->first();
        $request_to_exist = Friend::where('request_to', '=', $auth_user)
            ->where('request_from', '=', $request->request_to)
            ->first();
        if ($request_from_exist != null || $request_to_exist != null) {
            return response()->json([
                "success" => false,
                "data" => null,
                "message" => 'request already exists'
            ], 200);
        }
        $friendRequest = Friend::create([
            'request_from' => $auth_user,
            'request_to' => $request->request_to,
        ]);
        if (!$friendRequest) {
            return response()->json([
                "success" => false,
                "data" => null,
                "message" => 'friend request not send'
            ], 400);
        }
        return response()->json([
            "success" => true,
            "data" => $friendRequest,
            "message" => "friend request sent successfully"
        ], 200);
    }
    public function acceptRequest($id)
    {
        $auth_user = JWTAuth::user()->id;
        //check if request already exists
        $request_exist = Friend::where('id', '=', $id);
        // if request exist
        if ($request_exist === null) {
            return response()->json([
                "success" => false,
                "message" => 'friend request not found'
            ], 404);
        }
        $friend = Friend::find($id);
        $friend->status = true;
        $friend->save();

        
        $content = [
            "user_id" => $friend->request_to,
            "message" => "accept friend request"
        ];
        $notice = $this->createNotification( $friend->request_from , $content , "accept_friend");

        return response()->json([
            "success" => true,
            "data" => $friend,
            "message" => 'friend accepted'
        ], 200);
    }

    public function userRequestsCount()
    {
        $auth_user = JWTAuth::user()->id;
        $requests_count = Friend::where("request_to", $auth_user)
            ->where("status", false)
            ->count();
        // if request exist
        return response()->json([
            "success" => true,
            "requests_count" => $requests_count,
            "message" => 'requests exists'
        ], 200);
    }

    public function deleteFriend($friendId)
    {
        $auth_user = JWTAuth::user()->id;
        $friend = Friend::
            where("request_to", $auth_user)
            ->where("request_from", $friendId)

            ->orwhere("request_to", $friendId)
            ->where("request_from", $auth_user)
            ->first();
        $deleting = $friend->delete();
        if ($deleting)
            return response()->json([
                "success" => true,
                "message" => 'friend deleted successfully'
            ], 200);
        return response()->json([
            "success" => false,
            "message" => 'error to delete friend'
        ]);
    }

}