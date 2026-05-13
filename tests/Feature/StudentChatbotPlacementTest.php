<?php

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;

it('shows AI chat chrome on student dashboard', function () {
    $student = User::factory()->student()->create();

    $html = $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('AI Chat Assistant');
});

it('does not show AI chat chrome on teacher exams index', function () {
    $teacher = User::factory()->teacher()->create();

    $html = $this->actingAs($teacher)
        ->get(route('teacher.exams.index'))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('AI Chat Assistant');
});
