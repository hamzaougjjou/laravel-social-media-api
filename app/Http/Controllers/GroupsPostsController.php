<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Models\Like;
use App\Models\Comment;
use App\Models\User;
use App\Models\File;
use App\Models\GroupsPosts;
use App\Models\GroupsMembers;
use App\Http\Controllers\TimeConverter;
use JWTAuth;

class GroupsPostsController extends TimeConverter
{
    public function posts(Request $request, $groupId)
    {
        $auth_user = Auth::user()->id;

        $groupPosts = DB::table('posts')
            ->select(
                'posts.id as post_id',
                'posts.user_id',
                'posts.description',
                'posts.type as post_type',
                'posts.file_id',
                'posts.updated_at as updated_at'
            )
            ->join('groups_posts', 'posts.id', '=', 'groups_posts.post_id')
            ->where('posts.is_group_post', true)
            ->where('groups_posts.group_id', $groupId)
            ->where('groups_posts.accepted', true)
            ->orderBy("posts.updated_at", "desc")
            ->get()
            ->toArray();
        foreach ($groupPosts as $groupPost) {
            # code...
            $dbUser = User::where('id', $groupPost->user_id)->first();
            $user = [];
            $time = TimeConverter::secondsToTime(time() - strtotime($groupPost->updated_at));
            $profile_img = $dbUser->profile_img;
            $user['profile_img'] = null;
            if ($profile_img != null) {
                $user['profile_img'] = File::where('id', $dbUser->profile_img)->first()->file;
            }
            $user['id'] = $dbUser->id;
            $user['name'] = $dbUser->name;
            $user['time'] = $time;
            $user['created_at'] = $groupPost->updated_at;

            if ( $groupPost->post_type === "image") {
                # get post file path
                $groupPost->file = File::where('id', $groupPost->file_id)->first()->file;
            }
            $groupPost->user = $user;
            $groupPost->time = $time;
            // get post likes count
            $groupPost->likes_count = Like::where("post_id", $groupPost->post_id)->count();
            $groupPost->liked = Like::where("post_id", $groupPost->post_id)
                ->where("user_id", $auth_user)
                ->count() > 0;
            $groupPost->comments_count = Comment::where("post_id", $groupPost->post_id)->count();
        }
        return response()->json([
            "posts" => $groupPosts,
            "message" => "group posts reatreaved successfully",
            "status" => 200,
        ]);

    }
    public function postsRequests(Request $request, $groupId)
    {
        $auth_user = Auth::user()->id;

        $groupPosts = DB::table('posts')
            ->select(
                'posts.id as post_id',
                'posts.user_id',
                'posts.description',
                'posts.type as post_type',
                'posts.file_id',
                'posts.updated_at as updated_at'
            )
            ->join('groups_posts', 'posts.id', '=', 'groups_posts.post_id')
            ->where('posts.is_group_post', true)
            ->where('groups_posts.group_id', $groupId)
            ->where('groups_posts.accepted', false)
            ->orderBy("posts.updated_at", "desc")
            ->get()
            ->toArray();
        foreach ($groupPosts as $groupPost) {
            # code...
            $dbUser = User::where('id', $groupPost->user_id)->first();
            $user = [];
            $time = TimeConverter::secondsToTime(time() - strtotime($groupPost->updated_at));
            $profile_img = $dbUser->profile_img;
            $user['profile_img'] = null;
            if ($profile_img != null) {
                $user['profile_img'] = File::where('id', $dbUser->profile_img)->first()->file;
            }
            $user['id'] = $dbUser->id;
            $user['name'] = $dbUser->name;
            $user['time'] = $time;
            $user['created_at'] = $groupPost->updated_at;

            if ( $groupPost->post_type === "image") {
                # get post file path
                $groupPost->file = File::where('id', $groupPost->file_id)->first()->file;
            }
            $groupPost->user = $user;
            $groupPost->time = $time;
            // get post likes count
            $groupPost->likes_count = Like::where("post_id", $groupPost->post_id)->count();
            $groupPost->liked = Like::where("post_id", $groupPost->post_id)
                ->where("user_id", $auth_user)
                ->count() > 0;
            $groupPost->comments_count = Comment::where("post_id", $groupPost->post_id)->count();
        }
        return response()->json([
            "posts" => $groupPosts,
            "message" => "group posts reatreaved successfully",
            "status" => 200,
        ]);

    }
    public function groupPostsReqCount($id)
    {
        //check if user is admin
        $auth_user = JWTAuth::user()->id;
        $is_admin = GroupsMembers::where("user_id", $auth_user)
            ->where("group_id", $id)
            ->where("role", "admin")
            ->first();

        if ($is_admin === null) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "you have not access to remove a member from group"
                ]
                ,
                400
            );
        }
        $postsReqCount = GroupsPosts::where('accepted', false)
            ->where("group_id", $id)
            ->count();
        return response()->json(
            [
                "success" => true,
                "count" => $postsReqCount,
                "message" => "group posts requests count reatreaved successfully"
            ]
            ,
            200
        );
    }

    public function acceptPost($groupId, $postId)
    {
        //check if user is admin
        $auth_user = JWTAuth::user()->id;
        $is_admin = GroupsMembers::where("user_id", $auth_user)
            ->where("group_id", $groupId)
            ->where("role", "admin")
            ->first();

        if ($is_admin === null) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "you have not access to remove a member from group"
                ]
                ,
                400
            );
        }
        $GroupPost = GroupsPosts::where('accepted', false)
            ->where("group_id", $groupId)
            ->where("post_id", $postId)
            ->first();

        if ($GroupPost === null) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "post not found"
                ]
                ,
                404
            );
        }
        $GroupPost->accepted = true;
        $GroupPost->save();
        return response()->json(
            [
                "success" => true,
                "message" => "group posts accepted successfully"
            ]
            ,
            200
        );
    }

}