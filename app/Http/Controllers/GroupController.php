<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\Group;
use App\Models\GroupsMembers;
use App\Models\File;
use App\Models\User;
use App\Models\AnimalsBreed;
use App\Models\GroupsPosts;
use JWTAuth;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\TimeConverter;
use DB;
use App\Traits\NotificationsTrait;

class GroupController extends TimeConverter
{
    use NotificationsTrait;


    public function index($start = null, $q = null)
    {
        $auth_user = JWTAuth::user()->id;
        if ($q === null)
            $q = '';
        if ($start === null)
            $start = 0;

        $q = trim($q);
        $groups = Group::where('name', 'like', '%' . $q . '%')
            ->orwhere("description", 'like', '%' . $q . '%')
            ->inRandomOrder()->offset($start)->limit(10)->get();
        //check if error exists
        if (!$groups) {
            return response()->json([
                'success' => false,
                'message' => "error to get groups"
            ]);
        }
        foreach ($groups as $group) {
            //get profile img for the group
            if ($group['profile_img'] != null) {
                $group['profile_img'] = File::where("id", $group->profile_img)->first()->file;
            }
            //get cover img for the group
            if ($group['cover_img'] != null) {
                $group['cover_img'] = File::where("id", $group->cover_img)->first()->file;
            }

            //check if user already joined a group
            $groupsMemberExists = GroupsMembers::where("user_id", $auth_user)->where("group_id", $group->id)->get();
            if (count($groupsMemberExists) > 0) {
                //     // user already joined a group
                $group['joined'] = 1;
                if ($groupsMemberExists[0]->accepted === 0)
                    $group['joined'] = 0;
            } else {
                $group['joined'] = -1;
            }

            //get members count
            // ->count();
            $group['members_count'] = GroupsMembers::where("group_id", $group->id)
                ->where('role', '!=', 'admin')
                ->where('accepted', true)
                ->get()->count();

            $group['admins_count'] = GroupsMembers::where("group_id", $group->id)
                ->where('role', 'admin')
                ->get()->count();
        }
        return response()->json([
            'success' => true,
            'message' => "group data retrievrd successfully",
            "groups" => $groups
        ]);
    }
    public function store(Request $request) //create a new group
    {
        //get auth user id
        $auth_user = JWTAuth::user()->id;

        //valide daata sent by user
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'message' => "invalid data sent",
                'error' => true,
            ]);
        }
        //create group [ save in databese ]
        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description
        ]);

        if ($group) { //save user in group members table
            $groupsMembers = GroupsMembers::create(
                [
                    "user_id" => $auth_user,
                    "group_id" => $group->id,
                    "accepted" => true,
                    "role" => 'admin'
                ]
            );
            //if there is error to create group member
            if (!$groupsMembers) {
                return response()->json([
                    'success' => false,
                    'message' => "error to create group",
                    'error' => true,
                ]);
            }
            //if group created successfully
            return response()->json([
                'success' => true,
                'message' => "group created successfully",
                "data" => [
                    "group" => $group,
                    "member" => $groupsMembers
                ]
            ]);
        }
        // if there is error to create group
        return response()->json([
            'success' => false,
            'message' => "error to create group",
            'error' => true,
        ]);
    }
    //join group function
    public function joinGroup($group)
    {
        //get auth user id
        $auth_user = JWTAuth::user()->id;
        //check if user already joined a group
        $groupsMemberExists = GroupsMembers::where("user_id", $auth_user)->where("group_id", $group)->first();
        if ($groupsMemberExists != null) {
            return response()->json([
                'status' => false,
                'message' => "user already joined a group"
            ]);
        }
        //creating a group member
        $groupsMembers = GroupsMembers::create(
            [
                "user_id" => $auth_user,
                "group_id" => $group,
            ]
        );
        //if there is error to create group member
        if (!$groupsMembers) {
            return response()->json([
                'success' => false,
                'message' => "error to join group",
                'error' => true,
            ]);
        }
        //if group created successfully
        return response()->json([
            'success' => true,
            'message' => "group joined successfully"
        ]);
    }
    public function leaveGroup($group)
    {
        //get auth user id
        $auth_user = JWTAuth::user()->id;
        //delete a user from members group
        // $groupsMember = 
        GroupsMembers::where("user_id", $auth_user)->where("group_id", $group)->delete();
        return response()->json([
            'status' => true,
            'message' => "user leaved group successfully"
        ]);

    }
    public function show($id)
    {
        $auth_user = JWTAuth::user()->id;

        $group = Group::find($id);

        //check if error exists
        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => "error to get group"
            ]);
        }
        //get profile img for the group
        if ($group['profile_img'] != null) {
            $group['profile_img'] = File::where("id", $group->profile_img)->first()->file;
        }
        //get cover img for the group
        if ($group['cover_img'] != null) {
            $group['cover_img'] = File::where("id", $group->cover_img)->first()->file;
        }
        //check if user already joined a group
        $groupsMemberExists = GroupsMembers::where("user_id", $auth_user)->where("group_id", $group->id)->get();
        if (count($groupsMemberExists) > 0) {
            //     // user already joined a group
            $group['joined'] = 1;
            if ($groupsMemberExists[0]->accepted === 0)
                $group['joined'] = 0;
        } else {
            $group['joined'] = -1;
        }

        $is_admin = GroupsMembers::where("user_id", $auth_user)
            ->where("group_id", $group->id)
            ->where("role", "admin")
            ->first();
        $group['is_admin'] = $is_admin === null ? false : true;
        //get members count
        $group['members_count'] = GroupsMembers::where("group_id", $id)
            // ->where('role', '!=', 'admin')
            ->where('accepted', true)
            ->get()->count();

        $group['admins_count'] = GroupsMembers::where("group_id", $group->id)
            ->where('role', 'admin')
            ->get()->count();

        $group['posts_count'] = GroupsPosts::where("group_id", $group->id)
            ->where("accepted", true)
            ->count();

        return response()->json([
            'success' => true,
            'message' => "group data retrievrd successfully",
            "group" => $group
        ]);
    }
    public function joinRequests($id)
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
                    "message" => "you have not access to join group requests"
                ]
                ,
                400
            );
        }
        $groupsMembers = GroupsMembers::where('accepted', false)
            ->where("group_id", $id)->get();

        foreach ($groupsMembers as $groupsMember) {
            # code...
            $user = User::where('id', $groupsMember->user_id)->first();
            $groupsMember['user_name'] = $user->name;
            $profile_img = $user->profile_img;
            $groupsMember['profile_img'] = null;
            if ($profile_img != null) {
                $groupsMember['profile_img'] = File::where('id', $user->profile_img)->first()->file;

            } //AnimalsBreed
            $groupsMember['user_breed'] = AnimalsBreed::where('id', $user->breed_id)->first()->name;
            $groupsMember['time'] = TimeConverter::secondsToTime(time() - strtotime($groupsMember->created_at));
        }
        return response()->json(
            [
                "success" => true,
                "message" => "join group requests retrieved successfull",
                "MembersRequests" => $groupsMembers
            ]
            ,
            200
        );

    }
    public function acceptRequest(Request $request, $groupId)
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
                    "message" => "you have not access to accept join group requests"
                ]
                ,
                400
            );
        }
        $groupsMembers = GroupsMembers::where('accepted', false)
            ->where("group_id", $groupId)
            ->where("user_id", $request->user_id)
            ->first();

        $groupsMembers->accepted = true;
        $groupsMembers->save();
        //send notification to user that he accepted on group
        // ===============================================
             $content = [
                 "group_id" => $groupId,
                 "admin_id" => $auth_user,
                 "message" => "you request to join group has been accepted"
             ];
             $notice = $this->createNotification( $request->user_id , $content, "group_accept");
        // ===============================================
        return response()->json([
            "success" => true,
            "data" => $groupsMembers,
            "message" => 'join group request deleted'
        ], 200);

    }
    public function deleteRequest(Request $request, $groupId)
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
                    "message" => "you have not access to delete join group requests"
                ]
                ,
                400
            );
        }
        $groupsMembers = GroupsMembers::where('accepted', false)
            ->where("group_id", $groupId)
            ->where("user_id", $request->user_id)
            ->first();

        $groupsMembers->delete();

        return response()->json([
            "success" => true,
            "message" => 'join group request deleted'
        ], 200);

    }
    public function groupMembers($id)
    {
        //check if user is admin
        $auth_user = JWTAuth::user()->id;
        $groupsMembers = GroupsMembers::where('accepted', true)
            ->where("group_id", $id)
            ->where("user_id", "!=", $auth_user)
            ->get();

        foreach ($groupsMembers as $groupsMember) {
            # code...
            $user = User::where('id', $groupsMember->user_id)->first();
            $groupsMember['user_name'] = $user->name;
            $profile_img = $user->profile_img;
            $groupsMember['profile_img'] = null;
            if ($profile_img != null) {
                $groupsMember['profile_img'] = File::where('id', $user->profile_img)->first()->file;

            } //AnimalsBreed
            $groupsMember['user_breed'] = AnimalsBreed::where('id', $user->breed_id)->first()->name;
            $groupsMember['time'] = TimeConverter::secondsToTime(time() - strtotime($groupsMember->updated_at));

            $friend = Friend::
                where("request_from", $user->id)
                ->where("request_to", $auth_user)
                ->orwhere("request_to", $user->id)
                ->where("request_from", $auth_user)

                ->first();

            if ($friend === null)
                $groupsMember['friend'] = -1;
            else
                $groupsMember['friend'] = $friend->status;
        }
        return response()->json(
            [
                "success" => true,
                "message" => "join group requests retrieved successfull",
                "groupMembers" => $groupsMembers
            ]
            ,
            200
        );

    }
    public function groupAdmins($id)
    {
        //check if user is admin
        $auth_user = JWTAuth::user()->id;
        $groupAdmins = GroupsMembers::where('accepted', true)
            ->where("group_id", $id)
            ->where("user_id", "!=", $auth_user)
            ->where("role", "admin")
            ->get();

        foreach ($groupAdmins as $groupAdmin) {
            # code...
            $user = User::where('id', $groupAdmin->user_id)->first();
            $groupAdmin['user_name'] = $user->name;
            $profile_img = $user->profile_img;
            $groupAdmin['profile_img'] = null;
            if ($profile_img != null) {
                $groupAdmin['profile_img'] = File::where('id', $user->profile_img)->first()->file;

            } //AnimalsBreed
            $groupAdmin['user_breed'] = AnimalsBreed::where('id', $user->breed_id)->first()->name;
            $groupAdmin['time'] = TimeConverter::secondsToTime(time() - strtotime($groupAdmin->updated_at));

            $friend = Friend::
                where("request_from", $user->id)
                ->where("request_to", $auth_user)

                ->orwhere("request_to", $user->id)
                ->where("request_from", $auth_user)

                ->first();

            if ($friend === null)
                $groupAdmin['friend'] = -1;
            else
                $groupAdmin['friend'] = $friend->status;
        }
        return response()->json(
            [
                "success" => true,
                "message" => "all group admins retrieved successfull",
                "groupAdmins" => $groupAdmins
            ]
            ,
            200
        );

    }

    public function removeMember(Request $request, $id)
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
        $groupsMembers = GroupsMembers::where('accepted', true)
            ->where("group_id", $id)
            ->where("user_id", $request->user_id)
            ->first();

        $groupsMembers->delete();

        return response()->json([
            "success" => true,
            "message" => 'member deleted successfutty'
        ], 200);

    }
    public function joinGroupReqCount($id)
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
        $groupsMembersCount = GroupsMembers::where('accepted', false)
            ->where("group_id", $id)
            ->count();
        return response()->json(
            [
                "success" => true,
                "count" => $groupsMembersCount,
                "message" => "group requests count reatreaved successfully"
            ]
            ,
            200
        );
    }
    public function update(Request $request, Group $group)
    {
        //
    }
    public function destroy(Group $group)
    {
        //
    }

    public function userGroups($userId, $start = null, $q = null)
    {
        $auth_user = JWTAuth::user()->id;
        if ($q === null)
            $q = '';
        if ($start === null)
            $start = 0;

        $q = trim($q);
        // $groups = DB::table('groups')
        //     ->select([
        //         'groups.id',
        //         'groups.name',
        //         'groups.profile_img',
        //         'groups.cover_img',
        //         'groups.description',
        //         'groups.created_at',
        //         'groups.updated_at',
        //     ])
        //     ->join('groups_members', function ($join) use ($userId) {
        //         $join->on('groups.id', '=', 'groups_members.group_id')
        //             ->where('groups_members.user_id', '=', $userId);
        //     })
        //     ->where(function ($query) use ($q) {
        //         $query->where('groups.name', 'like', '%' . $q . '%')
        //             ->orWhere('groups.description', 'like', '%' . $q . '%');
        //     })
        //     ->inRandomOrder()
        //     ->limit(10)
        //     ->get()->toArray();
        $groups = DB::select("select 
                                groups.id,
                                groups.name,
                                groups.profile_img	,
                                groups.cover_img	,
                                groups.description	,
                                groups.created_at	,
                                groups.updated_at
                                from 
                                groups
                                INNER JOIN groups_members ON(
                                    groups.id = groups_members.group_id 
                                    AND 
                                    groups_members.user_id = $userId 
                                    AND groups_members.accepted=true
                                )
                                WHERE
                                (groups.name LIKE '%$q%' OR groups.description LIKE '%$q%')
                                ORDER BY
                                    RAND()
                                LIMIT 
                                    $start, 10
                                ");
        // $groups = DB::table('groups')
        //         ->select(
        //             'groups.name',
        //             'groups.profile_img',
        //             'groups.cover_img',
        //             'groups.description',
        //             'groups.created_at',
        //             'groups.updated_at',
        //         )
        //         ->join('groups_members', 'groups.id' , '=' , 'groups_members.group_id' )

        //         ->where('groups_members.accepted', true)
        //         ->where('groups_members.user_id', $userId)

        //         ->where('groups.name' , 'LIKE' , "%$q%")
        //         ->orwhere('groups.description' , 'LIKE' , "%$q%")
        //         ->get()
        //         ->toArray();
        //check if error exists
        if (!$groups) {
            return response()->json([
                'success' => false,
                'message' => "error to get groups"
            ]);
        }
        foreach ($groups as $group) {
            //get profile img for the group
            if ($group->profile_img != null) {
                $group->profile_img = File::where("id", $group->profile_img)->first()->file;
            }
            //get cover img for the group
            if ($group->cover_img != null) {
                $group->cover_img = File::where("id", $group->cover_img)->first()->file;
            }
            //check if user already joined a group
            $groupsMemberExists = GroupsMembers::where("user_id", $auth_user)->where("group_id", $group->id)->get();
            if (count($groupsMemberExists) > 0) {
                //     // user already joined a group
                $group->joined = 1;
                if ($groupsMemberExists[0]->accepted === 0)
                    $group->joined = 0;
            } else {
                $group->joined = -1;
            }

            //get members count
            // ->count();
            $group->members_count = GroupsMembers::where("group_id", $group->id)
                ->where('role', '!=', 'admin')
                ->where('accepted', true)
                ->get()->count();

            $group->admins_count = GroupsMembers::where("group_id", $group->id)
                ->where('role', 'admin')
                ->get()->count();
        }
        return response()->json([
            'success' => true,
            'message' => "group data retrievrd successfully",
            "groups" => $groups
        ]);
    }

} //END CLASS