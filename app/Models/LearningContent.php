<?php

namespace App\Models;

use App\Services\FileService;
use Database\Factories\LearningContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'title',
    'content',
    'created_by',
])]
class LearningContent extends Model
{
    /** @use HasFactory<LearningContentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (LearningContent $learningContent) {
            $fileService = app(FileService::class);
            foreach ($learningContent->media as $media) {
                $fileService->remove($media);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'content' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return MorphMany<ModuleObject, $this>
     */
    public function moduleObjects(): MorphMany
    {
        return $this->morphMany(ModuleObject::class, 'object');
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'fileable');
    }
}
