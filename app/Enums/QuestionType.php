<?php

namespace App\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';

    public function label(): string
    {
        return match ($this) {
            QuestionType::MultipleChoice => 'Multiple Choice',
            QuestionType::TrueFalse => 'True / False',
            QuestionType::ShortAnswer => 'Short Answer',
        };
    }

    public function hasOptions(): bool
    {
        return $this === QuestionType::MultipleChoice || $this === QuestionType::TrueFalse;
    }

    /**
     * Resolve a question type from AI or import payloads that may use labels or alternate spellings.
     */
    public static function tryFromAi(null|int|string $raw): ?self
    {
        if ($raw === null) {
            return null;
        }

        $s = trim((string) $raw);

        if ($s === '') {
            return null;
        }

        $direct = self::tryFrom($s);

        if ($direct !== null) {
            return $direct;
        }

        $lower = mb_strtolower($s);
        $compact = preg_replace('/[\s\/_-]+/', '', $lower) ?? $lower;

        return match ($compact) {
            'truefalse', 'boolean', 'bool', 'tf' => self::TrueFalse,
            'multiplechoice', 'mcq', 'multichoice' => self::MultipleChoice,
            'shortanswer', 'freetext', 'freeresponse', 'openended' => self::ShortAnswer,
            default => null,
        };
    }
}
