<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    /**
     * Upload an uploaded file as a temporary file and save its metadata.
     */
    public function upload(UploadedFile $file, int $userId): Media
    {
        $uuidName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('tmp', $uuidName, 'public');

        return Media::create([
            'disk' => 'public',
            'path' => $path,
            'filename' => $uuidName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'temporary',
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * Remove the media entry and physically delete the file.
     */
    public function remove(Media $media): bool
    {
        if (Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->delete($media->path);
        }

        return (bool) $media->delete();
    }

    /**
     * Promote a temporary file to an attached file and link it to a parent model.
     */
    public function promote(Media $media, Model $model, string $folder = 'images'): Media
    {
        $newPath = $folder.'/'.$media->filename;

        if (Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->move($media->path, $newPath);
        }

        $media->status = 'attached';
        $media->path = $newPath;
        $media->fileable_type = get_class($model);
        $media->fileable_id = $model->getKey();
        $media->save();

        return $media;
    }
}
