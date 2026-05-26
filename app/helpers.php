<?php

use App\Models\User;

if (! function_exists('tms_user')) {
    function tms_user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
