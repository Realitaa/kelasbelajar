<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected FileService $fileService
    ) {}

    /**
     * Upload a new file (restricted to images <= 2MB).
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|image|max:2048',
        ]);

        $media = $this->fileService->upload($request->file('file'), $request->user()->id);

        return response()->json([
            'success' => true,
            'file' => [
                'id' => $media->id,
                'filename' => $media->filename,
                'original_name' => $media->original_name,
            ],
        ]);
    }

    /**
     * Remove an existing temporary file.
     */
    public function remove(string|int $id, Request $request): JsonResponse
    {
        $media = Media::find($id);

        if (! $media) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan.',
            ], 404);
        }

        if ($media->uploaded_by !== $request->user()->id) {
            abort(403);
        }

        $this->fileService->remove($media);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Access/stream a file directly.
     */
    public function show(string|int $id): Response
    {
        $media = Media::find($id);

        if (! $media) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan.',
            ]);
        }

        if (! Storage::disk($media->disk)->exists($media->path)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan.',
            ]);
        }

        return Storage::disk($media->disk)->response($media->path);
    }
}
