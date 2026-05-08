<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('reverb.{uuid}', function ($user, string $uuid) {
    return in_array($uuid, session()->get('reverb_uuids', []), true);
});
