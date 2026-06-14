<?php

namespace App\Policies;

use App\Models\ClassroomComment;
use App\Models\User;

class ClassroomCommentPolicy
{
    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, ClassroomComment $classroomComment): bool
    {
        return $user->id === $classroomComment->user_id;
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, ClassroomComment $classroomComment): bool
    {
        // Comment author, classroom educator, or administrator can delete
        return $user->id === $classroomComment->user_id
            || $classroomComment->classroom->educator_id === $user->id
            || $user->role === 'administrator';
    }
}
