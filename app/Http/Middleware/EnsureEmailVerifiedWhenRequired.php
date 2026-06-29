<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces email verification only when `app.require_email_verification` is on.
 *
 * Self-hosters keep frictionless signup by default (flag off); the hosted plan
 * sets REQUIRE_EMAIL_VERIFICATION=true (with mail configured) to enforce it.
 */
class EnsureEmailVerifiedWhenRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.require_email_verification')) {
            $user = $request->user();

            if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                abort_if($request->expectsJson(), 409, 'Your email address is not verified.');

                return redirect()->route('verification.notice');
            }
        }

        return $next($request);
    }
}
