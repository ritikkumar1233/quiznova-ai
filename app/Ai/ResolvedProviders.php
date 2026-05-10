<?php

namespace App\Ai;

use Laravel\Ai\Enums\Lab;

class ResolvedProviders
{
    /**
     * Return only providers that have an API key configured.
     * Order: Anthropic → Gemini → OpenAI → Ollama (local dev).
     *
     * @return array<int, Lab>
     */
    public static function list(): array
    {
        $providers = [];

        if (config('ai.providers.anthropic.key')) {
            $providers[] = Lab::Anthropic;
        }

        if (config('ai.providers.azure.key') && config('ai.providers.azure.url')) {
            $providers[] = Lab::Azure;
        }

        if (config('ai.providers.gemini.key')) {
            $providers[] = Lab::Gemini;
        }

        if (config('ai.providers.groq.key')) {
            $providers[] = Lab::Groq;
        }

        if (config('ai.providers.mistral.key')) {
            $providers[] = Lab::Mistral;
        }

        if (config('ai.providers.deepseek.key')) {
            $providers[] = Lab::DeepSeek;
        }

        if (config('ai.providers.openrouter.key')) {
            $providers[] = Lab::OpenRouter;
        }

        if (config('ai.providers.openai.key')) {
            $providers[] = Lab::OpenAI;
        }

        if (config('ai.providers.xai.key')) {
            $providers[] = Lab::xAI;
        }

        if (self::ollamaEnabled()) {
            $providers[] = Lab::Ollama;
        }

        return $providers;
    }

    private static function ollamaEnabled(): bool
    {
        $url = config('ai.providers.ollama.url');
        $key = config('ai.providers.ollama.key');

        if (! $url) {
            return false;
        }

        if ($key) {
            return true;
        }

        return $url !== 'http://localhost:11434';
    }
}
