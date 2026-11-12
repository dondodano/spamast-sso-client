<?php

use Illuminate\Support\Facades\Route;
use Spamast\Sso\Http\Middleware\AuthenticateHub;

/*
| Hub API for the mobile app. Only the hub calls these, with SSO_HUB_TOKEN,
| naming the person by sub and email. See AuthenticateHub.
*/
$api = config('sso.routes.hub_api');
$controller = $api['controller'];

if (class_exists($controller)) {
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
}

// School-wide summary for the SSO executive dashboard. The controller checks the hub token
// itself; no person is named, so AuthenticateHub does not apply.
$summary = $api['summary_controller'] ?? null;
if ($summary && class_exists($summary)) {
    Route::prefix($api['prefix'])
        ->middleware(array_merge($api['middleware'], ['throttle:60,1']))
        ->get('/summary', $summary)
        ->name('hub.summary');
}
