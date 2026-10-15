<?php

namespace App\Services\AxisIq;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the Anthropic Messages API (tool-calling enabled).
 * No SDK dependency — a handful of HTTP calls is all this needs.
 */
class ClaudeClient
{
    public function send(array $messages, array $tools, string $system): array
    {
        $config = config('axis_iq.anthropic');

        if (! $config['api_key']) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $config['api_key'],
            'anthropic-version' => $config['version'],
        ])
            ->baseUrl($config['base_url'])
            ->timeout(30)
            ->post('/messages', [
                'model' => $config['model'],
                'max_tokens' => $config['max_tokens'],
                'system' => $system,
                'messages' => $messages,
                'tools' => $tools,
            ]);

        $response->throw();

        return $response->json();
    }
}
