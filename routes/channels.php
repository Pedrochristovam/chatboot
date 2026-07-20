<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Infrastructure\Persistence\Eloquent\Models\Conversation;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canais preparados para tempo real (Reverb/Echo)
Broadcast::channel('inbox', function ($user) {
    return $user !== null
        && Gate::forUser($user)->allows('viewAny', Conversation::class);
});

Broadcast::channel('conversation.{id}', function ($user, $id) {
    $conversation = Conversation::query()->find($id);

    return $conversation !== null
        && Gate::forUser($user)->allows('view', $conversation);
});
