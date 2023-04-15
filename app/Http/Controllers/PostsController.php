<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\File;
use App\Models\Friend;
use App\Models\GroupsMembers;
use App\Models\GroupsPosts;
use App\Traits\FilesRefTrait;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Http\Request;
use DB;

class PostsController extends TimeConverter
{
    use FilesRefTrait;
    public function index($postsStart)
    {
        // $MyFriendsList = $this->allFriends();
        // if ($MyFriendsList === null) {
        //     return response()->json([
        //         'success' => true,
        //         'message' => 'posts not exist',
        //         "posts" => null,
        //     ], 404);
        // }
        // //start , number off records
        // $posts = DB::select("select * from posts where user_id in (" . implode(',', $this->allFriends()) . ") LIMIT $postsStart,10");
        // $posts = Post::wherein("user_id", $this->allFriends())
        //     ->get();

        // return response()->json([
        //     'success' => true,
        //     'message' => 'posts retrieved successfully',
        //     "posts" => $posts,
        //     "friends" => $this->allFriends(),
        // ], 200);
    }

    public function friendsPosts(Request $request)
    {
        $auth_user = Auth::user()->id;
        $posts = DB::select("
                            SELECT
                                posts.id as post_id,
                                posts.user_id,
                                posts.description,
                                posts.type as post_type,
                                posts.created_at as created_at,
                                files.file as file,
                                post_liked($auth_user,posts.id) as liked,
                                users.name,
                                user_profile_img(posts.user_id) as profile_img,
                                (select count(*) from likes where post_id=posts.id) as likes_count,
                                (select count(*) from comments where post_id=posts.id) as comments_count
                            FROM
                                posts
                                inner JOIN users on posts.user_id=users.id
                                INNER JOIN files ON files.id = posts.file_id
                                INNER JOIN friends ON (friends.request_from=$auth_user and friends.request_to = posts.user_id )
                                or (friends.request_to=$auth_user and friends.request_from = posts.user_id )
                                AND (friends.status = true)
                                WHERE posts.user_id !=$auth_user
                                LIMIT 10;
        "); //ORDER BY RAND()

        foreach ($posts as $post) {
            $time = TimeConverter::secondsToTime(time() - strtotime($post->created_at));
            $post->time = $time;
        }
        return response()->json([
            "posts" => $posts,
            "message" => "posts of your friends",
            "status" => 200,
        ]);

    }

    public function store(Request $request)
    {
        //store posts
        $auth_user = Auth::user()->id;
        // check if user uploaded a post image 
        $post_img = $request->file('image');
        $post_img_data = null;
        if ($request->has('image')) {
            $path = $post_img->store('images/posts', 'public');
            $file = File::create([
                "type" => "image",
                "size" => 20025,
                "file" => "storage/" . $path
            ]);
            $post_img_data = $file;
            $this->saveFilesRef($auth_user ,$file->id , "post");
        }
        
        $input = $request->all();
        $input["user_id"] = $auth_user;
        $input["is_group_post"] = false;
        $input["file_id"] = 1;
        $input["type"] = "text";
        if ($request->has("group_id")) {
            $input["is_group_post"] = true;
        }
        $file = null;
        if ($post_img_data != null) {
            $input["file_id"] = $post_img_data->id;
            $input["type"] = "image";
            $file = $post_img_data->file;
        }

        $post = Post::create($input);
        if (!$post) {
            return response()->json([
                "message" => "not acceptable",
                "status" => 406,
            ]);
        }
        $post['file'] = $file;
        //save data in group posts tables 
        if ($request->group_id) {
            $is_admin = GroupsMembers::where("user_id", $auth_user)
                ->where("group_id", $request->group_id)
                ->where("role", "admin")
                ->first();
            $post_accepted = false;
            if ($is_admin != null) {
                $post_accepted = true;
            }
            $groupPost = GroupsPosts::create([
                "post_id" => $post->id,
                "group_id" => $request->group_id,
                "accepted" => $post_accepted,
            ]);
            return response()->json([
                "success" => true,
                "data" => ["post" => $post, "groupPost" => $groupPost],
                "message" => "post created"
            ], 200);
        } else {
            return response()->json([
                "success" => true,
                "data" => $post,
                "message" => "post created"
            ], 200);
        }

    }

    public function update(Request $request, $id)
    {
        $auth_user = Auth::user()->id;
        $validate = Validator::make($request->all(), [
            'description' => 'string',
            'user_id' => 'required|integer',
            'is_group_post' => 'required',
            'type' => 'required|string',
            'file_id' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors()->toJson(), 400);
        }

        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                "message" => "not found post",
                "status" => 404,
            ]);
        }

        $post->update($request->all());
        return response()->json([
            "data" => $post,
            "message" => "post updated",
            "status" => 200,
        ]);
    }
    public function delete($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json([
                "message" => "not found post",
                "status" => 404,
            ]);
        }
        $post->delete();

        return response()->json([
            "message" => "post deleted",
            "status" => 201,
        ]);

    }

    public function likesCount($postId)
    {
        $count = DB::select("select count(*) as likes_count from likes where post_id=? ", [$postId]);
        if (!$count) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "error to get post s likes count"
                ]
                ,
                200
            );
        }
        return response()->json(
            [
                "success" => true,
                "count" => $count[0]->likes_count,
                "message" => "likes count retrieved successfullly"
            ]
            ,
            200
        );
    }
    public function commentsCount($postId)
    {
        $count = DB::select("select count(post_id) as comments_count from comments where post_id = ? ", [$postId]);
        if (!$count) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "error to get comments count"
                ]
                ,
                200
            );
        }
        return response()->json(
            [
                "success" => true,
                "count" => $count[0]->comments_count,
                "message" => "comments count retrieved successfully"
            ]
            ,
            200
        );
    }

    public function show($postId)
    {

        $auth_user = Auth::user()->id;
        $posts = DB::select("
                            SELECT
                                posts.id as post_id,
                                posts.user_id,
                                posts.description,
                                posts.type as post_type,
                                posts.created_at as created_at,
                                files.file as file,
                                post_liked($auth_user,posts.id) as liked,
                                users.name,
                                user_profile_img(posts.user_id) as profile_img,
                                (select count(*) from likes where post_id=posts.id) as likes_count,
                                (select count(*) from comments where post_id=posts.id) as comments_count
                            FROM
                                posts
                                inner JOIN users on posts.user_id=users.id
                                INNER JOIN files ON files.id = posts.file_id
                                WHERE posts.id=$postId
                                LIMIT 1;
        ");
        $post = $posts[0];
        $time = TimeConverter::secondsToTime(time() - strtotime($post->created_at));
        $post->time = $time;
        return response()->json([
            "post" => $post,
            "message" => "post item reatreaved successfully",
            "status" => 200,
        ]);
    }
    
}