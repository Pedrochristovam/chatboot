<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canais preparados para tempo real (Reverb/Echo)
Broadcast::channel('inbox', function ($user) {
    return $user !== null;
});

Broadcast::channel('conversation.{id}', function ($user, $id) {
    return $user !== null;
});
