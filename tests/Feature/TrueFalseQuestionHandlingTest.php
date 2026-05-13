<?php

use App\Enums\QuestionType;
use App\Jobs\ProcessGeneratedQuestionsJob;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;

it('resolves true false from flexible ai type strings and stores canonical options', function () {
    $resolved = Question::resolveFromAiPayload([
        'type' => 'True / False',
        'options' => [],
        'question' => 'Sky is blue?',
        'correct_answer' => 'True',
    ]);

    expect($resolved['type'])->toBe(QuestionType::TrueFalse)
        ->and($resolved['options'])->toBe(['True', 'False']);
});

it('exposes choice options for true false when database options are null', function () {
    $exam = Exam::factory()->create();

    $question = Question::query()->create([
        'exam_id' => $exam->id,
        'question' => 'Sample?',
        'type' => QuestionType::TrueFalse,
        'options' => null,
        'correct_answer' => 'False',
        'order' => 1,
    ]);

    expect($question->choiceOptions)->toBe(['True', 'False']);
});

it('process generated questions job persists true false with options', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->for($teacher)->create();

    (new ProcessGeneratedQuestionsJob($exam->id, [
        [
            'question' => 'Is water wet?',
            'type' => 'boolean',
            'options' => [],
            'correct_answer' => 'True',
        ],
    ]))->handle();

    $q = $exam->fresh()->questions->first();

    expect($q)->not->toBeNull()
        ->and($q->type)->toBe(QuestionType::TrueFalse)
        ->and($q->options)->toBe(['True', 'False']);
});

it('student take exam shows radios for true false without stored options', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    Question::query()->create([
        'exam_id' => $exam->id,
        'question' => 'Earth is flat?',
        'type' => QuestionType::TrueFalse,
        'options' => null,
        'correct_answer' => 'False',
        'order' => 1,
    ]);

    $html = $this->actingAs($student)
        ->get(route('student.exams.take', $exam))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('type="radio"')
        ->toContain('Earth is flat?')
        ->not->toContain('Get a Hint');
});
