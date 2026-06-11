<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ClassroomService
{
    /**
     * Get classrooms for the given user based on their role.
     *
     * @return Collection<int, Classroom>
     */
    public function getClassroomsForUser(User $user): Collection
    {
        if ($user->role === 'educator') {
            return Classroom::where('educator_id', $user->id)
                ->latest()
                ->get();
        }

        return collect();
    }

    /**
     * Create a new classroom.
     *
     * @param  array{title: string, description?: string|null}  $data
     */
    public function createClassroom(User $user, array $data): Classroom
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Classroom::where('unique_code', $code)->exists());

        $slug = Str::slug($data['title']).'-'.strtolower($code);

        return Classroom::create([
            'educator_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'unique_code' => $code,
            'slug' => $slug,
            'is_published' => false,
        ]);
    }

    /**
     * Update an existing classroom.
     *
     * @param  array{title?: string, description?: string|null}  $data
     */
    public function updateClassroom(Classroom $classroom, array $data): Classroom
    {
        $classroom->fill(array_filter([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
        ], fn ($value) => ! is_null($value)));

        if ($classroom->isDirty('title')) {
            $classroom->slug = Str::slug($classroom->title).'-'.strtolower($classroom->unique_code);
        }

        $classroom->save();

        return $classroom;
    }

    /**
     * Publish the classroom.
     */
    public function publishClassroom(Classroom $classroom): bool
    {
        return $classroom->update(['is_published' => true]);
    }

    /**
     * Unpublish the classroom.
     */
    public function unpublishClassroom(Classroom $classroom): bool
    {
        return $classroom->update(['is_published' => false]);
    }

    /**
     * Delete (soft-delete) the classroom.
     */
    public function deleteClassroom(Classroom $classroom): bool
    {
        return (bool) $classroom->delete();
    }
}
