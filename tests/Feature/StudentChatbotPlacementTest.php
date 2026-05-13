<?php

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;

it('shows AI chat on student dashboard shell', function () {
    $student = User::factory()->student()->create();

    $html = $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('AI Chat Assistant');
});

it('hides AI chat on active exam page', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->has(Question::factory(), 'questions')->create();

    $html = $this->actingAs($student)
        ->get(route('student.exams.take', $exam))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('AI Chat Assistant');
});

it('does not render student AI chat on teacher layout pages', function () {
    $teacher = User::factory()->teacher()->create();

    $html = $this->actingAs($teacher)
        ->get(route('teacher.exams.index'))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('AI Chat Assistant');
});
