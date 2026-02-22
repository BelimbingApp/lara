<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Modules.Core.User.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
