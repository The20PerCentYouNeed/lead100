<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Agents\LeadAgent;
use App\Enums\ModelName;
use App\Http\Requests\ChatStreamRequest;
use App\Models\Chat;
use Generator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use LarAgent\Messages\StreamedAssistantMessage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class ChatStreamController extends Controller
{
    public function __invoke(ChatStreamRequest $request, Chat $chat): StreamedResponse
    {
        $startTime = microtime(true);
        Log::info("ChatStream: Request started", ['chat_id' => $chat->id, 'start_time' => $startTime]);

        $userMessage = $request->string('message')->trim()->value();
        $model = $request->enum('model', ModelName::class, ModelName::GPT_5_NANO);

        // Save user message to database.
        $chat->messages()->create([
            'role' => 'user',
            'parts' => [
                'text' => $userMessage,
            ],
            'attachments' => '[]',
        ]);

        return Response::stream(function () use ($chat, $userMessage, $model, $startTime): Generator {
            // Extend execution time for long-running agent tool calls.
            set_time_limit(120);

            Log::info("ChatStream: Stream callback started", [
                'elapsed' => round(microtime(true) - $startTime, 2) . 's',
            ]);

            $accumulatedText = '';

            try {
                // Create user-scoped cache key for defense in depth.
                $sessionKey = Auth::id() . '_' . $chat->id;

                Log::info("ChatStream: Creating agent", [
                    'elapsed' => round(microtime(true) - $startTime, 2) . 's',
                ]);

                // Create agent instance with provider first, then override model.
                $agent = LeadAgent::for($sessionKey)
                    ->setProvider($model->getProvider())
                    ->withModel($model->getModelName());

                Log::info("ChatStream: Agent created, starting respondStreamed", [
                    'elapsed' => round(microtime(true) - $startTime, 2) . 's',
                ]);

                // Stream the response.
                $stream = $agent->respondStreamed($userMessage);

                Log::info("ChatStream: respondStreamed returned generator", [
                    'elapsed' => round(microtime(true) - $startTime, 2) . 's',
                ]);

                $chunkCount = 0;
                foreach ($stream as $chunk) {
                    $chunkCount++;
                    if ($chunkCount === 1) {
                        Log::info("ChatStream: First chunk received!", [
                            'elapsed' => round(microtime(true) - $startTime, 2) . 's',
                            'chunk_type' => get_class($chunk),
                        ]);
                    }

                    if ($chunk instanceof StreamedAssistantMessage) {
                        $delta = $chunk->getLastChunk();

                        if ($delta) {
                            $accumulatedText .= $delta;

                            // Adapt LarAgent format to match frontend expectations.
                            yield json_encode([
                                'eventType' => 'text_delta',
                                'content' => $delta,
                            ]) . "\n";
                        }

                        // When stream is complete, save to database.
                        if ($chunk->isComplete() && $accumulatedText !== '') {
                            $chat->messages()->create([
                                'role' => 'assistant',
                                'parts' => [
                                    'text' => $accumulatedText,
                                ],
                                'attachments' => '[]',
                            ]);
                            $chat->touch();
                        }
                    }
                }

                // Fallback: Save if we have text but didn't catch completion.
                if ($accumulatedText !== '' && !$chat->messages()->where('role', 'assistant')->latest()->first()) {
                    $chat->messages()->create([
                        'role' => 'assistant',
                        'parts' => [
                            'text' => $accumulatedText,
                        ],
                        'attachments' => '[]',
                    ]);
                    $chat->touch();
                }

                Log::info("ChatStream: Stream completed", [
                    'elapsed' => round(microtime(true) - $startTime, 2) . 's',
                    'total_chunks' => $chunkCount,
                ]);
            }
            catch (Throwable $throwable) {
                Log::error("Chat stream error for chat {$chat->id}: {$throwable->getMessage()}", [
                    'exception' => $throwable,
                    'elapsed' => round(microtime(true) - $startTime, 2) . 's',
                ]);

                            yield json_encode([
                                'eventType' => 'error',
                                'content' => 'Stream failed: ' . $throwable->getMessage(),
                            ]) . "\n";
            }
        });
    }
}
