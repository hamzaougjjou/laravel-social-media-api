<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Like;
use App\Models\Post;
use JWTAuth;
use App\Traits\NotificationsTrait;

class LikeController extends Controller
{
    use NotificationsTrait;

    public function index($postId)
    {
        $auth_user = JWTAuth::user()->id;
        $like = Like::where("user_id", $auth_user)
            ->where("post_id", $postId)->first();

        if ($like === null) { //user not liked a post excute this code
            $creatLike = Like::create([
                'post_id' => $postId,
                'user_id' => $auth_user
            ]);
            if ( !$creatLike )  
                return response()->json([
                    "success" => false,
                    "liked" => false,
                    "message" => "error to like post"
                ], 400);

            $post = Post::find($postId);
            $content = [
                "user_id" => $auth_user,
                "post_id" => $auth_user,
                "message" => "your post is liked"
            ];
            // check if post is not mine
            if ( $post->user_id  != $auth_user)
                $notice = $this->createNotification( $post->user_id , $content , "like_post");

            return response()->json([
                    "success" => true,
                    "liked" => true,
                    "message" => "post liked successfully",
                ], 200);

        } else { //user already liked a post this code will be excuted
            $deleteLike = Like::where("post_id", $postId)
                ->where("user_id",$auth_user)->delete();
            if ($deleteLike)
                return response()->json([
                    "success" => true,
                    "liked" => false,
                    "message" => "post disliked successfully",
                ], 200);
            return response()->json([
                "success" => false,
                "liked" => true,
                "message" => "error to dislike post",
            ], 400);

        }
    }

}