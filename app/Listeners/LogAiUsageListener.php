<?php

namespace App\Listeners;

use App\Models\AiUsage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\EmbeddingsGenerated;

class LogAiUsageListener
{
    public function handle(AgentPrompted|EmbeddingsGenerated $event): void
    {
    // AI usage logging and budget enforcement disabled for demo mode.
    return;
    }
}
