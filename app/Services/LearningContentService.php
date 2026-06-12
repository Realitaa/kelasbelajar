<?php

namespace App\Services;

use App\Models\LearningContent;
use App\Models\Media;

class LearningContentService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected FileService $fileService
    ) {}

    /**
     * Process learning content JSON and promote temporary media files
     */
    public function promoteMediaFromContent(LearningContent $learningContent, array|string|null $content): void
    {
        if (empty($content)) {
            return;
        }

        $contentArray = is_string($content) ? json_decode($content, true) : $content;

        if (! is_array($contentArray)) {
            return;
        }

        $mediaIds = [];
        $this->extractMediaIds($contentArray, $mediaIds);

        if (empty($mediaIds)) {
            return;
        }

        $mediaFiles = Media::whereIn('id', array_unique($mediaIds))
            ->where('status', 'temporary')
            ->get();

        foreach ($mediaFiles as $media) {
            $this->fileService->promote($media, $learningContent, 'images');
        }
    }

    /**
     * Recursively extract media IDs from Tiptap JSON node
     */
    protected function extractMediaIds(array $node, array &$ids): void
    {
        if (isset($node['type'])) {
            if ($node['type'] === 'image' && isset($node['attrs']['src'])) {
                if (preg_match('/\/files\/(\d+)/', $node['attrs']['src'], $matches)) {
                    $ids[] = $matches[1];
                }
            } elseif ($node['type'] === 'slideshow' && isset($node['attrs']['images'])) {
                foreach ($node['attrs']['images'] as $image) {
                    if (isset($image['id'])) {
                        $ids[] = $image['id'];
                    }
                }
            }
        }

        if (isset($node['content']) && is_array($node['content'])) {
            foreach ($node['content'] as $child) {
                if (is_array($child)) {
                    $this->extractMediaIds($child, $ids);
                }
            }
        }
    }
}
