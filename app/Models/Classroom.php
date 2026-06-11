<?php

namespace App\Models;

use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'educator_id',
    'title',
    'slug',
    'unique_code',
    'description',
    'thumbnail_path',
    'is_published',
])]
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * Get the enrollments for the classroom.
     *
     * @return HasMany<ClassroomEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassroomEnrollment::class);
    }

    /**
     * Get the educator that owns the classroom.
     *
     * @return BelongsTo<User, $this>
     */
    public function educator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educator_id');
    }
}
