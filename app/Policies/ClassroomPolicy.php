<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    /**
     * Determine whether the user can view any classrooms.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the classroom.
     */
    public function view(User $user, Classroom $classroom): bool
    {
        if ($user->role === 'educator') {
            return $classroom->educator_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create classrooms.
     */
    public function create(User $user): bool
    {
        return $user->role === 'educator';
    }

    /**
     * Determine whether the user can update the classroom.
     */
    public function update(User $user, Classroom $classroom): bool
    {
        return $user->role === 'educator' && $classroom->educator_id === $user->id;
    }

    /**
     * Determine whether the user can delete the classroom.
     */
    public function delete(User $user, Classroom $classroom): bool
    {
        return $user->role === 'educator' && $classroom->educator_id === $user->id;
    }

    /**
     * Determine whether the user can publish the classroom.
     */
    public function publish(User $user, Classroom $classroom): bool
    {
        return $user->role === 'educator' && $classroom->educator_id === $user->id;
    }

    /**
     * Determine whether the user can unpublish the classroom.
     */
    public function unpublish(User $user, Classroom $classroom): bool
    {
        return $user->role === 'educator'
            && $classroom->educator_id === $user->id
            && ! $classroom->enrollments()->exists();
    }
}
