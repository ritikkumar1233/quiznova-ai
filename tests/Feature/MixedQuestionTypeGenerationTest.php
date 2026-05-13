<?php

use App\Ai\Agents\QuestionGeneratorAgent;
use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

it('merges synchronous multi-type AI responses into pending questions', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                [
                    'question' => 'MC one?',
                    'type' => QuestionType::MultipleChoice->value,
                    'options' => ['a', 'b', 'c', 'd'],
                    'correct_answer' => 'a',
                    'explanation' => 'Because.',
                    'difficulty' => 2,
                ],
            ],
        ],
        [
            'questions' => [
                [
                    'question' => 'TF one?',
                    'type' => QuestionType::TrueFalse->value,
                    'options' => ['True', 'False'],
                    'correct_answer' => 'True',
                    'explanation' => 'Because.',
                    'difficulty' => 2,
                ],
            ],
        ],
    ]);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'Mixed topic')
        ->set('aiMixMultipleChoice', true)
        ->set('aiCountMultipleChoice', 1)
        ->set('aiMixTrueFalse', true)
        ->set('aiCountTrueFalse', 1)
        ->set('aiMixShortAnswer', false)
        ->set('aiDifficulty', 2)
        ->call('streamGenerateWithAi')
        ->assertSet('aiError', '')
        ->assertCount('pendingAiQuestions', 2);
});

it('deduplicates AI questions that share the same text', function () {
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();

    QuestionGeneratorAgent::fake([
        [
            'questions' => [
                [
                    'question' => 'Same?',
                    'type' => QuestionType::ShortAnswer->value,
                    'options' => [],
                    'correct_answer' => 'A',
                    'explanation' => 'x',
                    'difficulty' => 1,
                ],
                [
                    'question' => 'Same?',
                    'type' => QuestionType::ShortAnswer->value,
                    'options' => [],
                    'correct_answer' => 'B',
                    'explanation' => 'x',
                    'difficulty' => 1,
                ],
            ],
        ],
    ]);

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $exam])
        ->set('aiTopic', 'Topic')
        ->set('aiMixMultipleChoice', false)
        ->set('aiMixTrueFalse', false)
        ->set('aiMixShortAnswer', true)
        ->set('aiCountShortAnswer', 2)
        ->set('aiDifficulty', 1)
        ->call('streamGenerateWithAi')
        ->assertCount('pendingAiQuestions', 1);
});

it('loads mixed random questions from other exams into pending review', function () {
    $teacher = User::factory()->teacher()->create();
    $poolExam = Exam::factory()->published()->for($teacher)->create();
    $targetExam = Exam::factory()->published()->for($teacher)->create();

    Question::factory()->count(2)->for($poolExam)->create();
    Question::factory()->trueFalse()->for($poolExam)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $targetExam])
        ->set('aiMixMultipleChoice', true)
        ->set('aiCountMultipleChoice', 2)
        ->set('aiMixTrueFalse', true)
        ->set('aiCountTrueFalse', 1)
        ->set('aiMixShortAnswer', false)
        ->call('importMixedRandomFromBank')
        ->assertSet('aiError', '')
        ->assertCount('pendingAiQuestions', 3);
});

it('rejects bank import when fewer questions exist than requested', function () {
    $teacher = User::factory()->teacher()->create();
    $poolExam = Exam::factory()->published()->for($teacher)->create();
    $targetExam = Exam::factory()->published()->for($teacher)->create();

    Question::factory()->count(2)->for($poolExam)->create();

    Livewire::actingAs($teacher)
        ->test('pages::teacher.exams.questions', ['exam' => $targetExam])
        ->set('aiMixMultipleChoice', true)
        ->set('aiCountMultipleChoice', 10)
        ->set('aiMixTrueFalse', false)
        ->set('aiMixShortAnswer', false)
        ->call('importMixedRandomFromBank')
        ->assertHasErrors(['aiBank']);
});
