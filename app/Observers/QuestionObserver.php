<?php

namespace App\Observers;

use App\Models\Question;

class QuestionObserver
{
    public function created(Question $question): void
    {
        // OpenAI embeddings disabled for production deployment
    }

    public function updated(Question $question): void
    {
        // OpenAI embeddings disabled for production deployment
    }
}