<?php

namespace App\Services\AxisIq;

use Illuminate\Support\Facades\Http;

/**
 * Calls a single product's read-only /api/iq/* endpoint. Axis IQ Core
 * never touches a product's database directly.
 *
 * Auth differs per product (see each connector's doc):
 * - 'forward_user_token' (default): forward the end user's own token, so
 *   the product enforces its normal per-user/tenant authorization.
 * - 'shared_token': use a fixed service token configured in Axis IQ
 *   Core's own .env, for products (e.g. Field/AxisField nosaas) that have
 *   no per-user auth on their connector. The end user's token is never
 *   used or forwarded in that case.
 */
class ProductConnector
{
    public function call(string $baseUrl, array $endpoint, array $params, array $auth, ?string $userToken): array
    {
        $request = Http::baseUrl(rtrim($baseUrl, '/'))->acceptJson()->timeout(10);

        $token = match ($auth['type'] ?? 'forward_user_token') {
            'shared_token' => $auth['token'] ?? null,
            default => $userToken,
        };

        if ($token) {
            $request = $request->withToken($token);
        }

        $method = strtolower($endpoint['method'] ?? 'get');
        $path = $endpoint['path'];

        $response = $request->{$method}($path, $params);

        $response->throw();

        return $response->json() ?? [];
    }
}
