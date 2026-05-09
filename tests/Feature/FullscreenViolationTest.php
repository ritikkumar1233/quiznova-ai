<?php

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;

it('records a fullscreen violation for the current student attempt', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $attempt = Attempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'violations' => 0,
    ]);

    $response = $this->actingAs($student)->postJson(
        route('student.attempts.violations', $attempt),
        ['event' => 'fullscreen_exit'],
    );

    $response->assertOk()
        ->assertJsonPath('violations', 1)
        ->assertJsonPath('must_submit', false);

    expect($attempt->fresh()->violations)->toBe(1);
});

it('marks attempt for auto-submit when violations reach the limit', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $attempt = Attempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'violations' => 2,
    ]);

    $response = $this->actingAs($student)->postJson(
        route('student.attempts.violations', $attempt),
        ['event' => 'fullscreen_exit'],
    );

    $response->assertOk()
        ->assertJsonPath('violations', 3)
        ->assertJsonPath('must_submit', true);

    expect($attempt->fresh()->disqualified_at)->not->toBeNull();
});

it('forbids recording violations for another students attempt', function () {
    $student = User::factory()->student()->create();
    $otherStudent = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $attempt = Attempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $otherStudent->id,
    ]);

    $this->actingAs($student)->postJson(
        route('student.attempts.violations', $attempt),
        ['event' => 'fullscreen_exit'],
    )->assertForbidden();
});

it('does not keep incrementing after attempt is already disqualified', function () {
    $student = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $exam = Exam::factory()->published()->for($teacher)->create();
    $attempt = Attempt::factory()->create([
        'exam_id' => $exam->id,
        'user_id' => $student->id,
        'violations' => 3,
        'disqualified_at' => now(),
    ]);

    $response = $this->actingAs($student)->postJson(
        route('student.attempts.violations', $attempt),
        ['event' => 'fullscreen_exit'],
    );

    $response->assertOk()
        ->assertJsonPath('violations', 3)
        ->assertJsonPath('must_submit', true);
});
