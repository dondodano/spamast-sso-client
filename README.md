# spamast/laravel-sso-client

SPAMAST SSO client for Laravel: sign in from the hub, pending accounts for first-time visitors, and a hub API for the SSO mobile app.

## Install

In the app's `composer.json`:

```json
"repositories": [{ "type": "path", "url": "packages/spamast-sso" }],
"require": { "spamast/laravel-sso-client": "*" }
```

```bash
composer update spamast/laravel-sso-client
php artisan migrate
```

The service provider is auto-discovered. Then remove the app's own copies (they are now in the package):
`app/Http/Controllers/Sso/SsoCallbackController.php`, `app/Http/Middleware/AuthenticateHub.php`,
`app/Support/Sso/HubUsers.php`, `routes/sso.php`, `routes/sso-api.php` (and their entries in `bootstrap/app.php`),
`resources/views/sso/notice.blade.php`, `config/sso.php`.
Keep `HubApiController` (see below).

## Configure (.env)

```
SSO_HUB_URL=            # empty = SSO off
SSO_CLIENT_SECRET=
SSO_HUB_TOKEN=
SSO_USER_MODEL=App\Models\User
SSO_LOGIN_ROUTE=filament.app.auth.login
SSO_APP_NAME=PGSMS
```

`php artisan vendor:publish --tag=sso-config` for the rest (routes, URIs, middleware, redirect fallback).
Other tags: `sso-views`, `sso-migrations`, `sso-stubs`.

## What the user model must provide

- A nullable unique `sso_sub` column (migration included; skipped if it already exists).
- `isApproved()`, `isDisapproved()`, `isAdministrator()`, `getRoleSlugs(): array`, and the `is_active` attribute.
- Optional: `getPanelRedirect()` (otherwise `sso.redirect_after_login` is used), `SoftDeletes`.
- Mass-assignable `first_name, last_name, email, username, password, is_admin, is_active` (used when creating a pending account).

## Hub API (mobile app)

`/api/hub/*` is guarded by `AuthenticateHub`. What it serves is app-specific, so the package ships the
PGSMS controller as a stub rather than as package code:

```bash
php artisan vendor:publish --tag=sso-stubs   # -> app/Http/Controllers/Sso/HubApiController.php
```

Point `sso.routes.hub_api.controller` at your own class instead if you prefer. Routes are registered only when
that class exists. Adjust `types` and `actions` in the config to match.
