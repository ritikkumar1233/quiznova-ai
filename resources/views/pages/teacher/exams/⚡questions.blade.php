<?php

use App\Ai\Agents\QuestionGeneratorAgent;
use App\Ai\ResolvedProviders;
use App\Enums\QuestionType;
use App\Jobs\ImportQuestionsFromCsvJob;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Manage Questions')] class extends Component {
    use WithFileUploads;

    public Exam $exam;

    // --- Manual form ---
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $question = '';
    public string $type = QuestionType::MultipleChoice->value;
    public string $correct_answer = '';

    /** @var array<int, string> */
    public array $options = ['', '', '', ''];

    // --- AI generation panel ---
    public bool $showAiPanel = false;

    #[Validate('required|string|max:200')]
    public string $aiTopic = '';

    public bool $aiMixMultipleChoice = true;

    public int $aiCountMultipleChoice = 5;

    public bool $aiMixTrueFalse = false;

    public int $aiCountTrueFalse = 3;

    public bool $aiMixShortAnswer = false;

    public int $aiCountShortAnswer = 2;

    #[Validate('required|integer|min:1|max:5')]
    public int $aiDifficulty = 3;

    // --- CSV Import ---
    #[Validate('file|mimes:csv,txt|max:512')]
    public mixed $csvFile = null;

    // --- Bulk selection ---
    /** @var array<int, int> */
    public array $selectedQuestions = [];

    public bool $selectAll = false;

    public bool $aiGenerating = false;
    public string $aiError = '';

    /** @var array<int, array<string, mixed>> */
    public array $pendingAiQuestions = [];

    public function mount(): void
    {
        abort_unless(auth()->id() === $this->exam->user_id, 403);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showAiPanel = false;
        $this->showForm = true;
    }

    public function toggleAiPanel(): void
    {
        $this->showAiPanel = ! $this->showAiPanel;
        $this->showForm = false;
        $this->aiError = '';
    }

    public function editQuestion(Question $question): void
    {
        $this->editingId = $question->id;
        $this->question = $question->question;
        $this->type = $question->type->value;
        $this->correct_answer = $question->correct_answer;
        $this->options = $question->options ?? ['', '', '', ''];
        $this->showForm = true;
        $this->showAiPanel = false;
    }

    public function saveQuestion(): void
    {
        $rules = [
            'question'       => 'required|string|max:1000',
            'type'           => 'required|string|in:'.implode(',', array_column(QuestionType::cases(), 'value')),
            'correct_answer' => 'required|string|max:500',
        ];

        if (QuestionType::from($this->type)->hasOptions()) {
            $rules['options']   = 'required|array|min:2';
            $rules['options.*'] = 'required|string|max:500';
        }

        $this->validate($rules);

        $data = [
            'question'       => $this->question,
            'type'           => $this->type,
            'correct_answer' => $this->correct_answer,
            'options'        => QuestionType::from($this->type)->hasOptions() ? array_values(array_filter($this->options)) : null,
        ];

        if ($this->editingId) {
            Question::findOrFail($this->editingId)->update($data);
        } else {
            $order = $this->exam->questions()->max('order') + 1;
            $this->exam->questions()->create([...$data, 'order' => $order]);
        }

        $this->resetForm();
    }

    public function deleteQuestion(Question $question): void
    {
        abort_unless($question->exam_id === $this->exam->id, 403);
        $question->delete();
    }

    public function updatedSelectAll(): void
    {
        $this->selectedQuestions = $this->selectAll
            ? $this->questions->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedQuestions)) {
            return;
        }

        $count = Question::query()
            ->whereIn('id', $this->selectedQuestions)
            ->where('exam_id', $this->exam->id)
            ->delete();

        $this->selectedQuestions = [];
        $this->selectAll = false;
        unset($this->questions);

        $this->modal('confirm-bulk-delete')->close();
        $this->dispatch('toast', variant: 'success', heading: 'Deleted', text: "{$count} question(s) deleted.");
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function importCsv(): void
    {
        $this->validateOnly('csvFile');

        $path = $this->csvFile->storeAs(
            'imports',
            'csv-'.$this->exam->id.'-'.time().'.csv',
            'local',
        );

        ImportQuestionsFromCsvJob::dispatchSync($this->exam->id, $path);

        $this->csvFile = null;

        unset($this->questions);

        $this->modal('csv-import')->close();

        $this->dispatch('toast', variant: 'success', heading: 'Import complete', text: 'CSV questions have been imported.');
    }

    /**
     * Backward-compatible entry point: all generation runs synchronously.
     */
    public function generateWithAi(): void
    {
        $this->streamGenerateWithAi();
    }

    /**
     * Synchronous mixed-type generation — one prompt() per selected type, then merge, shuffle, and dedupe.
     */
    public function streamGenerateWithAi(): void
    {
        $providers = ResolvedProviders::list();

        if (empty($providers)) {
            $this->aiError = 'No AI providers are configured. Add a provider API key or set OLLAMA_BASE_URL to use Ollama.';

            return;
        }

        $this->validateOnly('aiTopic');
        $this->validateOnly('aiDifficulty');

        $this->validateAiMixCounts();

        $this->aiError = '';
        $this->aiGenerating = true;
        $this->pendingAiQuestions = [];

        try {
            $merged = [];

            foreach ($this->typeMixSpecifications() as $spec) {
                $agent = new QuestionGeneratorAgent(
                    topic: $this->aiTopic,
                    type: $spec['type'],
                    count: $spec['count'],
                    difficulty: $this->aiDifficulty,
                );

                $response = $agent->prompt(
                    "Generate {$spec['count']} questions about {$this->aiTopic}.",
                    provider: $providers,
                );

                $data = json_decode($response->text, true);

                if (! is_array($data) || ! isset($data['questions'])) {
                    throw new \RuntimeException('AI response invalid.');
                }

                foreach ($data['questions'] as $row) {
                    if (is_array($row)) {
                        $merged[] = $row;
                    }
                }
            }

            $this->pendingAiQuestions = $this->dedupeAiQuestionsByText($merged);
            $this->pendingAiQuestions = collect($this->pendingAiQuestions)->shuffle()->values()->all();

            if (count($this->pendingAiQuestions) > 0) {
                $this->saveToHistory('ai');
            }
        } catch (\Throwable $e) {
            logger()->error('AI generation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->aiError = 'AI generation failed. Check your AI provider keys or Ollama server, then try again.';
        } finally {
            $this->aiGenerating = false;
        }
    }

    /**
     * Build a mixed-type set from the teacher's other exams using random selection (synchronous).
     */
    public function importMixedRandomFromBank(): void
    {
        $this->aiError = '';

        try {
            $this->validateAiMixCounts();
        } catch (ValidationException $e) {
            throw $e;
        }

        $this->assertBankHasEnoughQuestions();

        $specs = $this->typeMixSpecifications();
        $picked = collect();

        foreach ($specs as $spec) {
            $batch = Question::query()
                ->whereHas('exam', fn ($q) => $q->where('user_id', auth()->id()))
                ->where('exam_id', '!=', $this->exam->id)
                ->where('type', $spec['type'])
                ->inRandomOrder()
                ->limit($spec['count'])
                ->get();

            $picked = $picked->merge($batch);
        }

        $picked = $picked->unique('id')->shuffle()->values();

        $this->pendingAiQuestions = $picked->map(fn (Question $q) => [
            'question' => $q->question,
            'type' => $q->type->value,
            'options' => $q->options ?? [],
            'correct_answer' => $q->correct_answer,
            'explanation' => '',
            'difficulty' => $this->aiDifficulty,
        ])->all();

        if (count($this->pendingAiQuestions) > 0) {
            $this->saveToHistory('bank');
        }

        unset($this->questions);

        $this->dispatch('toast', variant: 'success', heading: 'Ready to add', text: count($this->pendingAiQuestions).' question(s) loaded from your question bank. Review below, then add to this exam.');
    }

    /**
     * @return array<int, array{type: string, count: int}>
     */
    private function typeMixSpecifications(): array
    {
        $items = [];

        if ($this->aiMixMultipleChoice && $this->aiCountMultipleChoice > 0) {
            $items[] = ['type' => QuestionType::MultipleChoice->value, 'count' => $this->aiCountMultipleChoice];
        }

        if ($this->aiMixTrueFalse && $this->aiCountTrueFalse > 0) {
            $items[] = ['type' => QuestionType::TrueFalse->value, 'count' => $this->aiCountTrueFalse];
        }

        if ($this->aiMixShortAnswer && $this->aiCountShortAnswer > 0) {
            $items[] = ['type' => QuestionType::ShortAnswer->value, 'count' => $this->aiCountShortAnswer];
        }

        return $items;
    }

    private function validateAiMixCounts(): void
    {
        $rules = [
            'aiCountMultipleChoice' => $this->aiMixMultipleChoice ? 'required|integer|min:1|max:10' : 'nullable|integer|min:0|max:10',
            'aiCountTrueFalse' => $this->aiMixTrueFalse ? 'required|integer|min:1|max:10' : 'nullable|integer|min:0|max:10',
            'aiCountShortAnswer' => $this->aiMixShortAnswer ? 'required|integer|min:1|max:10' : 'nullable|integer|min:0|max:10',
        ];

        $this->validate($rules);

        if ($this->typeMixSpecifications() === []) {
            throw ValidationException::withMessages([
                'aiMix' => ['Select at least one question type and enter a count greater than zero.'],
            ]);
        }

        $total = array_sum(array_column($this->typeMixSpecifications(), 'count'));

        if ($total > 20) {
            throw ValidationException::withMessages([
                'aiMix' => ['Total questions cannot exceed 20 per generation.'],
            ]);
        }
    }

    private function assertBankHasEnoughQuestions(): void
    {
        $lines = [];

        foreach ($this->typeMixSpecifications() as $spec) {
            $available = Question::query()
                ->whereHas('exam', fn ($q) => $q->where('user_id', auth()->id()))
                ->where('exam_id', '!=', $this->exam->id)
                ->where('type', $spec['type'])
                ->count();

            if ($available < $spec['count']) {
                $label = QuestionType::from($spec['type'])->label();
                $lines[] = "{$label}: {$available} available, {$spec['count']} requested.";
            }
        }

        if ($lines !== []) {
            throw ValidationException::withMessages([
                'aiBank' => ['Not enough questions in your other exams: '.implode(' ', $lines)],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    private function dedupeAiQuestionsByText(array $questions): array
    {
        $seen = [];
        $out = [];

        foreach ($questions as $q) {
            $key = mb_strtolower(trim((string) ($q['question'] ?? '')));

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[] = $q;
        }

        return $out;
    }

    /**
     * @return array<int, array{type: string, enabled: bool, count: int}>
     */
    private function snapshotTypeMix(): array
    {
        return [
            ['type' => QuestionType::MultipleChoice->value, 'enabled' => $this->aiMixMultipleChoice, 'count' => $this->aiCountMultipleChoice],
            ['type' => QuestionType::TrueFalse->value, 'enabled' => $this->aiMixTrueFalse, 'count' => $this->aiCountTrueFalse],
            ['type' => QuestionType::ShortAnswer->value, 'enabled' => $this->aiMixShortAnswer, 'count' => $this->aiCountShortAnswer],
        ];
    }

    /**
     * @param  array<int, array{type: string, enabled: bool, count: int}>  $snapshot
     */
    private function restoreTypeMixFromSnapshot(array $snapshot): void
    {
        foreach ($snapshot as $row) {
            if ($row['type'] === QuestionType::MultipleChoice->value) {
                $this->aiMixMultipleChoice = (bool) $row['enabled'];
                $this->aiCountMultipleChoice = (int) $row['count'];
            } elseif ($row['type'] === QuestionType::TrueFalse->value) {
                $this->aiMixTrueFalse = (bool) $row['enabled'];
                $this->aiCountTrueFalse = (int) $row['count'];
            } elseif ($row['type'] === QuestionType::ShortAnswer->value) {
                $this->aiMixShortAnswer = (bool) $row['enabled'];
                $this->aiCountShortAnswer = (int) $row['count'];
            }
        }
    }

    /**
     * @param  array<int, array{type: string, count: int}>  $specs
     */
    private function formatTypeMixLabel(array $specs): string
    {
        return collect($specs)
            ->map(fn (array $s) => QuestionType::from($s['type'])->label().' × '.$s['count'])
            ->implode(', ');
    }

    public function confirmAiQuestion(int $index): void
    {
        $data = $this->pendingAiQuestions[$index] ?? null;

        if (! $data) {
            return;
        }

        $resolved = Question::resolveFromAiPayload($data);
        $order = $this->exam->questions()->max('order') + 1;

        $this->exam->questions()->create([
            'question'       => $data['question'],
            'type'           => $resolved['type']->value,
            'options'        => $resolved['options'],
            'correct_answer' => $data['correct_answer'],
            'order'          => $order,
        ]);

        unset($this->pendingAiQuestions[$index]);
        $this->pendingAiQuestions = array_values($this->pendingAiQuestions);
    }

    public function confirmAllAiQuestions(): void
    {
        $nextOrder = $this->exam->questions()->max('order') + 1;

        foreach ($this->pendingAiQuestions as $data) {
            $resolved = Question::resolveFromAiPayload($data);

            $this->exam->questions()->create([
                'question'       => $data['question'],
                'type'           => $resolved['type']->value,
                'options'        => $resolved['options'],
                'correct_answer' => $data['correct_answer'],
                'order'          => $nextOrder++,
            ]);
        }

        $this->pendingAiQuestions = [];
    }

    public function discardAiQuestion(int $index): void
    {
        unset($this->pendingAiQuestions[$index]);
        $this->pendingAiQuestions = array_values($this->pendingAiQuestions);
    }

    /** Re-populate pendingAiQuestions from a previous generation batch stored in session. */
    public function loadFromHistory(int $index): void
    {
        $history = session("ai_gen_history_{$this->exam->id}", []);

        if (! isset($history[$index])) {
            return;
        }

        $this->pendingAiQuestions = $history[$index]['questions'];
        $this->aiTopic = $history[$index]['topic'] ?? '';

        if (isset($history[$index]['type_specs']) && is_array($history[$index]['type_specs'])) {
            $this->restoreTypeMixFromSnapshot($history[$index]['type_specs']);
        } elseif (isset($history[$index]['type'])) {
            $this->aiMixMultipleChoice = false;
            $this->aiMixTrueFalse = false;
            $this->aiMixShortAnswer = false;
            $legacyType = $history[$index]['type'];
            $legacyCount = (int) ($history[$index]['count'] ?? 1);

            match ($legacyType) {
                QuestionType::MultipleChoice->value => $this->aiMixMultipleChoice = true,
                QuestionType::TrueFalse->value => $this->aiMixTrueFalse = true,
                QuestionType::ShortAnswer->value => $this->aiMixShortAnswer = true,
                default => $this->aiMixShortAnswer = true,
            };

            if ($this->aiMixMultipleChoice) {
                $this->aiCountMultipleChoice = max(1, min(10, $legacyCount));
            }
            if ($this->aiMixTrueFalse) {
                $this->aiCountTrueFalse = max(1, min(10, $legacyCount));
            }
            if ($this->aiMixShortAnswer) {
                $this->aiCountShortAnswer = max(1, min(10, $legacyCount));
            }
        }
    }

    #[Computed]
    public function questions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->exam->questions()->get();
    }

    #[Computed]
    public function questionTypes(): array
    {
        return QuestionType::cases();
    }

    #[Computed]
    public function currentTypeHasOptions(): bool
    {
        return QuestionType::from($this->type)->hasOptions();
    }

    #[Computed]
    public function aiMixTotalRequested(): int
    {
        $total = 0;

        if ($this->aiMixMultipleChoice) {
            $total += max(0, $this->aiCountMultipleChoice);
        }

        if ($this->aiMixTrueFalse) {
            $total += max(0, $this->aiCountTrueFalse);
        }

        if ($this->aiMixShortAnswer) {
            $total += max(0, $this->aiCountShortAnswer);
        }

        return $total;
    }

    /**
     * Last 5 generation batches for this exam, keyed in session.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function aiHistory(): array
    {
        return session("ai_gen_history_{$this->exam->id}", []);
    }

    /** Persist the latest batch to session history (max 5 entries per exam). */
    private function saveToHistory(string $source = 'ai'): void
    {
        $key = "ai_gen_history_{$this->exam->id}";
        $history = session($key, []);
        $specs = $this->typeMixSpecifications();

        array_unshift($history, [
            'topic' => $source === 'bank' ? 'Question bank (random)' : $this->aiTopic,
            'source' => $source,
            'type_specs' => $this->snapshotTypeMix(),
            'type_label' => $this->formatTypeMixLabel($specs),
            'count' => count($this->pendingAiQuestions),
            'questions' => $this->pendingAiQuestions,
            'generated_at' => now()->format('H:i'),
        ]);

        session([$key => array_slice($history, 0, 5)]);
    }

    private function resetForm(): void
    {
        $this->showForm     = false;
        $this->editingId    = null;
        $this->question     = '';
        $this->type         = QuestionType::MultipleChoice->value;
        $this->correct_answer = '';
        $this->options      = ['', '', '', ''];
        $this->resetValidation();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" icon="arrow-left" :href="route('teacher.exams.index')" wire:navigate />
            <div>
                <flux:heading size="xl">{{ $exam->title }}</flux:heading>
                <flux:text>{{ $this->questions->count() }} question(s)</flux:text>
            </div>
        </div>
        @if (!$showForm && !$showAiPanel)
            <div class="flex items-center gap-2">
                <flux:button
                    variant="ghost"
                    icon="eye"
                    :href="route('student.exams.take', $exam) . '?preview=1'"
                    wire:navigate
                >
                    Preview
                </flux:button>
                <flux:modal.trigger name="csv-import">
                    <flux:button variant="ghost" icon="arrow-up-tray">Import CSV</flux:button>
                </flux:modal.trigger>
                <flux:button variant="filled" icon="sparkles" wire:click="toggleAiPanel">
                    Generate with AI
                </flux:button>
                <flux:button variant="primary" icon="plus" wire:click="openCreate">Add Question</flux:button>
            </div>
        @elseif ($showAiPanel)
            <flux:button variant="ghost" wire:click="toggleAiPanel">Cancel</flux:button>
        @endif
    </div>

    {{-- ── AI Generation Panel ── --}}
    @if ($showAiPanel)
        <div class="bento-flat space-y-4">
            <div class="flex items-center gap-2">
                <flux:icon.sparkles class="text-teal-600 size-5" />
                <flux:heading size="lg">Generate Questions with AI</flux:heading>
            </div>

            @if ($aiError)
                <flux:callout variant="danger" icon="x-circle" heading="{{ $aiError }}" />
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>Topic</flux:label>
                    <flux:input
                        wire:model="aiTopic"
                        placeholder="e.g. PHP arrays, World War II, Photosynthesis…"
                        autofocus
                    />
                    <flux:error name="aiTopic" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>Question mix</flux:label>
                    <flux:description>Select types and how many of each (max 10 per type, 20 total). The same mix is used for AI generation and for random picks from your other exams.</flux:description>
                    <flux:error name="aiMix" />
                    <flux:error name="aiBank" />
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="flex flex-col gap-2 rounded-xl border border-zinc-200/80 bg-white/60 p-3 dark:border-zinc-700 dark:bg-zinc-900/40">
                            <label class="flex cursor-pointer items-center gap-2 select-none">
                                <input
                                    type="checkbox"
                                    wire:model.live="aiMixMultipleChoice"
                                    class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                                />
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Multiple Choice</span>
                            </label>
                            <flux:input
                                wire:model="aiCountMultipleChoice"
                                type="number"
                                min="1"
                                max="10"
                                class="w-full"
                                @disabled(! $aiMixMultipleChoice)
                            />
                            <flux:error name="aiCountMultipleChoice" />
                        </div>
                        <div class="flex flex-col gap-2 rounded-xl border border-zinc-200/80 bg-white/60 p-3 dark:border-zinc-700 dark:bg-zinc-900/40">
                            <label class="flex cursor-pointer items-center gap-2 select-none">
                                <input
                                    type="checkbox"
                                    wire:model.live="aiMixTrueFalse"
                                    class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                                />
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">True / False</span>
                            </label>
                            <flux:input
                                wire:model="aiCountTrueFalse"
                                type="number"
                                min="1"
                                max="10"
                                class="w-full"
                                @disabled(! $aiMixTrueFalse)
                            />
                            <flux:error name="aiCountTrueFalse" />
                        </div>
                        <div class="flex flex-col gap-2 rounded-xl border border-zinc-200/80 bg-white/60 p-3 dark:border-zinc-700 dark:bg-zinc-900/40">
                            <label class="flex cursor-pointer items-center gap-2 select-none">
                                <input
                                    type="checkbox"
                                    wire:model.live="aiMixShortAnswer"
                                    class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                                />
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Short Answer</span>
                            </label>
                            <flux:input
                                wire:model="aiCountShortAnswer"
                                type="number"
                                min="1"
                                max="10"
                                class="w-full"
                                @disabled(! $aiMixShortAnswer)
                            />
                            <flux:error name="aiCountShortAnswer" />
                        </div>
                    </div>
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>Difficulty (1 = Easy · 5 = Expert)</flux:label>
                    <flux:select wire:model="aiDifficulty">
                        <flux:select.option value="1">1 — Very Easy</flux:select.option>
                        <flux:select.option value="2">2 — Easy</flux:select.option>
                        <flux:select.option value="3">3 — Medium</flux:select.option>
                        <flux:select.option value="4">4 — Hard</flux:select.option>
                        <flux:select.option value="5">5 — Expert</flux:select.option>
                    </flux:select>
                    <flux:error name="aiDifficulty" />
                </flux:field>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    <flux:button
                        variant="primary"
                        wire:click="streamGenerateWithAi"
                        wire:loading.attr="disabled"
                        wire:target="streamGenerateWithAi"
                    >
                        <span wire:loading.remove wire:target="streamGenerateWithAi" class="inline-flex items-center gap-1">
                            <flux:icon.sparkles class="size-4" />
                            Generate with AI
                        </span>
                        <span wire:loading wire:target="streamGenerateWithAi" class="inline-flex items-center gap-1">
                            <svg class="animate-spin size-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                            Generating…
                        </span>
                    </flux:button>

                    <flux:button
                        variant="ghost"
                        icon="rectangle-stack"
                        wire:click="importMixedRandomFromBank"
                        wire:loading.attr="disabled"
                        wire:target="importMixedRandomFromBank streamGenerateWithAi"
                    >
                        <span wire:loading.remove wire:target="importMixedRandomFromBank" class="inline-flex items-center gap-1">
                            Random from question bank
                        </span>
                        <span wire:loading wire:target="importMixedRandomFromBank" class="inline-flex items-center gap-1">
                            <svg class="animate-spin size-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                            Loading…
                        </span>
                    </flux:button>
                </div>

                <flux:text wire:loading wire:target="streamGenerateWithAi" size="sm" class="text-charcoal-400 animate-pulse">
                    Generating questions… please wait.
                </flux:text>
            </div>

            {{-- Skeleton cards shown while generating --}}
            <div wire:loading wire:target="streamGenerateWithAi" class="space-y-3">
                @for ($s = 0; $s < max(min($this->aiMixTotalRequested, 3), 1); $s++)
                    <div class="bento-flat animate-pulse space-y-3 border-teal-100 bg-teal-50/40">
                        <div class="h-4 bg-teal-100 rounded w-3/4"></div>
                        <div class="flex gap-2">
                            <div class="h-3 bg-teal-100 rounded w-16"></div>
                            <div class="h-3 bg-teal-100 rounded w-20"></div>
                        </div>
                        <div class="h-3 bg-teal-100 rounded w-1/2"></div>
                    </div>
                @endfor
            </div>
        </div>
    @endif

    {{-- ── Generation History ── --}}
    @if ($showAiPanel && count($this->aiHistory) > 0 && ! $aiGenerating && count($pendingAiQuestions) === 0)
        <div class="bento-flat space-y-3">
            <flux:heading size="sm">Recent Generations</flux:heading>
            <div class="space-y-2">
                @foreach ($this->aiHistory as $hi => $batch)
                    <div class="flex items-center justify-between gap-3 py-2 border-b border-gray-100 last:border-0">
                        <div class="flex items-center gap-3">
                            <flux:badge size="sm" color="zinc">{{ $batch['generated_at'] }}</flux:badge>
                            <flux:text size="sm">
                                <strong>{{ $batch['topic'] }}</strong>
                                · {{ $batch['count'] }} question(s)
                                · {{ $batch['type_label'] ?? (isset($batch['type']) ? QuestionType::from($batch['type'])->label() : 'Mixed') }}
                            </flux:text>
                        </div>
                        <flux:button size="sm" variant="ghost" wire:click="loadFromHistory({{ $hi }})">
                            Re-use
                        </flux:button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Pending AI Questions ── --}}
    @if (count($pendingAiQuestions) > 0)
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg">Review Generated Questions</flux:heading>
                </div>
                <flux:button variant="primary" size="sm" wire:click="confirmAllAiQuestions">
                    <span wire:loading.remove wire:target="confirmAllAiQuestions">Add All ({{ count($pendingAiQuestions) }})</span>
                    <span wire:loading wire:target="confirmAllAiQuestions" class="inline-flex items-center gap-1">
                        <svg class="animate-spin size-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        Adding…
                    </span>
                </flux:button>
            </div>

            @foreach ($pendingAiQuestions as $i => $pq)
                <div
                    class="bento-flat space-y-2 border-teal-200 bg-teal-50"
                    wire:key="pending-{{ $i }}"
                    x-data x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                >
                    <div class="flex items-start justify-between gap-3">
                        <flux:text class="font-medium">{{ $pq['question'] }}</flux:text>
                        <flux:badge size="sm" color="teal">AI</flux:badge>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <flux:badge size="sm" color="blue">
                            {{ QuestionType::tryFromAi($pq['type'] ?? '')?->label() ?? $pq['type'] }}
                        </flux:badge>
                        <flux:badge size="sm" color="zinc">Difficulty: {{ $pq['difficulty'] ?? '?' }}</flux:badge>
                        <flux:text size="sm" class="text-zinc-500">Correct: {{ $pq['correct_answer'] }}</flux:text>
                    </div>

                    @if (!empty($pq['options']))
                        <div class="flex flex-wrap gap-1">
                            @foreach ($pq['options'] as $opt)
                                <flux:badge size="sm" :color="$opt === $pq['correct_answer'] ? 'green' : 'zinc'">
                                    {{ $opt }}
                                </flux:badge>
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($pq['explanation']))
                        <flux:text size="sm" class="text-zinc-500 italic">{{ $pq['explanation'] }}</flux:text>
                    @endif

                    <div class="flex items-center gap-2 pt-1">
                        <flux:button size="sm" variant="primary" wire:click="confirmAiQuestion({{ $i }})">
                            Add to Exam
                        </flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="discardAiQuestion({{ $i }})">
                            Discard
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Manual Question Form ── --}}
    @if ($showForm)
        <div class="bento-flat space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit Question' : 'New Question' }}</flux:heading>

            <flux:field>
                <flux:label>Question</flux:label>
                <flux:textarea wire:model="question" placeholder="Enter your question…" rows="3" autofocus />
                <flux:error name="question" />
            </flux:field>

            <flux:field>
                <flux:label>Type</flux:label>
                <flux:select wire:model.live="type">
                    @foreach ($this->questionTypes as $qType)
                        <flux:select.option :value="$qType->value">{{ $qType->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="type" />
            </flux:field>

            @if ($this->currentTypeHasOptions)
                <div class="space-y-2">
                    <flux:label>Answer Options</flux:label>
                    @foreach ($options as $i => $option)
                        <flux:input
                            wire:model="options.{{ $i }}"
                            placeholder="Option {{ $i + 1 }}"
                            :key="'opt-'.$i"
                        />
                    @endforeach
                    <flux:error name="options" />
                </div>
            @endif

            <flux:field>
                <flux:label>Correct Answer</flux:label>
                <flux:input wire:model="correct_answer" placeholder="Enter the correct answer exactly as written" />
                <flux:error name="correct_answer" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancel">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveQuestion">
                    {{ $editingId ? 'Update Question' : 'Add Question' }}
                </flux:button>
            </div>
        </div>
    @endif

    {{-- ── CSV Import Modal ── --}}
    <flux:modal name="csv-import">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Import Questions from CSV</flux:heading>
                <flux:text>
                    Upload a CSV with columns:
                    <code class="text-xs bg-zinc-100 px-1 rounded">question, type, options, correct_answer</code>.
                    Options for multiple-choice should be pipe-separated (e.g. <code class="text-xs bg-zinc-100 px-1 rounded">A|B|C|D</code>).
                    Malformed rows are skipped.
                </flux:text>
            </div>

            <flux:field>
                <flux:label>CSV File</flux:label>
                <flux:input type="file" wire:model="csvFile" accept=".csv,.txt" />
                <flux:error name="csvFile" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="importCsv" icon="arrow-up-tray">
                    <span wire:loading.remove wire:target="importCsv">Import</span>
                    <span wire:loading wire:target="importCsv" class="inline-flex items-center gap-1">
                        <svg class="animate-spin size-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        Importing…
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ── Question List ── --}}
    <div class="space-y-3">
        @if ($this->questions->isNotEmpty())
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        wire:model.live="selectAll"
                        class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                    />
                    <flux:text size="sm">Select All</flux:text>
                </label>
                @if (count($selectedQuestions) > 0)
                    <flux:modal.trigger name="confirm-bulk-delete">
                        <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500">
                            Delete Selected ({{ count($selectedQuestions) }})
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        @endif

        @forelse ($this->questions as $q)
            <div class="bento-flat flex items-start gap-4" wire:key="question-{{ $q->id }}">
                <div class="flex items-center pt-0.5 shrink-0">
                    <input
                        type="checkbox"
                        wire:model.live="selectedQuestions"
                        value="{{ $q->id }}"
                        class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                    />
                </div>
                <div class="flex-1 space-y-1">
                    <flux:text class="font-medium">{{ $q->question }}</flux:text>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" color="blue">{{ $q->type->label() }}</flux:badge>
                        <flux:text size="sm" class="text-zinc-500">Correct: {{ $q->correct_answer }}</flux:text>
                    </div>
                    @if ($q->options)
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach ($q->options as $opt)
                                <flux:badge size="sm" :color="$opt === $q->correct_answer ? 'green' : 'zinc'">
                                    {{ $opt }}
                                </flux:badge>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editQuestion({{ $q->id }})" />
                    <flux:button size="sm" variant="ghost" icon="trash" class="text-red-500" wire:click="deleteQuestion({{ $q->id }})" />
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed p-10 text-center" style="border-color:var(--color-border-hover)">
                <flux:text>No questions yet. Add your first question above.</flux:text>
            </div>
        @endforelse
    </div>

    {{-- ── Confirm Bulk Delete Modal ── --}}
    <flux:modal name="confirm-bulk-delete" class="max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Delete Selected Questions</flux:heading>
                <flux:text class="mt-1">
                    Are you sure you want to delete {{ count($selectedQuestions) }} question(s)? This cannot be undone.
                </flux:text>
            </div>
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" class="!bg-red-600 hover:!bg-red-700" wire:click="deleteSelected">
                    Delete
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
