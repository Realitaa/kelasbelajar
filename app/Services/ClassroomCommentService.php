<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\ClassroomComment;
use App\Models\User;
use Illuminate\Support\Collection;

class ClassroomCommentService
{
    public function __construct(protected TiptapSanitizer $sanitizer) {}

    /**
     * Get discussion forum comments for the given classroom.
     *
     * @return Collection<int, ClassroomComment>
     */
    public function getCommentsForClassroom(Classroom $classroom): Collection
    {
        $comments = ClassroomComment::withTrashed()
            ->where('classroom_id', $classroom->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,email,role',
                'replies' => function ($query) {
                    $query->withTrashed()->with('user:id,name,email,role')->orderBy('created_at', 'desc');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $user = auth()->user();
        $isEducatorOrAdmin = $user && ($user->id === $classroom->educator_id || $user->role === 'administrator');

        if (! $isEducatorOrAdmin) {
            // Prevent exposing original content for soft-deleted comments/replies to regular users
            $comments->each(function (ClassroomComment $comment) {
                if ($comment->trashed()) {
                    $comment->content = [];
                }
                $comment->replies->each(function (ClassroomComment $reply) {
                    if ($reply->trashed()) {
                        $reply->content = [];
                    }
                });
            });
        }

        return $comments;
    }

    /**
     * Create a new comment or reply.
     *
     * @param  array{content: array, parent_id?: int|null}  $data
     */
    public function createComment(User $user, Classroom $classroom, array $data): ClassroomComment
    {
        $parentId = $data['parent_id'] ?? null;

        if ($parentId !== null) {
            $parent = ClassroomComment::findOrFail($parentId);
            if ($parent->parent_id !== null) {
                throw new \InvalidArgumentException('Cannot reply to a reply.');
            }
        }

        return ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'content' => $this->sanitizer->sanitize($data['content']) ?? [],
        ]);
    }

    /**
     * Update an existing comment or reply.
     *
     * @param  array{content: array}  $data
     */
    public function updateComment(ClassroomComment $comment, array $data): ClassroomComment
    {
        $comment->update([
            'content' => $this->sanitizer->sanitize($data['content']) ?? [],
        ]);

        return $comment;
    }

    /**
     * Delete (soft-delete) a comment or reply.
     */
    public function deleteComment(ClassroomComment $comment): bool
    {
        return (bool) $comment->delete();
    }
}
