<?php

namespace App\Services\AxisIq;

use InvalidArgumentException;
use Throwable;

/**
 * The tool-calling loop described in AGENT.md: pick tool(s) for the
 * product the query came from, call its connector, feed results back to
 * Claude, repeat until it has a final natural-language answer.
 */
class QueryOrchestrator
{
    public function __construct(
        private ClaudeClient $claude,
        private ProductConnector $connector,
    ) {}

    /**
     * @return array{answer: string, tools_called: array<int, string>}
     */
    public function ask(string $product, string $query, ?string $userToken): array
    {
        $config = config("axis_iq.products.{$product}");

        if (! $config) {
            throw new InvalidArgumentException("Unknown product [{$product}].");
        }

        $tools = collect($config['tools'])->map(fn (array $tool) => [
            'name' => $tool['name'],
            'description' => $tool['description'],
            'input_schema' => $tool['parameters'],
        ])->all();

        $system = $this->systemPrompt($config['label']);
        $messages = [['role' => 'user', 'content' => $query]];
        $toolsCalled = [];
        $maxIterations = (int) config('axis_iq.anthropic.max_tool_iterations');

        for ($i = 0; $i < $maxIterations; $i++) {
            $response = $this->claude->send($messages, $tools, $system);
            $content = $response['content'] ?? [];
            $messages[] = ['role' => 'assistant', 'content' => $content];

            $toolUses = collect($content)->where('type', 'tool_use')->values();

            if (($response['stop_reason'] ?? null) !== 'tool_use' || $toolUses->isEmpty()) {
                return [
                    'answer' => $this->extractText($content),
                    'tools_called' => $toolsCalled,
                ];
            }

            $toolResults = [];

            foreach ($toolUses as $toolUse) {
                $toolDef = collect($config['tools'])->firstWhere('name', $toolUse['name']);
                $toolsCalled[] = $toolUse['name'];
                $auth = $config['auth'] ?? ['type' => 'forward_user_token'];
                $result = $toolDef
                    ? $this->callTool($config['base_url'], $toolDef, $toolUse['input'] ?? [], $auth, $userToken)
                    : ['error' => "Unknown tool [{$toolUse['name']}]"];

                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $toolUse['id'],
                    'content' => json_encode($result),
                ];
            }

            $messages[] = ['role' => 'user', 'content' => $toolResults];
        }

        return [
            'answer' => "I wasn't able to finish answering that within the allowed number of steps. Try a more specific question.",
            'tools_called' => $toolsCalled,
        ];
    }

    private function callTool(string $baseUrl, array $toolDef, array $input, array $auth, ?string $userToken): array
    {
        try {
            return $this->connector->call($baseUrl, $toolDef['endpoint'], $input, $auth, $userToken);
        } catch (Throwable $e) {
            return ['error' => 'Failed to fetch data from the product: '.$e->getMessage()];
        }
    }

    private function extractText(array $content): string
    {
        return collect($content)
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");
    }

    private function systemPrompt(string $productLabel): string
    {
        return <<<PROMPT
            You are Axis IQ, a read-only reporting assistant for the AxisOne Suite product "{$productLabel}".

            Rules:
            - Only use the tools provided. They are already scoped to the current user's tenant and permissions — never ask for or assume a broader scope.
            - Never state a number, name, or status that didn't come from a tool result. If no tool can answer the question, say so plainly instead of guessing or estimating.
            - If a tool result includes an "as_of" timestamp, mention how fresh the data is when it's relevant to the answer.
            - Keep answers short: a direct sentence with the number/fact, at most a one-line breakdown. Only produce a list/table if the question implies one.
            - Do not restate the question, and do not add filler like "Great question!".
            - You can only answer about this product. If the question is clearly about a different AxisOne product, say so and name which product it sounds like instead of guessing at data.
            PROMPT;
    }
}
