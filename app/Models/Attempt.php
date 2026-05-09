<?php

namespace App\Models;

use Database\Factories\AttemptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attempt extends Model
{
    /** @use HasFactory<AttemptFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_id',
        'user_id',
        'answers',
        'score',
        'violations',
        'started_at',
        'completed_at',
        'disqualified_at',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'score' => 'integer',
            'violations' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'disqualified_at' => 'datetime',
            'embedding' => 'array',
        ];
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /** @param Builder<self> $query */
    public function scopeCompleted(Builder $query): void
    {
        $query->whereNotNull('completed_at');
    }

    /** @return BelongsTo<Exam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
