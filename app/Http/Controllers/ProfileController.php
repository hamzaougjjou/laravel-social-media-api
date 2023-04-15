<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\File;
use App\Models\User;
use App\Models\FilesReferences;
use App\Traits\FilesRefTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
use Validator;

class ProfileController extends TimeConverter
{
    use FilesRefTrait;
    public function profilePosts()
    {
        $user = Auth::user();
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

    public function changeProfileName(Request $request)
    {
        // {password: 'sdfergfertge', new_name: 'xcdfg'}
        $auth_user = Auth()->user()->id;

        $validate = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
            "new_name" => "required|string|min:3",
        ]);
        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'code' => -1,
                'message' => "invalid data sent",
            ]);
        }
        $user = User::find($auth_user);
        $user->name = $request->new_name;
        $saved = $user->save();
        if ($saved) {
            return response()->json([
                'success' => true,
                'message' => "name changed successfully"
            ]);
        }
        return response()->json([
            'success' => false,
            "code" => 0,
            'message' => "Somthing went wrong"
        ]);

    }

    public function changeProfileCover(Request $request)
    {
        // check if user uploaded a profile image 
        $profile_img = $request->file('profile_img');
        $cover_img = $request->file('cover_img');
        if ($profile_img === null && $cover_img === null) {
            return response()->json([
                'success' => false,
                'message' => 'no data sended'
            ]);
        }
        $error = false;
        $auth_user = Auth()->user()->id;
        $user = User::find($auth_user);

        //if user uploaded a cover image and profile img
        if ($profile_img != null && $cover_img != null) {
            $profile_path = $profile_img->store('images/profiles', 'public');
            $cover_path = $cover_img->store('images/covers', 'public');
            $profile_db = File::create([
                "type" => "image/png",
                "size" => 20025,
                "file" => "storage/" . $profile_path
            ]);
            $cover_db = File::create([
                "type" => "image/png",
                "size" => 20025,
                "file" => "storage/" . $cover_path
            ]);
            $user->profile_img = $profile_db->id;
            $user->cover_img = $cover_db->id;
            $user->save();
            $this->saveFilesRef($auth_user, $profile_db->id, "profile");
            $this->saveFilesRef($auth_user, $cover_db->id, "cover");

        } // check if user uploaded a profile image 
        else if ($profile_img != null) {
            $profile_path = $profile_img->store('images/profiles', 'public');
            $profile_db = File::create([
                "type" => "image/png",
                "size" => 20025,
                "file" => "storage/" . $profile_path
            ]);
            $user->profile_img = $profile_db->id;
            $user->save();
            $this->saveFilesRef($auth_user, $profile_db->id, "profile");
        } // check if user uploaded a cover image 
        else if ($cover_img != null) {
            $cover_path = $cover_img->store('images/covers', 'public');
            $cover_db = File::create([
                "type" => "image/png",
                "size" => 20025,
                "file" => "storage/" . $cover_path
            ]);
            $user->cover_img = $cover_db->id;
            $user->save();
            $this->saveFilesRef($auth_user, $cover_db->id, "cover");
        }
        // ===================================================
        //Token created, return with success response and jwt token
        return response()->json([
            'success' => true,
            'message' => 'data updated successfully'
        ]);

        // ===================================================
    }

    public function getProfileImages()
    {
        $auth_user = Auth()->user()->id;
        $images = FilesReferences::where("user_id", $auth_user)
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
    public function getCoverImages()
    {
        $auth_user = Auth()->user()->id;
        $images = FilesReferences::where("user_id", $auth_user)
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
    public function getPostsImages()
    {
        $auth_user = Auth()->user()->id;
        $images = FilesReferences::where("user_id", $auth_user)
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
