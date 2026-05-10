<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[Model('claude-haiku-4-5-20251001')]
#[MaxTokens(512)]
#[Temperature(0.4)]
#[Timeout(60)]
class StudentChatAgent implements Agent
{
    use Promptable;

    public function __construct(
        private readonly string $conversation,
        private readonly string $message,
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<INSTRUCTIONS
        You are a friendly tutoring assistant helping a student review exam material.

        Conversation so far:
        {$this->conversation}

        The student's latest message:
        "{$this->message}"

        Rules:
        - Respond in 2–6 sentences.
        - Be clear, supportive, and precise.
        - If the student asks for the answer directly, explain the reasoning instead of just giving the answer.
        INSTRUCTIONS;
    }
}
