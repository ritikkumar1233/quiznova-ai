<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttemptViolationController extends Controller
{
    private const MAX_VIOLATIONS = 3;

    public function __invoke(Request $request, Attempt $attempt): JsonResponse
    {
        abort_unless($request->user()?->id === $attempt->user_id, 403);
        abort_if($attempt->isCompleted(), 422, 'Attempt has already been submitted.');

        $validated = $request->validate([
            'event' => ['required', 'string', 'in:fullscreen_exit,tab_switch'],
        ]);

        try {
            $attempt = DB::transaction(function () use ($attempt): Attempt {
                /** @var Attempt $lockedAttempt */
                $lockedAttempt = Attempt::query()->lockForUpdate()->findOrFail($attempt->id);

                if ($lockedAttempt->disqualified_at === null) {
                    $lockedAttempt->increment('violations');
                    $lockedAttempt->refresh();

                    if ($lockedAttempt->violations >= self::MAX_VIOLATIONS) {
                        $lockedAttempt->update([
                            'disqualified_at' => now(),
                        ]);
                        $lockedAttempt->refresh();
                    }
                }

                return $lockedAttempt;
            });
        } catch (\Throwable $e) {
            logger()->error('Violation sync failed', ['error' => $e->getMessage(), 'attempt_id' => $attempt->id]);

            // Return a recoverable response so the client can still enforce fullscreen behavior locally.
            return response()->json([
                'event' => $validated['event'],
                'violations' => null,
                'max_violations' => self::MAX_VIOLATIONS,
                'must_submit' => false,
                'message' => 'Violation could not be synced right now. Fullscreen violation recorded locally.',
            ], 503);
        }

        $mustSubmit = $attempt->violations >= self::MAX_VIOLATIONS;
        $event = $validated['event'];
        $baseMessage = $event === 'tab_switch'
            ? 'Tab switch violation recorded.'
            : 'Fullscreen violation recorded.';

        return response()->json([
            'event' => $event,
            'violations' => $attempt->violations,
            'max_violations' => self::MAX_VIOLATIONS,
            'must_submit' => $mustSubmit,
            'message' => $mustSubmit
                ? 'You have been disqualified due to multiple violations.'
                : $baseMessage,
        ]);
    }
}
