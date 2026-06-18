<?php

use App\Services\TiptapSanitizer;

it('removes text nodes with null text', function () {
    $sanitizer = new TiptapSanitizer;

    $input = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'text',
                'text' => null,
            ],
            [
                'type' => 'text',
                'text' => 'valid text',
            ],
        ],
    ];

    $expected = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'text',
                'text' => 'valid text',
            ],
        ],
    ];

    expect($sanitizer->sanitize($input))->toBe($expected);
});

it('removes text nodes with empty string text', function () {
    $sanitizer = new TiptapSanitizer;

    $input = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'text',
                'text' => '',
            ],
        ],
    ];

    $expected = [
        'type' => 'doc',
        'content' => [],
    ];

    expect($sanitizer->sanitize($input))->toBe($expected);
});

it('removes nodes without type', function () {
    $sanitizer = new TiptapSanitizer;

    $input = [
        'type' => 'doc',
        'content' => [
            [
                'text' => 'missing type',
            ],
        ],
    ];

    $expected = [
        'type' => 'doc',
        'content' => [],
    ];

    expect($sanitizer->sanitize($input))->toBe($expected);
});

it('sanitizes deeply nested invalid nodes', function () {
    $sanitizer = new TiptapSanitizer;

    $input = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => null,
                        'marks' => [
                            ['type' => 'italic'],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => 'valid',
                    ],
                ],
            ],
        ],
    ];

    $expected = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'valid',
                    ],
                ],
            ],
        ],
    ];

    expect($sanitizer->sanitize($input))->toBe($expected);
});

it('returns null if input is not valid JSON array or string', function () {
    $sanitizer = new TiptapSanitizer;

    expect($sanitizer->sanitize(null))->toBeNull();
    expect($sanitizer->sanitize('not json'))->toBeNull();
    expect($sanitizer->sanitize(123))->toBeNull();
});

it('accepts JSON string and returns sanitized array', function () {
    $sanitizer = new TiptapSanitizer;

    $input = json_encode([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'text',
                'text' => null,
            ],
            [
                'type' => 'text',
                'text' => 'ok',
            ],
        ],
    ]);

    $expected = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'text',
                'text' => 'ok',
            ],
        ],
    ];

    expect($sanitizer->sanitize($input))->toBe($expected);
});
