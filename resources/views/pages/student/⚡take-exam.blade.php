<?php

use App\Ai\Agents\AutoGraderAgent;
use App\Ai\Agents\HintAgent;
use App\Ai\ResolvedProviders;
use App\Enums\QuestionType;
use App\Events\AttemptSubmittedEvent;
use App\Jobs\GenerateAttemptEmbeddingJob;
use App\Models\Attempt;
use App\Models\Exam;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Ai\Streaming\Events\TextDelta;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.exam')] #[Title('Take Exam')] class extends Component {
    public Exam $exam;
    public ?Attempt $attempt = null;

    /**
     * Answers keyed by question ID.
     * Shape: { value: string, flagged: bool }
     * After AI grading: { value, flagged, ai_graded, ai_score, ai_explanation, ai_suggestion, raw_answer }
     *
     * @var array<int|string, array<string, mixed>>
     */
    public array $answers = [];

    /** @var array<int|string, string> */
    public array $hints = [];

    public bool $timedOut = false;

    #[Url(as: 'preview', except: false)]
    public bool $isPreview = false;

    /** 'all' | 'flagged' */
    public string $filter = 'all';
    public int $maxViolations = 3;
    public bool $submittedForViolations = false;

    public function mount(): void
    {
        if ($this->isPreview) {
            // Only the exam's owner may preview
            abort_unless(auth()->id() === $this->exam->user_id, 403);
        } else {
            abort_unless($this->exam->isPublished(), 404);
        }

        if (! $this->isPreview) {
            $this->attempt = auth()->user()->attempts()
                ->where('exam_id', $this->exam->id)
                ->whereNull('completed_at')
                ->latest()
                ->first();

            if (! $this->attempt) {
                $this->attempt = auth()->user()->attempts()->create([
                    'exam_id'    => $this->exam->id,
                    'started_at' => now(),
                ]);
            }

            $this->answers = $this->attempt->answers ?? [];

            if ($this->attempt->violations >= $this->maxViolations && ! $this->attempt->isCompleted()) {
                $this->submittedForViolations = true;
                $this->submitExam();

                return;
            }
        }

        $this->normalizeAnswers();
    }

    /**
     * Ensure every question slot uses the canonical { value, flagged } shape.
     * Handles three legacy cases:
     *   1. Slot missing entirely (new exam)
     *   2. Slot is a plain string (legacy attempt)
     *   3. Slot is an AI-graded array without the 'value' key (legacy AI-graded)
     */
    private function normalizeAnswers(): void
    {
        foreach ($this->exam->questions as $question) {
            $id  = $question->id;
            $raw = $this->answers[$id] ?? null;

            if ($raw === null) {
                $this->answers[$id] = ['value' => '', 'flagged' => false];
            } elseif (! is_array($raw)) {
                $this->answers[$id] = ['value' => $raw, 'flagged' => false];
            } elseif (! array_key_exists('value', $raw)) {
                // Old AI-graded shape: { raw_answer, ai_score, ... } — lift raw_answer into value
                $this->answers[$id] = array_merge(['value' => $raw['raw_answer'] ?? '', 'flagged' => false], $raw);
            }
            // Otherwise already in new shape — leave as-is
        }
    }

    /**
     * Seconds remaining based on server time — cannot be spoofed by the client.
     * Returns 0 when the exam has no time limit.
     */
    #[Computed]
    public function timeRemaining(): int
    {
        if (! $this->exam->time_limit || ! $this->attempt) {
            return 0;
        }

        $elapsed = (int) $this->attempt->started_at->diffInSeconds(now(), absolute: true);
        $limit   = $this->exam->time_limit * 60;

        return max(0, $limit - $elapsed);
    }

    /**
     * Called by the Alpine ticker every second once the client countdown hits zero.
     * Validates real server-side elapsed time before auto-submitting.
     */
    public function checkTimer(): void
    {
        if (! $this->exam->time_limit) {
            return;
        }

        if ($this->timeRemaining <= 0) {
            $this->timedOut = true;
            $this->submitExam();
        }
    }

    /**
     * Toggle the flagged state for a question and auto-save to the database.
     * Also acts as an auto-save for the current answer value.
     */
    public function toggleFlag(int $questionId): void
    {
        if (! isset($this->answers[$questionId])) {
            $this->answers[$questionId] = ['value' => '', 'flagged' => true];
        } else {
            $this->answers[$questionId]['flagged'] = ! ($this->answers[$questionId]['flagged'] ?? false);
        }

        $this->attempt->update(['answers' => $this->answers]);
    }

    public function streamHint(int $questionId): void
    {
        if (Cache::has('ai_budget_exceeded:'.auth()->id()) ||
            ! RateLimiter::attempt('ai:'.auth()->id(), config('ai.rate_limit.per_minute', 30), fn () => true)) {
            $this->hints[$questionId] = 'AI is temporarily unavailable. Please try again later.';

            return;
        }

        $question = $this->exam->questions->firstWhere('id', $questionId);

        if (! $question) {
            return;
        }

        $this->hints[$questionId] = '';

        $answerData    = $this->answers[$questionId] ?? null;
        $currentAnswer = is_array($answerData) ? ($answerData['value'] ?? '') : ($answerData ?? '');

        try {
            $stream = (new HintAgent($question->question))
                ->stream(
                    $currentAnswer ?: 'I have not answered yet.',
                    provider: ResolvedProviders::list(),
                );

            foreach ($stream as $event) {
                if ($event instanceof TextDelta) {
                    $this->hints[$questionId] .= $event->delta;
                    $this->stream(to: "hint-{$questionId}", content: $event->delta);
                }
            }
        } catch (\Throwable $e) {
            logger()->warning('Hint streaming failed', ['error' => $e->getMessage()]);
            $this->hints[$questionId] = 'Hint unavailable — AI provider could not be reached. Please try again later.';
            $this->stream(to: "hint-{$questionId}", content: $this->hints[$questionId]);
        }
    }

    public function submitExam(): void
    {
        // Block submission in preview mode
        if ($this->isPreview) {
            return;
        }

        // Idempotency guard — already submitted (double-click or race condition)
        if ($this->attempt->isCompleted()) {
            $this->redirect(route('student.attempts.results', $this->attempt), navigate: true);

            return;
        }

        // Skip validation on auto-submit; answers may be partial
        if (! $this->timedOut) {
            $this->validate([
                'answers'           => 'array',
                'answers.*.value'   => 'nullable|string|max:500',
                'answers.*.flagged' => 'nullable|boolean',
            ]);
        }

        $questions = $this->exam->questions;
        $correct   = 0;

        foreach ($questions as $question) {
            $answerData = $this->answers[$question->id] ?? null;
            $given      = is_array($answerData) ? ($answerData['value'] ?? null) : $answerData;
            $flagged    = is_array($answerData) ? ($answerData['flagged'] ?? false) : false;

            if ($given === null || $given === '') {
                continue;
            }

            if ($question->type === QuestionType::ShortAnswer) {
                try {
                    $result = (new AutoGraderAgent($question->question, $question->correct_answer))
                        ->prompt($given, provider: ResolvedProviders::list());

                    $this->answers[$question->id] = [
                        'value'          => $given,
                        'flagged'        => $flagged,
                        'raw_answer'     => $given,
                        'ai_score'       => $result['score'],
                        'ai_explanation' => $result['explanation'],
                        'ai_suggestion'  => $result['suggestion'],
                        'ai_graded'      => true,
                    ];

                    if ($result['is_correct']) {
                        $correct++;
                    }
                } catch (\Throwable) {
                    if (strtolower(trim($given)) === strtolower(trim($question->correct_answer))) {
                        $correct++;
                    }
                }
            } else {
                if (strtolower(trim($given)) === strtolower(trim($question->correct_answer))) {
                    $correct++;
                }
            }
        }

        $score = $questions->count() > 0
            ? (int) round(($correct / $questions->count()) * 100)
            : 0;

        $this->attempt->update([
            'answers'      => $this->answers,
            'score'        => $score,
            'completed_at' => now(),
        ]);

        GenerateAttemptEmbeddingJob::dispatch($this->attempt);

        // Broadcast live submission counter to teacher dashboard
        if (config('broadcasting.default') === 'reverb') {
            $completedCount = $this->exam->attempts()->completed()->count();

            AttemptSubmittedEvent::dispatch(
                $this->exam->id,
                $completedCount,
                auth()->user()->name,
            );
        }

        if ($this->submittedForViolations) {
            $this->dispatch('toast', variant: 'danger', heading: 'Disqualified', text: 'You have been disqualified due to multiple violations.');
        } elseif ($this->timedOut) {
            $this->dispatch('toast', variant: 'warning', heading: "Time's up", text: 'Your exam has been automatically submitted.');
        }

        $this->redirect(route('student.attempts.results', $this->attempt), navigate: true);
    }

    #[Computed]
    public function questions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->exam->questions;
    }

    #[Computed]
    public function filteredQuestions(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->filter === 'flagged') {
            return $this->questions->filter(
                fn ($q) => $this->answers[$q->id]['flagged'] ?? false
            )->values();
        }

        return $this->questions;
    }

    #[Computed]
    public function flaggedCount(): int
    {
        return collect($this->answers)->filter(fn ($a) => $a['flagged'] ?? false)->count();
    }
}; ?>

<div
    class="mx-auto max-w-3xl flex flex-col gap-6"
    x-data="{
        isPreview: @js($isPreview),
        violations: @js((int) ($attempt?->violations ?? 0)),
        maxViolations: @js($maxViolations),
        warningMessage: '',
        modalTitle: 'Fullscreen Required',
        modalMessage: '',
        showViolationModal: false,
        isHandlingViolation: false,
        isExitingPage: false,
        isFinishingExam: false,
        lastViolationAt: 0,
        pendingF11Exit: false,
        violationEndpoint: @js($attempt ? route('student.attempts.violations', $attempt) : ''),
        csrfToken: @js(csrf_token()),
        async init() {
            if (!this.isPreview) {
                // Keep the student on this route when using the browser Back button (no `beforeunload` dialog).
                history.pushState({ examMode: true }, '', window.location.href);
                window.addEventListener('popstate', () => {
                    if (this.isExitingPage || this.isFinishingExam) {
                        return;
                    }
                    history.pushState({ examMode: true }, '', window.location.href);
                    this.warningMessage = 'Navigation away from the exam is disabled. Use Submit Exam when you are finished.';
                });
            }

            if (this.isPreview || !document.fullscreenEnabled) {
                return;
            }

            await this.enterFullscreen();

            const leaveFullscreen = () => {
                this.isExitingPage = true;
                this.exitFullscreenSafely();
            };

            document.addEventListener('keydown', (event) => this.handleKeydown(event), true);
            document.addEventListener('fullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
            document.addEventListener('livewire:navigating', leaveFullscreen);
            window.addEventListener('pagehide', leaveFullscreen);
            window.addEventListener('beforeunload', () => {
                this.isExitingPage = true;
                this.exitFullscreenSafely();
            });
        },
        handleKeydown(event) {
            if (this.isPreview || this.isExitingPage || this.isHandlingViolation) {
                return;
            }

            if (event.key === 'F11' || event.keyCode === 122) {
                event.preventDefault();
                this.pendingF11Exit = true;
                this.registerViolation('f11_key');
            }
        },
        async enterFullscreen() {
            if (document.fullscreenElement || this.isExitingPage) {
                return;
            }

            try {
                await document.documentElement.requestFullscreen();
            } catch (error) {
                this.warningMessage = 'Fullscreen could not be enabled automatically. Please allow fullscreen to continue the quiz.';
            }
        },
        async exitFullscreenSafely() {
            const fullscreenElement = document.fullscreenElement
                || document.webkitFullscreenElement
                || document.msFullscreenElement;

            if (!fullscreenElement) {
                console.log('Fullscreen exited');
                return;
            }

            try {
                if (typeof document.exitFullscreen === 'function') {
                    await document.exitFullscreen();
                } else if (typeof document.webkitExitFullscreen === 'function') {
                    document.webkitExitFullscreen();
                } else if (typeof document.msExitFullscreen === 'function') {
                    document.msExitFullscreen();
                }
                console.log('Fullscreen exited');
            } catch (error) {
                // Ignore browser-level fullscreen errors; user may have already exited.
                console.log('Fullscreen exited');
            }
        },
        async submitQuizSafely(force = false) {
            if (this.isFinishingExam && !force) {
                return;
            }

            this.isFinishingExam = true;
            this.isExitingPage = true;

            // Exit fullscreen before submitting. A brief delay helps browsers settle
            // fullscreen state to avoid race conditions with Livewire/form submit.
            await this.exitFullscreenSafely();
            await new Promise((resolve) => setTimeout(resolve, 260));

            const quizForm = document.getElementById('quizForm');
            let livewireSubmitted = false;

            try {
                console.log('Submitting exam...');
                await Promise.race([
                    $wire.submitExam(),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('Livewire submit timeout')), 3000)),
                ]);
                livewireSubmitted = true;
            } catch (error) {
                // Fallback is mandatory to guarantee submission even if Livewire fails/times out.
            }

            if (!livewireSubmitted && quizForm) {
                console.log('Fallback submit triggered');
                quizForm.submit();
            }
        },
        async handleFullscreenChange() {
            if (this.isPreview || this.isExitingPage || document.fullscreenElement || this.isHandlingViolation) {
                return;
            }

            const reason = this.pendingF11Exit ? 'f11_exit' : 'fullscreen_exit';
            this.pendingF11Exit = false;

            await this.registerViolation(reason);
        },
        async handleVisibilityChange() {
            if (this.isPreview || this.isExitingPage || this.isHandlingViolation) {
                return;
            }

            if (document.hidden) {
                await this.registerViolation('tab_switch');
            }
        },
        async registerViolation(reason) {
            const now = Date.now();
            const isTabSwitch = reason === 'tab_switch';
            const violationEvent = isTabSwitch ? 'tab_switch' : 'fullscreen_exit';
            const baseWarning = isTabSwitch
                ? 'Tab switch detected. Stay on the exam tab during the test.'
                : 'You must stay in fullscreen mode during the exam.';

            if (this.isHandlingViolation || now - this.lastViolationAt < 1200) {
                return;
            }

            this.lastViolationAt = now;
            this.isHandlingViolation = true;
            this.warningMessage = baseWarning;
            this.modalTitle = 'Fullscreen Required';
            this.modalMessage = baseWarning;
            this.showViolationModal = true;

            try {
                const response = await fetch(this.violationEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        event: violationEvent,
                        reason: reason,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Unable to record violation on the server.');
                }

                const data = await response.json();
                this.violations = Number(data.violations ?? this.violations + 1);

                if (Boolean(data.must_submit)) {
                    console.log('Violation limit reached');
                    this.isFinishingExam = true;
                    this.isExitingPage = true;
                    this.modalTitle = 'Disqualified';
                    this.modalMessage = 'You have been disqualified due to multiple violations';
                    this.warningMessage = this.modalMessage;
                    this.showViolationModal = false;
                    $wire.set('submittedForViolations', true);
                    this.isHandlingViolation = false;
                    await this.submitQuizSafely(true);

                    return;
                }
            } catch (error) {
                this.violations += 1;
                this.modalTitle = 'Fullscreen Required';
                this.modalMessage = baseWarning;
                this.warningMessage = isTabSwitch
                    ? 'Tab switch violation could not be synced right now, but tab switching is still not allowed.'
                    : 'Violation could not be synced right now, but fullscreen is still required.';

                // If syncing failed but local violations reached the limit, enforce submission locally
                if (this.violations >= this.maxViolations) {
                    console.log('Local violation limit reached');
                    this.isFinishingExam = true;
                    this.isExitingPage = true;
                    this.modalTitle = 'Disqualified';
                    this.modalMessage = 'You have been disqualified due to multiple violations';
                    this.warningMessage = this.modalMessage;
                    this.showViolationModal = false;
                    $wire.set('submittedForViolations', true);
                    this.isHandlingViolation = false;
                    await this.submitQuizSafely(true);

                    return;
                }
            }
        },
        async acknowledgeViolation() {
            if (!this.showViolationModal || this.isExitingPage) {
                return;
            }

            this.showViolationModal = false;
            await this.enterFullscreen();
            this.isHandlingViolation = false;
        },

        /**
         * HTML implicitly submits a form when Enter is pressed on many focused controls
         * (e.g. a selected radio). Students then submit by accident. We block that here
         * while keeping: Tab/arrow navigation, Enter in textareas, and Enter on the
         * real Submit control (keyboard accessibility).
         */
        suppressExamEnterSubmit(event) {
            const target = event.target;
            if (target instanceof HTMLTextAreaElement) {
                return;
            }
            if (
                target instanceof HTMLButtonElement && target.type === 'submit'
            ) {
                return;
            }
            if (
                target instanceof HTMLInputElement && target.type === 'submit'
            ) {
                return;
            }
            const submitBtn = target.closest('button[type=submit], input[type=submit]');
            if (submitBtn) {
                return;
            }
            event.preventDefault();
        },
    }"
>
    @if ($isPreview)
        <flux:callout variant="warning" icon="eye">
            <flux:callout.heading>Preview Mode</flux:callout.heading>
            <flux:callout.text>You are previewing this exam as a teacher. Answers cannot be submitted.</flux:callout.text>
            <x-slot name="actions">
                <flux:button
                    size="sm"
                    variant="ghost"
                    :href="route('teacher.exams.questions', $exam)"
                    wire:navigate
                >
                    End Preview
                </flux:button>
            </x-slot>
        </flux:callout>
    @endif

    @if (! $isPreview)
        <div
            x-cloak
            x-show="warningMessage"
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            role="alert"
        >
            <div class="flex items-center justify-between gap-3">
                <span x-text="warningMessage"></span>
                <span class="font-semibold">
                    Violations: <span x-text="violations"></span>/<span x-text="maxViolations"></span>
                </span>
            </div>
        </div>

        <div
            x-cloak
            x-show="showViolationModal"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="fullscreen-warning-title"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
                <h2 id="fullscreen-warning-title" class="text-lg font-semibold text-charcoal-900" x-text="modalTitle"></h2>
                <p class="mt-3 text-sm text-charcoal-700" x-text="modalMessage"></p>
                <p class="mt-2 text-sm font-medium text-red-700">
                    Warnings: <span x-text="violations"></span>/<span x-text="maxViolations"></span>
                </p>
                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                        x-bind:disabled="isExitingPage"
                        x-on:click="acknowledgeViolation()"
                    >
                        OK
                    </button>
                </div>
            </div>
        </div>
    @endif

        <div class="space-y-1" x-bind:class="{ 'pointer-events-none opacity-70 select-none': showViolationModal || isFinishingExam }">
            <flux:heading size="xl">{{ $exam->title }}</flux:heading>
            <div class="flex flex-wrap items-center gap-3 mt-1">
                <flux:text>{{ $this->questions->count() }} questions</flux:text>
                @if ($exam->time_limit)
                    <flux:text>• {{ $exam->time_limit }} minute limit</flux:text>
                @endif
            </div>
        </div>

    {{-- ── Countdown Timer ── --}}
    @if ($exam->time_limit)
        <div
            x-data="{
                seconds: {{ $this->timeRemaining }},
                expired: false,
                get minutes() { return Math.floor(this.seconds / 60) },
                get secs()    { return String(this.seconds % 60).padStart(2, '0') },
                get isWarning() { return this.seconds <= 300 && this.seconds > 60 },
                get isDanger()  { return this.seconds <= 60 && !this.expired },
                init() {
                    if (this.seconds <= 0) { this.expired = true; $wire.checkTimer(); return; }
                    const tick = setInterval(() => {
                        if (this.seconds > 0) { this.seconds--; }
                        else { clearInterval(tick); this.expired = true; $wire.checkTimer(); }
                    }, 1000);
                }
            }"
            class="bento-flat py-3 px-4 flex items-center justify-between transition-colors duration-300"
            :class="{
                'border-amber-300 bg-amber-50': isWarning,
                'border-red-300 bg-red-50 animate-pulse': isDanger,
                'border-red-400 bg-red-100': expired
            }"
        >
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0"
                    :class="{ 'text-amber-600': isWarning, 'text-red-600': isDanger || expired, 'text-charcoal-600': !isWarning && !isDanger && !expired }"
                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span class="text-sm font-medium"
                    :class="{ 'text-amber-800': isWarning, 'text-red-800': isDanger || expired, 'text-charcoal-600': !isWarning && !isDanger && !expired }"
                    x-text="expired ? 'Time\'s up — submitting…' : 'Time Remaining'"></span>
            </div>
            <span class="font-mono text-lg font-bold tabular-nums"
                :class="{ 'text-amber-700': isWarning, 'text-red-700': isDanger || expired, 'text-charcoal-900': !isWarning && !isDanger && !expired }"
                x-text="expired ? '0:00' : `${minutes}:${secs}`"></span>
        </div>

        <div x-data="{ seconds: {{ $this->timeRemaining }} }" x-init="setInterval(() => { if (seconds > 0) seconds-- }, 1000)"
            x-show="seconds <= 300 && seconds > 60" x-cloak
            class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <strong>5 minutes remaining</strong> — please start wrapping up.
        </div>

        <div x-data="{ seconds: {{ $this->timeRemaining }} }" x-init="setInterval(() => { if (seconds > 0) seconds-- }, 1000)"
            x-show="seconds <= 60 && seconds > 0" x-cloak
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <strong>Less than 1 minute remaining!</strong> Your exam will be submitted automatically.
        </div>
    @endif

    @if ($exam->description)
        <flux:callout icon="information-circle">{{ $exam->description }}</flux:callout>
    @endif

    {{-- ── Filter tabs ── --}}
    <div class="flex items-center gap-1 border-b border-gray-200">
        <button
            wire:click="$set('filter', 'all')"
            class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
            :class="'{{ $filter }}' === 'all'
                ? 'border-teal-600 text-teal-700'
                : 'border-transparent text-charcoal-600 hover:text-charcoal-900'"
        >
            All Questions
            <span class="ml-1.5 inline-flex items-center justify-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-charcoal-600">
                {{ $this->questions->count() }}
            </span>
        </button>
        <button
            wire:click="$set('filter', 'flagged')"
            class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px flex items-center gap-1.5"
            :class="'{{ $filter }}' === 'flagged'
                ? 'border-teal-600 text-teal-700'
                : 'border-transparent text-charcoal-600 hover:text-charcoal-900'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="{{ $this->flaggedCount > 0 ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
            </svg>
            Review Later
            @if ($this->flaggedCount > 0)
                <span class="inline-flex items-center justify-center rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-700">
                    {{ $this->flaggedCount }}
                </span>
            @endif
        </button>
    </div>

    {{-- ── Empty state for flagged filter ── --}}
    @if ($filter === 'flagged' && $this->flaggedCount === 0)
        <div class="bento-flat text-center py-10 space-y-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-8 mx-auto text-charcoal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
            </svg>
            <flux:text>No questions flagged for review yet.</flux:text>
            <flux:text class="text-sm">Click the bookmark icon on any question to flag it.</flux:text>
        </div>
    @endif

    <form
        id="quizForm"
        wire:submit="submitExam"
        x-on:submit="if (!isFinishingExam) { isExitingPage = true; isFinishingExam = true; }"
        x-on:keydown.enter.capture="suppressExamEnterSubmit($event)"
        x-bind:class="{ 'pointer-events-none opacity-70 select-none': showViolationModal || isFinishingExam }"
        class="space-y-4"
    >
        @foreach ($this->filteredQuestions as $index => $question)
            @php $isFlagged = $answers[$question->id]['flagged'] ?? false; @endphp
            <div
                class="bento-flat space-y-4 transition-colors duration-150"
                style="{{ $isFlagged ? 'border-color: #5EEAD4; background-color: #F0FDFA;' : '' }}"
                wire:key="q-{{ $question->id }}"
            >
                <div style="display:flex; align-items:flex-start; gap:.75rem;">
                    <span style="display:inline-flex; align-items:center; justify-content:center; min-width:1.75rem; height:1.75rem; border-radius:8px; background:#F0FDFA; color:#0F766E; font-size:.75rem; font-weight:700; flex-shrink:0; margin-top:1px;">
                        {{ $loop->index + 1 }}
                    </span>
                    <flux:heading style="flex:1; padding-top:2px;">{{ $question->question }}</flux:heading>

                    {{-- Bookmark / flag button --}}
                    <button
                        type="button"
                        wire:click="toggleFlag({{ $question->id }})"
                        wire:loading.attr="disabled"
                        wire:target="toggleFlag({{ $question->id }})"
                        title="{{ $isFlagged ? 'Remove flag' : 'Flag for review' }}"
                        class="shrink-0 mt-0.5 p-1 rounded-md transition-colors {{ $isFlagged ? 'text-teal-600 hover:text-teal-800' : 'text-charcoal-400 hover:text-charcoal-700' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="{{ $isFlagged ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                        </svg>
                    </button>
                </div>

                @if ($question->options)
                    <div class="space-y-2 pt-1">
                        @foreach ($question->options as $option)
                            <label class="quiz-option" wire:key="opt-{{ $question->id }}-{{ $loop->index }}">
                                <input
                                    type="radio"
                                    wire:model="answers.{{ $question->id }}.value"
                                    value="{{ $option }}"
                                />
                                <span>{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <flux:input
                        wire:model="answers.{{ $question->id }}.value"
                        placeholder="Type your answer…"
                    />

                    {{-- Hint section for short-answer questions --}}
                    <div
                        x-data="{ showHint: {{ isset($hints[$question->id]) ? 'true' : 'false' }}, loading: false }"
                        class="space-y-2"
                    >
                        <div class="flex justify-end">
                            <flux:button
                                size="sm"
                                variant="ghost"
                                x-bind:disabled="loading"
                                x-on:click="loading = true; showHint = true; $wire.streamHint({{ $question->id }}).then(() => loading = false)"
                                :loading="false"
                            >
                                <span x-show="!loading" class="inline-flex items-center gap-1">
                                    <flux:icon.light-bulb class="size-4" />
                                    Get a Hint
                                </span>
                                <span x-show="loading" x-cloak class="inline-flex items-center gap-1">
                                    <svg class="animate-spin size-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    Thinking…
                                </span>
                            </flux:button>
                        </div>

                        <div
                            x-show="showHint"
                            style="display: none"
                            class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800"
                        >
                            <div class="flex items-start gap-2">
                                <flux:icon.light-bulb class="size-4 mt-0.5 shrink-0 text-amber-600" />
                                <span wire:stream="hint-{{ $question->id }}">{{ $hints[$question->id] ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach

        <div class="flex justify-end gap-3 pt-2 pb-4">
            @if ($isPreview)
                <flux:callout variant="warning" icon="eye" class="w-full">
                    <flux:callout.text>Preview mode — submission is disabled.</flux:callout.text>
                </flux:callout>
            @else
                <flux:button
                    variant="primary"
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="submitExam"
                >
                    <span wire:loading.remove wire:target="submitExam" class="inline-flex items-center gap-1">
                        <flux:icon.check class="size-4" />
                        Submit Exam
                    </span>
                    <span wire:loading wire:target="submitExam" class="inline-flex items-center gap-1">
                        <svg class="animate-spin size-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        Grading…
                    </span>
                </flux:button>
            @endif
        </div>
    </form>
</div>
