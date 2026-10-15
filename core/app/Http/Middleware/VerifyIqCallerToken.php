<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the CALLER of Axis IQ Core (e.g. a product's own backend
 * proxy), not the end user. Without this, a shared-token product (one
 * where Core itself holds the downstream secret — see ProductConnector)
 * would answer real queries for anyone who finds Core's URL, since Core
 * never forwards a user-specific credential downstream for it to reject.
 *
 * The `Authorization: Bearer <token>` header is reserved for this caller
 * check. It is NOT the end user's own product session/token — a
 * forward_user_token product passes that separately, in the request body
 * (see QueryController), precisely so the two concerns don't collide.
 */
class VerifyIqCallerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('axis_iq.caller_token');
        $given = $request->bearerToken();

        if (! $expected || ! $given || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
