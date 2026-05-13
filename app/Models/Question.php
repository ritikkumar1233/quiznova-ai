<?php

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_id',
        'question',
        'type',
        'options',
        'correct_answer',
        'order',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'options' => 'array',
            'order' => 'integer',
            'embedding' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class)->withTrashed();
    }

    /**
     * Radio choices for multiple choice / true-false. True/false always yields at least True & False even if options were not stored.
     */
    protected function choiceOptions(): Attribute
    {
        return Attribute::get(function (): ?array {
            if (! $this->type->hasOptions()) {
                return null;
            }

            if ($this->type === QuestionType::TrueFalse) {
                $opts = $this->options;

                if (is_array($opts) && count($opts) >= 2) {
                    return $opts;
                }

                return ['True', 'False'];
            }

            $opts = $this->options;

            return is_array($opts) && $opts !== [] ? $opts : null;
        });
    }

    /**
     * Normalize type + options from an AI (or similar) payload before persisting.
     *
     * @param  array<string, mixed>  $data
     * @return array{type: QuestionType, options: array<int, string>|null}
     */
    public static function resolveFromAiPayload(array $data): array
    {
        $type = QuestionType::tryFromAi($data['type'] ?? null) ?? QuestionType::ShortAnswer;

        $options = null;

        if ($type->hasOptions()) {
            if ($type === QuestionType::TrueFalse) {
                $options = ['True', 'False'];
            } else {
                $raw = $data['options'] ?? null;
                $list = is_array($raw)
                    ? array_values(array_filter($raw, static fn ($o) => $o !== null && $o !== ''))
                    : [];
                $options = $list !== [] ? $list : null;
            }
        }

        return ['type' => $type, 'options' => $options];
    }
}
