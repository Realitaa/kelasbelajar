<?php

use App\Models\Classroom;
use App\Models\ClassroomModule;
use App\Models\ModuleObject;
use App\Models\Quiz;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\put;

it('sanitizes invalid tiptap json payload when updating quiz questions', function () {
    $this->withoutExceptionHandling();
    $educator = User::factory()->create([
        'role' => 'educator',
    ]);

    $classroom = Classroom::factory()->create([
        'educator_id' => $educator->id,
        'is_published' => true,
    ]);

    $module = ClassroomModule::factory()->create([
        'classroom_id' => $classroom->id,
    ]);

    $quiz = Quiz::factory()->create([
        'created_by' => $educator->id,
    ]);

    ModuleObject::factory()->create([
        'module_id' => $module->id,
        'object_id' => $quiz->id,
        'object_type' => Quiz::class,
    ]);

    actingAs($educator);

    $invalidTiptapJson = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => null, // This is the invalid part
                        'marks' => [
                            ['type' => 'italic'],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => 'Valid text',
                    ],
                ],
            ],
        ],
    ];

    $payload = [
        'questions' => [
            [
                'type' => 'PG',
                'question' => $invalidTiptapJson,
                'solution' => null,
                'options' => [
                    [
                        'option' => [
                            'type' => 'doc',
                            'content' => [
                                [
                                    'type' => 'paragraph',
                                    'content' => [
                                        [
                                            'type' => 'text',
                                            'text' => '', // Another invalid part
                                        ],
                                        [
                                            'type' => 'text',
                                            'text' => 'Option A',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'is_correct' => true,
                    ],
                    [
                        'option' => [
                            'type' => 'doc',
                            'content' => [
                                [
                                    'type' => 'paragraph',
                                    'content' => [
                                        [
                                            'type' => 'text',
                                            'text' => 'Option B',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'is_correct' => false,
                    ],
                ],
            ],
        ],
    ];

    $response = put("/classrooms/{$classroom->slug}/quizzes/{$quiz->id}/questions", $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $quiz->refresh();

    $savedQuestion = $quiz->questions()->first();
    expect($savedQuestion)->not->toBeNull();

    // Check if the null text node was removed
    $questionContent = $savedQuestion->question;
    $paragraphContent = $questionContent['content'][0]['content'];

    expect($paragraphContent)->toHaveCount(1);
    expect($paragraphContent[0]['text'])->toBe('Valid text');

    // Check if empty text node in option A was removed
    $savedOptionA = $savedQuestion->options()->where('is_correct', true)->first();
    $optionAContent = $savedOptionA->option;
    $optionAParagraphContent = $optionAContent['content'][0]['content'];

    expect($optionAParagraphContent)->toHaveCount(1);
    expect($optionAParagraphContent[0]['text'])->toBe('Option A');
});
