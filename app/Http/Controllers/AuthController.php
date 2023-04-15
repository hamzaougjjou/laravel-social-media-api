<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\File;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use DB;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register(Request $request)
    {
        // check if email exist in database
        $userEmailDetails = User::where('email', "=", $request->email)->first();
        if ($userEmailDetails != null) {
            return response()->json([
                'success' => false,
                'error' => "user aleady exists",
            ]);
        }
        // check if user uploaded a profile image 
        $profile_img = $request->file('profile_img');
        $profile_img_data = null;
        if ($profile_img != null) {
            $image_path = $profile_img->store('images/profiles', 'public');
            $data = File::create([
                "type" => "image/png",
                "size" => 20025,
                "file" => "storage/" . $image_path
            ]);
            $profile_img_data = $data;
        }
        // check if user uploaded a cover image 
        $cover_img = $request->file('cover_img');
        $cover_img_data = null;
        if ($cover_img != null) {
            $image_path = $cover_img->store('images/covers', 'public');
            $data = File::create([
                "type" => "image/png",
                "size" => 20025,
                "file" => "storage/" . $image_path
            ]);
            $cover_img_data = $data;
        }
        // =================================================
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'required|string|min:8',
            'breed_id' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'error' => "invalid data sent",
            ]);
        }
        $profile_img_id = null;
        $cover_img_id = null;
        if ($profile_img_data != null) {
            $profile_img_id = $profile_img_data->id;
        }
        if ($cover_img_data != null) {
            $cover_img_id = $cover_img_data->id;
        }

        $user = User::create(
            [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'profile_img' => $profile_img_id,
                'cover_img' => $cover_img_id,
                'phone' => $request->phone,
                'birthday' => $request->birthday,
                'address' => $request->adress,
                'gender' => $request->gender,
                'breed_id' => $request->breed_id,
            ]
        );
        // ===================================================
        //send token
        $credentials = $request->only('email', 'password');
        //Crean token
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                ], 400);
            }
        } catch (JWTException $e) {
            return $credentials;
            return response()->json([
                'success' => false,
                'message' => 'Could not create token.',
            ], 500);
        }
        //Token created, return with success response and jwt token
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer'
            ]
        ]);

        // ===================================================
    }
    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        //valid credential
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string|min:6|max:50'
        ]);
        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 403);
        }
        //Request is validated
        //Crean token
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'code' => 0,
                    'message' => 'Login credentials are invalid.'
                ], 400);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token.',
            ], 500);
        }
        //Token created, return with success response and jwt token
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'User login successfully',
            'token' => $token
        ]);
    }

    public function profileInfo()
    {
        $user_id = Auth::user()->id;
        $user = DB::select("CALL user_profile_info($user_id) ");

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'error to get data'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'user info retrieved successfully',
            "user" => $user
        ], 200);
    }

    public function setLastLogin()
    {
        $auth_user = JWTAuth::user()->id;
        $user = User::find($auth_user);
        $user->last_login = time();
        if ($user->save()) {
            return response()->json([
                "success" => true,
                "message" => 'last login updated successfully'
            ], 200);
        }
        return response()->json([
            "success" => false,
            "message" => 'error to update user last login'
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $auth_user = JWTAuth::user()->id;
        // Validate input
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|min:6',
        ]);

        // Get the user by ID
        $user = User::findOrFail($auth_user);

        // Check if old password is correct
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                "success" => false,
                "message" => 'The old password is incorrect'
            ], 200);
        }

        // Update the user's password
        $user->password = bcrypt($request->password);
        // $user->password = Hash::make($request->password);
        $user->save();
        // Redirect back to the user's profile page
        return response()->json([
            "success" => true,
            "message" => 'password is updated successfully'
        ], 200);
    }


}