<?php

use App\Models\User;

// Keep the PHP 8.2 support declared by the project compatible with dependencies
// that use the array helpers introduced in PHP 8.4.
if (! function_exists('array_all')) {
    function array_all(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if (! $callback($value, $key)) {
                return false;
            }
        }

        return true;
    }
}

if (! function_exists('array_any')) {
    function array_any(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('array_find')) {
    function array_find(array $array, callable $callback): mixed
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }
}

if (! function_exists('array_find_key')) {
    function array_find_key(array $array, callable $callback): int|string|null
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $key;
            }
        }

        return null;
    }
}

if (! function_exists('tms_user')) {
    function tms_user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
