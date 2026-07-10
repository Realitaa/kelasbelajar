<?php

namespace App\Models;

use Database\Factories\ClassroomCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'classroom_id',
    'user_id',
    'parent_id',
    'module_id',
    'content',
])]
class ClassroomComment extends Model
{
    /** @use HasFactory<ClassroomCommentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'classroom_id' => 'integer',
            'user_id' => 'integer',
            'parent_id' => 'integer',
            'module_id' => 'integer',
            'content' => 'array',
        ];
    }

    /**
     * Get the classroom that owns the comment.
     *
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the module associated with the comment.
     *
     * @return BelongsTo<ClassroomModule, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(ClassroomModule::class, 'module_id');
    }

    /**
     * Get the user that wrote the comment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment.
     *
     * @return BelongsTo<ClassroomComment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ClassroomComment::class, 'parent_id');
    }

    /**
     * Get the replies for the comment.
     *
     * @return HasMany<ClassroomComment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ClassroomComment::class, 'parent_id')->orderBy('created_at', 'desc');
    }
}
