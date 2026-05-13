<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessGeneratedQuestionsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array<int, array<string, mixed>>  $questions
     */
    public function __construct(
        public readonly int $examId,
        public readonly array $questions,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exam = Exam::findOrFail($this->examId);
        $nextOrder = $exam->questions()->max('order') + 1;

        foreach ($this->questions as $data) {
            if (empty($data['question']) || empty($data['correct_answer'])) {
                continue;
            }

            $resolved = Question::resolveFromAiPayload($data);

            $exam->questions()->create([
                'question' => $data['question'],
                'type' => $resolved['type']->value,
                'options' => $resolved['options'],
                'correct_answer' => $data['correct_answer'],
                'order' => $nextOrder++,
            ]);
        }
    }
}
