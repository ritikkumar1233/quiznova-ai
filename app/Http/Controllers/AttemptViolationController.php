<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AttemptViolationController extends Controller
{
    private const MAX_VIOLATIONS = 3;

    public function __invoke(Request $request, Attempt $attempt): JsonResponse
    {
        abort_unless($request->user()?->id === $attempt->user_id, 403);
        abort_if($attempt->isCompleted(), 422, 'Attempt has already been submitted.');

        $validated = $request->validate([
            'event' => ['required', 'string', 'in:fullscreen_exit'],
        ]);

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

        $mustSubmit = $attempt->violations >= self::MAX_VIOLATIONS;

        return response()->json([
            'event' => $validated['event'],
            'violations' => $attempt->violations,
            'max_violations' => self::MAX_VIOLATIONS,
            'must_submit' => $mustSubmit,
            'message' => $mustSubmit
                ? 'You have been disqualified due to multiple violations.'
                : 'Fullscreen violation recorded.',
        ]);
    }
}
