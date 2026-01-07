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

        return Response::stream(function () use ($chat, $userMessage, $model): Generator {
            $accumulatedText = '';

            try {
                // Create user-scoped cache key for defense in depth.
                $sessionKey = Auth::id() . '_' . $chat->id;

                // Create agent instance with provider first, then override model.
                $agent = LeadAgent::for($sessionKey)
                    ->setProvider($model->getProvider())
                    ->withModel($model->getModelName());

                // Stream the response.
                $stream = $agent->respondStreamed($userMessage);

                foreach ($stream as $chunk) {
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
            }
            catch (Throwable $throwable) {
                Log::error("Chat stream error for chat {$chat->id}: {$throwable->getMessage()}", [
                    'exception' => $throwable,
                ]);

                yield json_encode([
                    'eventType' => 'error',
                    'content' => 'Stream failed: ' . $throwable->getMessage(),
                ]) . "\n";
            }
        });
    }
}
