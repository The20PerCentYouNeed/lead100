<?php

declare(strict_types=1);

namespace App\Enums;

enum ModelName: string
{
    case GPT_5_MINI = 'gpt-5-mini';
    case GPT_5_NANO = 'gpt-5-nano';

    case GPT_4O = 'gpt-4o';
    case GPT_4O_MINI = 'gpt-4o-mini';
    case O1_MINI = 'o1-mini';
    case O1_PREVIEW = 'o1-preview';

    case CLAUDE_3_7_SONNET = 'claude-3-7-sonnet-latest';
    case CLAUDE_3_5_SONNET = 'claude-3-5-sonnet-latest';
    case CLAUDE_3_OPUS = 'claude-3-opus-latest';

    /**
     * @return array{id: string, name: string, description: string, provider: string}[]
     */
    public static function getAvailableModels(): array
    {
        return array_map(
            fn (ModelName $model): array => $model->toArray(),
            self::cases()
        );
    }

    public function getName(): string
    {
        return match ($this) {
            self::GPT_5_MINI => 'GPT-5 mini',
            self::GPT_5_NANO => 'GPT-5 Nano',
            self::GPT_4O => 'GPT-4o',
            self::GPT_4O_MINI => 'GPT-4o Mini',
            self::O1_MINI => 'O1 Mini',
            self::O1_PREVIEW => 'O1 Preview',
            self::CLAUDE_3_7_SONNET => 'Claude 3.7 Sonnet',
            self::CLAUDE_3_5_SONNET => 'Claude 3.5 Sonnet',
            self::CLAUDE_3_OPUS => 'Claude 3 Opus',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::GPT_5_MINI => 'Cheapest model, best for smarter tasks',
            self::GPT_5_NANO => 'Cheapest model, best for simpler tasks',
            self::GPT_4O => 'Best for general purpose tasks',
            self::GPT_4O_MINI => 'Cheapest model, best for simpler tasks',
            self::O1_MINI => 'Best for general purpose tasks',
            self::O1_PREVIEW => 'Best for general purpose tasks',
            self::CLAUDE_3_7_SONNET => 'Latest Claude model, excellent for complex reasoning',
            self::CLAUDE_3_5_SONNET => 'Fast and capable Claude model',
            self::CLAUDE_3_OPUS => 'Most powerful Claude model for complex tasks',
        };
    }

    /**
     * Get the LarAgent provider name for this model
     */
    public function getProvider(): string
    {
        return match ($this) {
            self::CLAUDE_3_7_SONNET, self::CLAUDE_3_5_SONNET, self::CLAUDE_3_OPUS => 'claude',
            default => 'default', // OpenAI provider
        };
    }

    /**
     * Get the actual model name to use with the provider
     */
    public function getModelName(): string
    {
        return match ($this) {
            self::CLAUDE_3_7_SONNET => 'claude-3-7-sonnet-latest',
            self::CLAUDE_3_5_SONNET => 'claude-3-5-sonnet-latest',
            self::CLAUDE_3_OPUS => 'claude-3-opus-latest',
            default => $this->value,
        };
    }

    /**
     * @return array{id: string, name: string, description: string, provider: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->value,
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'provider' => $this->getProvider(),
        ];
    }
}
