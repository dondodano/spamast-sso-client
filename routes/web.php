<?php

use Illuminate\Support\Facades\Route;
use Spamast\Sso\Http\Controllers\SsoCallbackController;

/*
| Not under 'guest': someone already signed in who opens the app from the SSO
| lands here too, and is switched to the hub's person if it is someone else.
*/
$callback = config('sso.routes.callback');

Route::middleware($callback['middleware'])
    ->get($callback['uri'], SsoCallbackController::class)
    ->name($callback['name']);
