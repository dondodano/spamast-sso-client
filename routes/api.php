<?php

use Illuminate\Support\Facades\Route;
use Spamast\Sso\Http\Middleware\AuthenticateHub;

/*
| Hub API for the mobile app. Only the hub calls these, with SSO_HUB_TOKEN,
| naming the person by sub and email. See AuthenticateHub.
*/
$api = config('sso.routes.hub_api');
$controller = $api['controller'];

Route::prefix($api['prefix'])
    ->middleware(array_merge([AuthenticateHub::class], $api['middleware']))
    ->group(function () use ($controller, $api) {
        Route::get('/me', [$controller, 'me']);
        Route::get('/documents', [$controller, 'documents']);
        Route::get('/documents/{type}/{id}', [$controller, 'show'])
            ->whereIn('type', $api['types'])->whereNumber('id');
        Route::post('/documents/{type}/{id}/{action}', [$controller, 'act'])
            ->whereIn('type', $api['types'])->whereNumber('id')->whereIn('action', $api['actions']);
        Route::get('/notifications', [$controller, 'notifications']);
        Route::post('/notifications/read', [$controller, 'markRead']);
    });
