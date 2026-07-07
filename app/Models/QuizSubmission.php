<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quiz_id',
    'student_id',
    'score',
    'submitted_at',
    'questions_order',
    'options_order',
    'answers',
])]
class QuizSubmission extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quiz_id' => 'integer',
            'student_id' => 'integer',
            'score' => 'integer',
            'submitted_at' => 'datetime',
            'questions_order' => 'array',
            'options_order' => 'array',
            'answers' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
