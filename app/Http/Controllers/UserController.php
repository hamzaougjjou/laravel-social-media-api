<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\File;
use App\Models\User;
use App\Models\FilesReferences;
use App\Models\AnimalsBreed;
use App\Models\Friend;
use App\Models\Post;

use App\Traits\FilesRefTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class UserController extends TimeConverter
{
    use FilesRefTrait;

    public function index($userId) //get basic user info
    {
        $user = User::find($userId);
        if ($user === null)
            return response()->json([
                "success" => false,
                "code" => -1,
                "message" => "user not found"
            ], 200);

        $user->profile_img = File::find($user->profile_img)->file;

        $user->profile_img = null;
        $user->cover_img = null;

        if ($user->profile_img != null)
            $user->profile_img = File::find($user->profile_img)->file;

        if ($user->cover_img != null)
            $user->cover_img = File::find($user->cover_img)->file;

        $user->breed = AnimalsBreed::find($user->breed_id)->name;
        $user->posts_count = Post::where("user_id", $user->id)->count();
        $user->friends_count = Friend::where("status", true)
                                ->where("request_from", $userId)
                                ->orwhere("request_to", $userId)
                                ->count();

        return response()->json([
            "success" => true,
            "user" => $user,
            "message" => "user info retrieved successfully"
        ], 200);
    }

    public function friends($userId)
    {
        $friends = DB::select("CALL user_online_friends( ? , ? )", [$userId, time()]);
        return response()->json([
            "success" => true,
            "friends" => $friends,
            "status" => 200
        ]);
    }

    public function userPosts( $userId )
    {
        $user = User::find($userId);
        $user_id = $user->id;
        //=======================
        if ($user->profile_img != null)
            $user->profile_img = File::where('id', $user->profile_img)
                ->first()->file;
        else
            $user->profile_img = null;
        $user = [
            "id" => $user_id,
            "name" => $user->name,
            "profile_img" => $user->profile_img,
        ];
        //=======================
        $posts = DB::select("
            select 
            *
            from
                posts
            WHERE
             (posts.user_id = ? and is_group_post=false)
            ORDER by 
                posts.created_at ASC
            ", [$user_id]);

        foreach ($posts as $post) {
            $post->liked = true;
            $like = Like::where("post_id", "=", $post->id)
                ->where("user_id", "=", $user_id)
                ->first();
            $post->like = $like;
            if ($like === null)
                $post->liked = false;

            if ($post->type != "text")
                $post->file = File::where('id', $post->file_id)
                    ->first()->file;
            else
                $post->file = null;

            $post->post_id = $post->id;
            $post->comments_count = Comment::where("post_id", $post->id)->count();
            $post->likes_count = Like::where("post_id", $post->id)->count();
            $post->time = TimeConverter::secondsToTime(time() - strtotime($post->updated_at));
        }

        return response()->json([
            'success' => true,
            'message' => 'posts retrieved successfully',
            "data" => ["user" => $user, "posts" => $posts],
            "user" => $user
        ], 200);
    }
    public function getProfileImages( $userId )
    {
        
        $images = FilesReferences::where("user_id", $userId)
            ->where("type", 'profile')
            ->orderBy('created_at', 'DESC')
            ->get();
        $imagesPath = [];
        foreach ($images as $image) {
            array_push($imagesPath, File::where('id', $image->file_id)->first()->file);
        }
        return response()->json([
            'success' => true,
            'images' => $imagesPath,
            'message' => 'data reatreaved successfully'
        ]);
    }
    public function getCoverImages($userId)
    {
        $images = FilesReferences::where("user_id", $userId)
            ->where("type", 'cover')
            ->orderBy('created_at', 'DESC')
            ->get();
        $imagesPath = [];
        foreach ($images as $image) {
            array_push($imagesPath, File::where('id', $image->file_id)->first()->file);
        }
        return response()->json([
            'success' => true,
            'images' => $imagesPath,
            'message' => 'data reatreaved successfully'
        ]);
    }
    public function getPostsImages( $userId )
    {
        $images = FilesReferences::where("user_id", $userId)
            ->where("type", 'post')
            ->orderBy('created_at', 'DESC')
            ->get();
        $imagesPath = [];
        foreach ($images as $image) {
            $file = File::where('id', $image->file_id)->first();
            if ($file->type === "image")
                array_push($imagesPath, $file->file);
        }
        return response()->json([
            'success' => true,
            'images' => $imagesPath,
            'message' => 'immages reatreaved successfully'
        ]);
    }
}
