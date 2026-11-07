<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SPAMAST SSO hub
    |--------------------------------------------------------------------------
    | Leaving SSO_HUB_URL empty turns all of this off: the callback refuses and
    | the hub API answers 503, so the app works exactly as before.
    |
    |   hub_url        Where the hub lives; sign-in tokens are redeemed there.
    |   client_secret  The secret the hub issued for this system.
    |   hub_token      Shared secret the hub presents when it calls /api/hub/*.
    */
    'hub_url'       => rtrim((string) env('SSO_HUB_URL', ''), '/'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'hub_token'     => env('SSO_HUB_TOKEN'),

    // Seconds to wait for the hub before giving up.
    'timeout' => (int) env('SSO_TIMEOUT', 8),

    /*
    |--------------------------------------------------------------------------
    | Application
    |--------------------------------------------------------------------------
    */
    // Shown in the title of the notice page.
    'app_name' => env('SSO_APP_NAME', config('app.name', 'Laravel')),

    // Eloquent model of the people who sign in. It must have a nullable, unique `sso_sub`
    // column (see the migration) and the methods listed in the README.
    'user_model' => env('SSO_USER_MODEL', 'App\\Models\\User'),

    // Named route the notice page's button links to, and a URL used if the route does not exist.
    'login_route' => env('SSO_LOGIN_ROUTE', 'login'),
    'login_url'   => '/',

    // Where a signed-in user lands when the model has no getPanelRedirect() method.
    'redirect_after_login' => '/',

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'callback' => [
            'enabled'    => true,
            'uri'        => 'sso/callback',
            'name'       => 'sso.callback',
            'middleware' => ['web', 'throttle:20,1'],
        ],

        // The hub API for the mobile app. The package ships no controller for it, because
        // what it serves is specific to each app: publish the stub
        // (php artisan vendor:publish --tag=sso-stubs) and edit it, or point `controller`
        // at your own class. Routes are skipped while the class does not exist.
        'hub_api' => [
            'enabled'    => true,
            'prefix'     => 'api/hub',
            'middleware' => ['api', 'throttle:120,1'],
            'controller' => 'App\\Http\\Controllers\\Sso\\HubApiController',
            'types'      => ['ppmp', 'pr'],
            'actions'    => ['publish', 'unpublish', 'sign', 'unsign', 'sign_as_preparer', 'unsign_as_preparer', 'sign_as_head', 'unsign_as_head',
                'advance', 'revert_advance', 'review', 'unreview', 'certify', 'uncertify', 'approve', 'disapprove',
                'remark', 'resolve', 'cancel', 'restore'],
        ],
    ],
];
