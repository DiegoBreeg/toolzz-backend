<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Chat Channel
|--------------------------------------------------------------------------
| Authorize users to listen to their own private chat channel.
*/
Broadcast::channel('chat.{receiverId}', function ($user, $receiverId) {
    return (int) $user->id === (int) $receiverId;
});

/*
|--------------------------------------------------------------------------
| Presence Channel for Typing
|--------------------------------------------------------------------------
| Authorize both users to join the same presence channel for whispers.
*/
Broadcast::channel('presence-chat.{userA}.{userB}', function ($user, $userA, $userB) {
    $userId = (int) $user->id;
    if ($userId !== (int) $userA && $userId !== (int) $userB) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
