<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Post;
use App\Models\CommentsReplies;
use JWTAuth;
use DB;
use App\Http\Controllers\TimeConverter;
use App\Traits\NotificationsTrait;

class CommentController extends TimeConverter
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    use NotificationsTrait;
    public function index($postId) //get all comments for a post
    {
        // $comments = Comment::all()
        //     ->where('post_id', "=", $postId);
        try {
            $comments = DB::select('call post_comments( ? ) ', [$postId]);
            foreach ($comments as $comment) {
                $comment->time = TimeConverter::secondsToTime(time() - strtotime($comment->created_at));
            }
            return response()->json(
                [
                    "success" => true,
                    "comments" => $comments,
                    "message" => "comments retrived successfully"
                ],
                200
            );
        } catch (\Exception $ex) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "error to get comments to post"
                ],
                400
            );
        }


    }

    public function store(Request $request)
    {
        // return $request;
        $auth_user = JWTAuth::user()->id;
        $comment = Comment::create([
            'content' => $request->content,
            'post_id' => $request->post_id,
            'user_id' => $auth_user,
        ]);
        if (!$comment) {
            return response()->json([
                "success" => false,
                "message" => 'error to create comment'
            ], 400);
        }
        $comment->time = "just now"; //TimeConverter::secondsToTime(time() - strtotime($comment->created_at) );

        // send notification to post creator
        $post = Post::find($request->post_id);
        //check if post is not mine 
        if( $auth_user != $post->user_id){
            $content = [
                "post_id" => $request->post_id,
                "user_id" => $auth_user,
                "message" => "there is new comment on your post"
            ];
            $notice = $this->createNotification($post->user_id,$content, "comment");
        }
        //return response
        return response()->json([
            "success" => true,
            "comment" => $comment,
            "message" => "comment created successfully"
        ], 200);
    }

    public function commentReplies($commentId) //get all comments for a post
    {
        // $comments = Comment::all()
        //     ->where('post_id', "=", $postId);
        $comments = DB::select('call comment_replies( ? )', [$commentId]);

        if (!$comments) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "error to get comment replies to comment"
                ],
                400
            );
        }
        foreach ($comments as $comment) {
            $comment->time = TimeConverter::secondsToTime(time() - strtotime($comment->created_at));
        }
        return response()->json(
            [
                "success" => true,
                "comment_replies" => $comments,
                "message" => "comment replies retrived successfully"
            ],
            200
        );
    }

    public function storeCommentReplay(Request $request, $commentId)
    {
        $auth_user = JWTAuth::user()->id;
        if (strlen(trim($request->content)) === 0) {
            return response()->json(
                [
                    "error" => true,
                    "error_msg" => 'comment field is required'
                ]
                ,
                400
            );
        }
        $comment = CommentsReplies::create([
            'content' => $request->content,
            'comment_id' => $commentId,
            'user_id' => $auth_user
        ]);
        if (!$comment) {
            return response()->json([
                "success" => false,
                "message" => 'error to create comment'
            ], 400);
        }
        $updateComment = Comment::find($commentId);
        $updateComment['has_reply'] = true;
        $updateComment->save();

        $comment->time = "just now";
        // send notification to post creator
        $post = Post::find($request->post_id);
        //check if comment is not mine
        if( $updateComment->user_id != $auth_user ){
            $content = [
                "post_id" => $updateComment->post_id,
                "comment_id" => $updateComment->id,
                "user_id" => $auth_user,
                "message" => "there is new reply on your comment comment"
            ];
            $notice = $this->createNotification($updateComment->user_id,$content, "comment_reply");
        }

        return response()->json([
            "success" => true,
            "comment_reply" => $comment,
            "message" => "comment created successfully"
        ], 200);
        //update comment table set has_reply = true for comment_id = $commentId
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}