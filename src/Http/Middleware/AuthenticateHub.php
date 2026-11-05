<?php

namespace Spamast\Sso\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spamast\Sso\Support\HubUsers;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the hub API (/api/hub/*), which only the SPAMAST SSO hub calls, on
 * behalf of someone signed in to the SPAMAST SSO mobile app.
 *
 * The hub proves itself with the shared SSO_HUB_TOKEN and names the person
 * (sub, email). That person needs an account that may sign in; for the rest of
 * the request they are the signed-in user, so the app's own policies and
 * notification events see the right person.
 */
class AuthenticateHub
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('sso.hub_token');
        if (! $secret) {
            return response()->json(['error' => 'not_configured', 'message' => 'This system is not connected to the SPAMAST SSO.'], 503);
        }

        $presented = $request->bearerToken();
        if (! is_string($presented) || ! hash_equals($secret, $presented)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $sub = $request->input('sub');
        $user = HubUsers::find(['sub' => $sub, 'email' => $request->input('email')]);

        if (! $user) {
            return response()->json([
                'error' => 'no_account',
                'message' => 'There is no account for you here yet. Open this system once from the SSO website to request one.',
            ], 404);
        }
        if ($refusal = HubUsers::refusal($user)) {
            return response()->json(['error' => $refusal[0], 'message' => $refusal[1]], 403);
        }

        HubUsers::link($user, $sub);
        Auth::setUser($user);

        return $next($request);
    }
}
