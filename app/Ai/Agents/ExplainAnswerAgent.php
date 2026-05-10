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
#[Temperature(0.2)]
#[Timeout(60)]
class ExplainAnswerAgent implements Agent
{
    use Promptable;

    public function __construct(
        private readonly string $question,
        private readonly string $studentAnswer,
        private readonly ?string $correctAnswer = null,
    ) {}

    public function instructions(): Stringable|string
    {
        $correctAnswer = $this->correctAnswer ? "Correct answer: \"{$this->correctAnswer}\"" : 'Correct answer: (not provided)';

        return <<<INSTRUCTIONS
        You are a helpful tutor explaining a student's exam answer.

        Question: "{$this->question}"
        Student answer: "{$this->studentAnswer}"
        {$correctAnswer}

        Rules:
        - Explain the correct reasoning step-by-step in 3–6 sentences.
        - If the student is wrong, say where the reasoning diverged and give a concise correction.
        - If the student is correct, confirm and briefly explain why.
        - Use clear, friendly language.
        INSTRUCTIONS;
    }
}
