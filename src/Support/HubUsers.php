<?php

namespace Spamast\Sso\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Finds — or, for a first visit from the SPAMAST SSO, creates — the account for
 * a person the hub vouches for.
 *
 * Matching: the hub id remembered from an earlier visit, then the school
 * email. An account already tied to another hub person is never taken over.
 *
 * A created account is pending: no roles and not approved. It cannot open any
 * panel until an administrator approves it and assigns its roles, exactly like
 * an account the administrator made by hand.
 */
class HubUsers
{
    /** @return class-string<Model> */
    public static function model(): string
    {
        return config('sso.user_model');
    }

    /**
     * @param  array{sub?: ?string, email?: ?string}  $identity
     */
    public static function find(array $identity): ?Model
    {
        $model = static::model();

        $sub = trim((string) ($identity['sub'] ?? ''));
        if ($sub !== '' && ($user = $model::where('sso_sub', $sub)->first())) {
            return $user;
        }

        $email = strtolower(trim((string) ($identity['email'] ?? '')));
        $user = $email !== '' ? $model::whereRaw('LOWER(email) = ?', [$email])->first() : null;

        if ($user && $sub !== '' && $user->sso_sub !== null && $user->sso_sub !== $sub) {
            return null;
        }

        return $user;
    }

    /** True when an account (trashed or not) other than a hub match already uses this email. */
    public static function emailTaken(string $email): bool
    {
        return static::model()::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->exists();
    }

    /** True when the email belongs to a deleted (trashed) account, which only an administrator can restore. */
    public static function isTrashed(?string $email): bool
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' && static::model()::onlyTrashed()->whereRaw('LOWER(email) = ?', [$email])->exists();
    }

    /**
     * A pending account from the hub's verified details: no roles, not approved.
     *
     * @param  array{sub: string, email: string, first_name?: ?string, last_name?: ?string, name?: ?string}  $identity
     */
    public static function createPending(array $identity): Model
    {
        $words = preg_split('/\s+/', trim((string) ($identity['name'] ?? ''))) ?: [];
        $first = trim((string) ($identity['first_name'] ?? '')) ?: ($words[0] ?? '');
        $last  = trim((string) ($identity['last_name'] ?? '')) ?: (count($words) > 1 ? (string) end($words) : '');

        $user = static::model()::create([
            'first_name' => $first !== '' ? $first : strtok($identity['email'], '@'),
            'last_name'  => $last,
            'email'      => strtolower(trim($identity['email'])),
            'username'   => static::freeUsername($identity['email']),
            // Signing in is through the SSO; nobody knows this password.
            'password'   => Hash::make(Str::random(48)),
            'is_admin'   => false,
            'is_active'  => true,
        ]);

        $user->forceFill([
            'sso_sub'           => $identity['sub'],
            // The SSO verified the school mailbox.
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    /** The email's name part ("fe.canoy"), with a number added if someone already has it. */
    protected static function freeUsername(string $email): string
    {
        $model = static::model();
        $base = Str::of(strtok(strtolower(trim($email)), '@'))->replaceMatches('/[^a-z0-9._-]/', '')->limit(40, '')->value() ?: 'user';
        $name = $base;
        for ($i = 2; $model::withTrashed()->where('username', $name)->exists(); $i++) {
            $name = $base . $i;
        }

        return $name;
    }

    /** Remembers the hub id on the account (first visit only). */
    public static function link(Model $user, ?string $sub): void
    {
        if ($sub && $user->sso_sub === null) {
            $user->forceFill(['sso_sub' => $sub])->saveQuietly();
        }
    }

    /**
     * Why this account may not sign in yet, or null when it may.
     *
     * @return array{0: string, 1: string}|null  [code, message]
     */
    public static function refusal(Model $user): ?array
    {
        if ($user->isDisapproved()) {
            return ['disapproved', 'Your account was not approved. Please contact the administrator.'];
        }
        if (! $user->is_active) {
            return ['inactive', 'Your account has been deactivated. Please contact the administrator.'];
        }
        // Pending = not approved and no role yet (an account the SSO just made). Accounts an
        // administrator set up with roles sign in as they do with a password, approved or not.
        if (! $user->isApproved() && ! $user->isAdministrator() && $user->getRoleSlugs() === []) {
            return ['pending', 'Your account is waiting for approval. The administrator will approve it and assign your role.'];
        }

        return null;
    }
}
