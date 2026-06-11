<?php

namespace App\Models;

use Database\Factories\ClassroomEnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'classroom_id',
    'student_id',
    'enrolled_at',
])]
class ClassroomEnrollment extends Model
{
    /** @use HasFactory<ClassroomEnrollmentFactory> */
    use HasFactory;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
