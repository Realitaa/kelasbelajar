<?php

namespace App\Services;

class TiptapSanitizer
{
    /**
     * Sanitize Tiptap JSON data.
     */
    public function sanitize(array|string|null $data): ?array
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (! is_array($data)) {
            return null;
        }

        return $this->sanitizeNode($data);
    }

    /**
     * Recursively sanitize a Tiptap node.
     */
    protected function sanitizeNode(array $node): ?array
    {
        // A valid Tiptap node must have a type
        if (! isset($node['type']) || ! is_string($node['type'])) {
            return null;
        }

        // Sanitize text nodes
        if ($node['type'] === 'text') {
            if (! isset($node['text']) || ! is_string($node['text']) || $node['text'] === '') {
                return null;
            }

            return $node;
        }

        // Sanitize children if present
        if (isset($node['content'])) {
            if (is_array($node['content'])) {
                $sanitizedContent = [];
                foreach ($node['content'] as $child) {
                    if (is_array($child)) {
                        $sanitizedChild = $this->sanitizeNode($child);
                        if ($sanitizedChild !== null) {
                            $sanitizedContent[] = $sanitizedChild;
                        }
                    }
                }
                $node['content'] = $sanitizedContent;
            } else {
                unset($node['content']);
            }
        }

        return $node;
    }
}
