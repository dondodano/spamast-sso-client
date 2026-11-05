<?php

namespace Spamast\Sso\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Spamast\Sso\Support\HubUsers;

/**
 * GET /sso/callback?token=… — opening the app from the SPAMAST SSO.
 *
 * The hub sends the browser here with a one-time, 60-second token. The app
 * redeems it server-to-server (POST {hub}/api/sso/verify, authenticated with
 * the client secret) and learns who the person is.
 *
 *  - Existing account: signed in and sent to their own landing page, unless it
 *    is disapproved or deactivated.
 *  - No account yet: a pending one is created from the SSO details (no roles,
 *    not approved). The administrator approves it and assigns roles; until
 *    then the person is told it is waiting.
 */
class SsoCallbackController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $hub = config('sso.hub_url');
        $token = (string) $request->query('token', '');

        if (! $hub || ! config('sso.client_secret')) {
            return $this->fail('Signing in through the SPAMAST SSO is not set up here yet. Please sign in here instead.');
        }
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $this->fail('That sign-in link is not valid. Open this system again from the SPAMAST SSO.');
        }

        try {
            $response = Http::timeout(config('sso.timeout'))
                ->acceptJson()
                ->withToken(config('sso.client_secret'))
                ->post($hub . '/api/sso/verify', ['token' => $token]);
        } catch (\Throwable $e) {
            Log::warning('SSO: hub unreachable', ['message' => $e->getMessage()]);

            return $this->fail('The SPAMAST SSO could not be reached. Please try again, or sign in here.');
        }

        $identity = $response->json('user');
        if (! $response->successful() || $response->json('authenticated') !== true || ! is_array($identity)
            || blank($identity['sub'] ?? null) || blank($identity['email'] ?? null)) {
            Log::info('SSO: token refused by hub', ['status' => $response->status(), 'error' => $response->json('error')]);

            return $this->fail('That sign-in link has expired or was already used. Open this system again from the SPAMAST SSO.');
        }

        $user = HubUsers::find($identity);

        if (! $user) {
            if (HubUsers::isTrashed($identity['email'])) {
                return $this->fail('Your account was removed. Please contact the administrator.');
            }
            if (HubUsers::emailTaken($identity['email'])) {
                // The email's account is tied to another SSO person.
                return $this->fail('This account belongs to a different SSO user. Please contact the administrator.');
            }

            HubUsers::createPending($identity);
            Log::info('SSO: pending account created', ['email' => $identity['email']]);

            return $this->fail('Your account was created and is waiting for approval. The administrator will approve it and assign your role.', 'Account created', 'wait');
        }

        if ($refusal = HubUsers::refusal($user)) {
            return $refusal[0] === 'pending'
                ? $this->fail($refusal[1], 'Waiting for approval', 'wait')
                : $this->fail($refusal[1], 'Cannot sign in');
        }

        HubUsers::link($user, $identity['sub']);

        // The hub checked the school mailbox, as the magic link does.
        if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // Someone else may still be signed in on this browser.
        if (Auth::check() && Auth::id() !== $user->id) {
            Auth::logout();
            $request->session()->invalidate();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(method_exists($user, 'getPanelRedirect') ? $user->getPanelRedirect() : config('sso.redirect_after_login'));
    }

    /** Shows why, on a page of its own (sign-in pages often do not show flashed messages). */
    private function fail(string $message, string $title = 'Could not sign in', string $tone = 'error'): Response
    {
        $loginUrl = Route::has(config('sso.login_route')) ? route(config('sso.login_route')) : config('sso.login_url');
        $appName = config('sso.app_name');

        return response()->view('sso::notice', compact('message', 'title', 'tone', 'loginUrl', 'appName'), $tone === 'wait' ? 200 : 403);
    }
}
