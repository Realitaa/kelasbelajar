<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLearningContentRequest;
use App\Models\Classroom;
use App\Models\LearningContent;
use App\Services\LearningContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ClassroomLearningContentController extends Controller
{
    public function show(Request $request, Classroom $classroom, LearningContent $learningContent): JsonResponse
    {
        Gate::authorize('update', $classroom);

        return response()->json([
            'data' => [
                'id' => $learningContent->id,
                'content' => $learningContent->content,
            ],
        ]);
    }

    public function updateContent(UpdateLearningContentRequest $request, Classroom $classroom, LearningContent $learningContent, LearningContentService $learningContentService): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        $validated = $request->validated();

        $learningContent->update([
            'content' => $validated['content'],
        ]);

        $learningContentService->promoteMediaFromContent($learningContent, $validated['content']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Konten materi berhasil disimpan.',
        ]);

        return redirect()->back();
    }
}
