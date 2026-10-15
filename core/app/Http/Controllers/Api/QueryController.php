<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IqConversation;
use App\Services\AxisIq\QueryOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class QueryController extends Controller
{
    public function __invoke(Request $request, QueryOrchestrator $orchestrator): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:1000'],
            'product' => ['required', 'string', 'in:'.implode(',', array_keys(config('axis_iq.products')))],
        ]);

        $authToken = $request->bearerToken();

        try {
            $result = $orchestrator->ask($validated['product'], $validated['query'], $authToken);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        IqConversation::create([
            'product' => $validated['product'],
            'query' => $validated['query'],
            'answer' => $result['answer'],
            'tools_called' => $result['tools_called'],
            // Never store the raw auth token — just enough of a fingerprint for audit/debug.
            'user_fingerprint' => $authToken ? substr(hash('sha256', $authToken), 0, 16) : null,
        ]);

        return response()->json([
            'answer' => $result['answer'],
            'tools_called' => $result['tools_called'],
        ]);
    }
}
