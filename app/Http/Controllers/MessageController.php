<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use App\Models\File;
use Illuminate\Http\Request;
use App\Http\Controllers\TimeConverter;
use JWTAuth;
use DB;

class MessageController extends TimeConverter
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }
    public function conversation($user_id)
    {
        $auth_id = JWTAuth::user()->id;
        $messages = DB::select('call conversation_messages( ? , ? )', [$auth_id, $user_id]);
        $user = DB::select('call conversation_user_info( ? )', [$user_id]);

        foreach ($messages as $message) {
            # code...
            $message->time = TimeConverter::secondsToTime(time() - strtotime($message->created_at));
        }

        return response()
            ->json([
                "success" => true,
                "user" => $user[0],
                "messages" => $messages
            ], 200);
    }

    public function store(Request $request, $reciever_id)
    {
        $auth_id = JWTAuth::user()->id;
        //check in reciever user exist in db
        $reciever_user = User::where('id', "=", $reciever_id)->first();
        if ($reciever_user === null) {
            return response()->json([
                'success' => false,
                'error' => "user not found"
            ]);
        }
        // check if user uploaded a message image 
        $message_img = $request->file('image');
        $message_img_data = null;
        if ($message_img != null) {
            $image_path = $message_img->store('images/messages', 'public');
            $data = File::create([
                "type" => "image/png",
                "size" => 20025,
                "file" => "storage/" . $image_path
            ]);
            $message_img_data = $data;
        }
        // =================================================
        $message_type = 'text';
        $message_img_id = 1;
        if ($message_img_data != null) {
            $message_img_id = $message_img_data->id;
            $message_type = "image";
        }
        if ($message_img_id === 1 && strlen(trim($request->content)) === 0) {
            return response()->json([
                'success' => false,
                "code" => -1,
                'error' => "invalid data sent",
            ]);
        }
        $user = Message::create(
            [
                "content" => $request->content,
                "status" => 0,
                "user_id" => $auth_id,
                "reciever_id" => $reciever_id,
                "file_id" => $message_img_id,
                "type" => $message_type
            ]
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function destroy(Message $message)
    {
        //
    }
}