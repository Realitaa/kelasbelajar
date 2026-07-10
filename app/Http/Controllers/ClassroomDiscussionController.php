<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassroomCommentRequest;
use App\Http\Requests\UpdateClassroomCommentRequest;
use App\Models\Classroom;
use App\Models\ClassroomComment;
use App\Services\ClassroomCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ClassroomDiscussionController extends Controller
{
    /**
     * Display the discussion forum page for the classroom.
     */
    public function index(Request $request, Classroom $classroom, ClassroomCommentService $commentService): InertiaResponse
    {
        Gate::authorize('view', $classroom);

        $moduleId = $request->query('module_id');

        return Inertia::render('classrooms/Discussion', [
            'classroom' => $classroom,
            'modules' => $classroom->modules()->get(['id', 'title']),
            'comments' => $commentService->getCommentsForClassroom($classroom, $moduleId),
            'selectedModuleId' => $moduleId ?? 'all',
        ]);
    }

    /**
     * Store a new comment or reply in the classroom discussion.
     */
    public function storeComment(StoreClassroomCommentRequest $request, Classroom $classroom, ClassroomCommentService $commentService): RedirectResponse
    {
        try {
            $commentService->createComment($request->user(), $classroom, $request->validated());

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Komentar berhasil dikirim.',
            ]);
        } catch (\InvalidArgumentException $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()->back();
    }

    /**
     * Update an existing comment or reply.
     */
    public function updateComment(UpdateClassroomCommentRequest $request, Classroom $classroom, ClassroomComment $comment, ClassroomCommentService $commentService): RedirectResponse
    {
        if ($comment->classroom_id !== $classroom->id) {
            abort(404);
        }

        $commentService->updateComment($comment, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Komentar berhasil diubah.',
        ]);

        return redirect()->back();
    }

    /**
     * Soft-delete a comment or reply.
     */
    public function destroyComment(Request $request, Classroom $classroom, ClassroomComment $comment, ClassroomCommentService $commentService): RedirectResponse
    {
        if ($comment->classroom_id !== $classroom->id) {
            abort(404);
        }

        Gate::authorize('delete', $comment);

        $commentService->deleteComment($comment);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Komentar berhasil dihapus.',
        ]);

        return redirect()->back();
    }
}
