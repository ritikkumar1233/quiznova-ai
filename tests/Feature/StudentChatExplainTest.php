<?php

use App\Ai\Agents\ExplainAnswerAgent;
use App\Livewire\StudentChat;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use Livewire\Livewire;

it('stores an AI explanation when explain is triggered', function () {
    $student = User::factory()->student()->create();

    ExplainAnswerAgent::fake([
        'The equation factors into (x-2)(x-3)=0, so the solutions are x=2 and x=3.',
    ]);

    Livewire::actingAs($student)
        ->test(StudentChat::class)
        ->call('handleOpenChat', [
            'question' => 'What are the solutions to x^2 - 5x + 6 = 0?',
            'answer' => '34',
            'correctAnswer' => 'x = 2, x = 3',
        ]);

    expect(ChatSession::count())->toBe(1)
        ->and(ChatMessage::where('role', 'user')->count())->toBe(1)
        ->and(ChatMessage::where('role', 'assistant')->count())->toBe(1)
        ->and(ChatMessage::where('role', 'assistant')->latest()->value('content'))
        ->toContain('x=2')
        ->and(ChatMessage::where('role', 'assistant')->latest()->value('content'))
        ->toContain('x=3');
});
