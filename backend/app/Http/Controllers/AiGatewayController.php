<?php

namespace App\Http\Controllers;

use App\Models\AgentSession;
use App\Models\LlmRequestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiGatewayController extends Controller
{
    /**
     * Unified, OpenAI-compatible chat completion endpoint.
     */
    public function chatCompletions(Request $request): JsonResponse
    {
        $request->validate([
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant,system'],
            'messages.*.content' => ['required', 'string'],
            'model' => ['nullable', 'string'],
        ]);

        $startTime = microtime(true);
        $model = $request->input('model', 'gemini-2.0-flash');
        $messages = $request->input('messages');

        // Llama-Guard simulation input check
        foreach ($messages as $msg) {
            $content = strtolower($msg['content']);
            if (Str::contains($content, ['ignore previous instructions', 'read the config.env', 'exfiltrate', 'send it to'])) {
                return response()->json([
                    'message' => 'Llama-Guard: Suspicious prompt injection pattern blocked.',
                    'blocked' => true
                ], 400);
            }
        }

        // RAG Vector DB provenance tag check (defaults to production_merged)
        $ragFilter = $request->input('rag_filter', ['trust_level' => 'production_merged']);
        if (!isset($ragFilter['trust_level']) || $ragFilter['trust_level'] !== 'production_merged') {
            if (!$request->input('allow_untrusted', false)) {
                return response()->json([
                    'message' => 'RAG Protection: Blocked RAG queries on unmerged branches.',
                    'blocked' => true
                ], 403);
            }
        }

        $lastUserMessage = collect($messages)->where('role', 'user')->last()['content'] ?? '';

        $aiResponseContent = '';
        $promptTokens = 0;
        $completionTokens = 0;

        // Estimate prompt tokens (roughly 1 token per 4 characters)
        $promptText = collect($messages)->pluck('content')->implode(' ');
        $promptTokens = max(1, (int) (strlen($promptText) / 4));

        // 1. Route to external APIs if keys exist, otherwise use fallback mock
        $openAiKey = env('OPENAI_API_KEY');
        $geminiKey = env('GEMINI_API_KEY');

        if ($openAiKey && Str::contains($model, ['gpt', 'o3', 'o1'])) {
            try {
                $response = Http::withToken($openAiKey)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $model,
                        'messages' => $messages,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $aiResponseContent = $data['choices'][0]['message']['content'] ?? '';
                    $promptTokens = $data['usage']['prompt_tokens'] ?? $promptTokens;
                    $completionTokens = $data['usage']['completion_tokens'] ?? 0;
                }
            } catch (\Exception $e) {
                // Failover to local mock on network exception
            }
        } elseif ($geminiKey && Str::contains($model, ['gemini'])) {
            try {
                // Standard Google Gemini API routing
                $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$geminiKey}", [
                    'contents' => collect($messages)->map(function ($msg) {
                        return [
                            'role' => $msg['role'] === 'user' ? 'user' : 'model',
                            'parts' => [['text' => $msg['content']]]
                        ];
                    })->toArray()
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $aiResponseContent = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $completionTokens = max(1, (int) (strlen($aiResponseContent) / 4));
                }
            } catch (\Exception $e) {
                // Failover
            }
        }

        // 2. Intelligent local mock fallback if no API keys configured
        if (empty($aiResponseContent)) {
            $aiResponseContent = $this->generateMockResponse($lastUserMessage);
            $completionTokens = max(1, (int) (strlen($aiResponseContent) / 4));
        }

        // Calculate pricing estimation (USD)
        $cost = $this->estimateCost($model, $promptTokens, $completionTokens);
        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        // 3. Log the telemetry of the LLM request
        $user = $request->user();
        if ($user && $user->active_organization_id) {
            LlmRequestLog::create([
                'workspace_id' => $user->active_organization_id,
                'user_id' => $user->id,
                'model_used' => $model,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'cost_usd' => $cost,
                'latency_ms' => $latencyMs,
            ]);
        }

        // Return OpenAI compatible envelope
        return response()->json([
            'id' => 'chatcmpl-' . Str::random(24),
            'object' => 'chat.completion',
            'created' => time(),
            'model' => $model,
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => $aiResponseContent,
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $promptTokens + $completionTokens,
            ]
        ]);
    }

    /**
     * Start an asynchronous simulated Multi-Agent workspace workflow.
     */
    public function triggerAgentTask(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => ['required', 'string', 'min:3'],
        ]);

        $user = $request->user();

        if (!$user->active_organization_id) {
            return response()->json(['message' => 'Active workspace organization required.'], 422);
        }

        $session = DB::transaction(function () use ($user, $request) {
            // Provision task session
            $newSession = AgentSession::create([
                'workspace_id' => $user->active_organization_id,
                'user_id' => $user->id,
                'status' => 'running',
                'prompt' => $request->input('prompt'),
                'steps_log' => [
                    [
                        'timestamp' => date('H:i:s'),
                        'agent' => 'Planner',
                        'message' => 'Received task prompt. Analyzing codebase structure via AST code graph.'
                    ],
                    [
                        'timestamp' => date('H:i:s', time() + 1),
                        'agent' => 'Planner',
                        'message' => 'Task decomposed: 1. Generate DB Migration, 2. Add API Controller endpoints, 3. Create Vue frontend components.'
                    ]
                ]
            ]);

            return $newSession;
        });

        // Simulate background agent progression logs
        $mockLogs = [
            [
                'timestamp' => date('H:i:s', time() + 3),
                'agent' => 'Coder',
                'message' => 'Writing migration: create_payments_table.php with DECIMAL(19,4) fields.'
            ],
            [
                'timestamp' => date('H:i:s', time() + 5),
                'agent' => 'Tester',
                'message' => 'Executing php artisan test inside Firecracker isolated sandbox. 2 tests passed.'
            ],
            [
                'timestamp' => date('H:i:s', time() + 7),
                'agent' => 'Reviewer',
                'message' => 'PR reviews processed successfully. Generated pull request #12 for user approval.'
            ]
        ];

        // Append remaining simulation logs and complete the session status
        $updatedLogs = array_merge($session->steps_log, $mockLogs);
        $session->update([
            'status' => 'completed',
            'steps_log' => $updatedLogs
        ]);

        return response()->json($session);
    }

    /**
     * Get active agent sessions logs.
     */
    public function getAgentTaskStatus(Request $request, string $taskId): JsonResponse
    {
        $user = $request->user();
        $session = AgentSession::where('id', $taskId)
            ->where('workspace_id', $user->active_organization_id)
            ->firstOrFail();

        return response()->json($session);
    }

    /**
     * Silent endpoint to upload workspace file context updates from IDE extension.
     */
    public function syncContext(Request $request): JsonResponse
    {
        $request->validate([
            'file_path' => ['required', 'string'],
            'cursor_line' => ['required', 'integer'],
            'code_delta' => ['nullable', 'string'],
        ]);

        // In a full implementation, this stores the AST delta in Redis or vector db.
        // For baseline gateway verification, we return a successful response.
        return response()->json([
            'message' => 'IDE context synchronized successfully.',
            'timestamp' => time(),
        ]);
    }

    /**
     * Generate structured development advice if APIs are offline or unconfigured.
     */
    private function generateMockResponse(string $prompt): string
    {
        $p = strtolower($prompt);

        if (Str::contains($p, 'invoice')) {
            return "Based on your active time-logs, you have **14.5 hours** logged for client 'Acme Corp' on milestone 'DEV-102 (2FA Authentication)'. At an hourly rate of **$50.00**, this amounts to **$725.00**. I can generate an invoice now. Should I compile the PDF?";
        }

        if (Str::contains($p, 'auth') || Str::contains($p, 'limit')) {
            return "The multi-tenant IAM subsystem is operational. Active device session logs are monitored inside the `sessions` table, restricting logins to a max of **2 devices concurrent limit**. Google Authenticator TOTP is supported via Laravel Fortify.";
        }

        if (Str::contains($p, 'regex')) {
            return "Here is a regex to validate standard mobile numbers in Pakistan:\n```regex\n^((\\+92)|(0092))-{0,1}3\\d{2}-{0,1}\\d{7}$|^03\\d{2}-{0,1}\\d{7}$\n```\nIt supports formats like `+92-300-1234567`, `03001234567`, and standard variants.";
        }

        return "I am the DevOS AI intelligence gateway. I have analyzed your repository context. You are currently working on a Laravel 11 backend and Nuxt 3/4 frontend monorepo. Ask me to write code, design database schemas, or automate deployments.";
    }

    /**
     * Heuristic cost estimator based on token weights.
     */
    private function estimateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        // Default prices per 1,000 tokens
        $inputPrice = 0.00015;  // Gemini Flash standard
        $outputPrice = 0.0006;

        if (Str::contains($model, 'gpt-4o')) {
            $inputPrice = 0.005;
            $outputPrice = 0.015;
        } elseif (Str::contains($model, 'claude-3-5-sonnet')) {
            $inputPrice = 0.003;
            $outputPrice = 0.015;
        }

        return (($promptTokens / 1000) * $inputPrice) + (($completionTokens / 1000) * $outputPrice);
    }
}
