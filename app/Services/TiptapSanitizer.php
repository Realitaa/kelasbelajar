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
        // Node Tiptap yang valid wajib memiliki tipe bernilai string
        if (! isset($node['type']) || ! is_string($node['type'])) {
            return null;
        }

        // 1. Handling Text Node
        if ($node['type'] === 'text') {
            // Text node wajib memiliki properti 'text' berupa string non-kosong
            if (! isset($node['text']) || ! is_string($node['text']) || $node['text'] === '') {
                return null;
            }

            // Sanitisasi marks (seperti bold, italic, link) jika ada
            if (isset($node['marks']) && is_array($node['marks'])) {
                $sanitizedMarks = array_values(array_filter($node['marks'], function ($mark) {
                    return is_array($mark) && isset($mark['type']) && is_string($mark['type']);
                }));

                if (! empty($sanitizedMarks)) {
                    $node['marks'] = $sanitizedMarks;
                } else {
                    unset($node['marks']);
                }
            }

            return $node;
        }

        // 2. Handling Children (Content)
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

                // Jika setelah dibersihkan content-nya kosong, hapus key 'content'
                // Tiptap menerima {"type": "paragraph"} sebagai paragraf kosong yang valid
                if (empty($sanitizedContent)) {
                    unset($node['content']);
                } else {
                    // Re-index array agar di-encode sebagai JSON Array [...] bukan JSON Object {}
                    $node['content'] = array_values($sanitizedContent);
                }
            } else {
                unset($node['content']);
            }
        }

        return $node;
    }
}
