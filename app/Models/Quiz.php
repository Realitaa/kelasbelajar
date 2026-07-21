<?php

namespace App\Models;

use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'title',
    'description',
    'created_by',
    'passing_grade',
    'time_limit',
    'max_attempts',
    'min_attempts_for_solution',
])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'passing_grade' => 'integer',
            'time_limit' => 'integer',
            'max_attempts' => 'integer',
            'min_attempts_for_solution' => 'integer',
        ];
    }

    public function getAttemptsCount(int $studentId): int
    {
        return QuizSubmission::where('quiz_id', $this->id)
            ->where('student_id', $studentId)
            ->count();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return MorphMany<ModuleObject, $this>
     */
    public function moduleObjects(): MorphMany
    {
        return $this->morphMany(ModuleObject::class, 'object');
    }

    /**
     * @return HasMany<QuizQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }
}
