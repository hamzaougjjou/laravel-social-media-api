<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupsPostsController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\FriendsController;
use App\Http\Controllers\AnimalsBreedsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;

//php artisan serve --host=192.168.137.1
// public urls 
Route::controller(AnimalsBreedsController::class)->group(function () {
    Route::get('animales/breeds', 'index');
});
//auth urls
Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::group(
        ['middleware' => 'auth:api'],
        function () {
            Route::post('logout', 'logout');
            Route::post('login/refresh', 'refresh');
            Route::get('profile/info', 'profileInfo');
            Route::put('login/last/update', 'setLastLogin');
            Route::put('profile/password/change', 'changePassword');
        }
    );
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('friends/request/send', [FriendsController::class, 'sendRequest']);
    Route::get('friends/all', [FriendsController::class, 'allFriends']);
    Route::get('friends/requests/all', [FriendsController::class, 'requests']);
    Route::get('friends/requests/count', [FriendsController::class, 'userRequestsCount']);
    Route::put('friends/requests/{id}/accept', [FriendsController::class, 'acceptRequest']);
    Route::delete('friend/{id}/delete', [FriendsController::class, 'deleteFriend']);
    
    Route::controller(PostsController::class)->group(
        function () {
            Route::get('posts/{postsStart}', 'index');
            Route::post('posts/friends/all', 'friendsPosts');
            Route::post('post/create', 'store');
            Route::get('post/{postId}/likes/count', 'likesCount');
            Route::get('post/{postId}/comments/count', 'commentsCount');
            Route::get('post/{postId}', 'show');
        }
    );
    Route::controller(CommentController::class)->group(
        function () {
            Route::get('post/{postId}/comments', 'index');
            Route::post('post/comment/create', 'store');
            Route::post('comment/{commentId}/replay', 'storeCommentReplay');
            Route::get('comment/{commentId}/replies', 'commentReplies');
        }
    );

    Route::controller(ProfileController::class)->group(
        function () {
            Route::get('profile/posts/{postsStart}', 'profilePosts');
            Route::post('profile/name/change', 'changeProfileName');
            Route::post('profile/profile/change', 'changeProfileCover');
            Route::post('profile/cover/change', 'changeProfileCover');
            Route::get('profile/images/profile', 'getProfileImages');
            Route::get('profile/images/cover', 'getCoverImages');
            Route::get('profile/images/posts', 'getPostsImages');
        }
    );
    Route::controller(UserController::class)->group(
        function () {
            Route::get('user/{userId}/info', 'index');
            Route::get('user/{userId}/posts/{postsStart}', 'userPosts');
            Route::get('user/{userId}/friends', 'friends');
            Route::get('user/{userId}/images/profile', 'getProfileImages');
            Route::get('user/{userId}/images/cover', 'getCoverImages');
            Route::get('user/{userId}/images/posts', 'getPostsImages');
        }
    );

    Route::controller(MessageController::class)->group(
        function () {
            Route::get('messages/conversation/{user_id}', 'conversation');
            Route::post('messages/send/{reciever_id}', 'store');
        }
    );
    Route::controller(SearchController::class)->group(
        function () {
            Route::post('search/users', 'searchGetUsers');
        }
    );
    Route::controller(LikeController::class)->group(
        function () {
            Route::post('post/{postId}/like', 'index');
        }
    );

    Route::controller(GroupController::class)->group(
        function () {
            Route::get('/groups/{start?}/{q?}', 'index');
            Route::get('/user/{userId}/groups/{start?}/{q?}', 'userGroups'); //userGroups($user_id, $start = null, $q = null)
            Route::post('/group/create', 'store');
            Route::post('/group/{group}/join', 'joinGroup');
            Route::post('/group/{group}/leave', 'leaveGroup');
            Route::get('/group/{group}', 'show');
            Route::get('/group/{group}/requests', 'joinRequests');
            Route::put('/group/{group}/request/accept', 'acceptRequest');
            Route::post('/group/{group}/request/delete', 'deleteRequest');
            Route::get('/group/{group}/members', 'groupMembers');
            Route::get('/group/{group}/admins', 'groupAdmins');
            Route::post('/group/{group}/members/remove', 'removeMember');
            Route::get('/group/{group}/requests/count', 'joinGroupReqCount');
        }
    );
    Route::controller(GroupsPostsController::class)->group(
        function () {
            Route::post('/group/{groupId}/posts', 'posts');
            Route::post('/group/{groupId}/posts/requests', 'postsRequests');
            Route::get('/group/{groupId}/posts/requests/count', 'groupPostsReqCount');
            Route::put('/group/{groupId}/post/{postId}/accept', 'acceptPost');
        }
    );
    Route::controller(NotificationController::class)->group(
        function () {
            Route::get('/notifications', 'index');
            Route::put('/notifications/read', 'setNotificationRead');
            Route::delete('/notification/{notificationId}/delete', 'destroy');
        }
    );

});