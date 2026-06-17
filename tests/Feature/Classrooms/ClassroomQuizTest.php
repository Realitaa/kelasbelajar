<?php

use App\Models\Classroom;
use App\Models\ClassroomModule;
use App\Models\ModuleObject;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;

describe('Authorization & Access Control', function () {
    it('allows the classroom owner educator to view quiz questions', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $response = $this->actingAs($educator)->get(route('classrooms.quizzes.show', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]));

        $response->assertOk();
    });

    it('prevents non-owner educators from viewing quiz questions', function () {
        $educator1 = User::factory()->create(['role' => 'educator']);
        $educator2 = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator1->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator1->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $response = $this->actingAs($educator2)->get(route('classrooms.quizzes.show', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]));

        $response->assertForbidden();
    });

    it('prevents students from viewing quiz questions', function () {
        $student = User::factory()->create(['role' => 'student']);
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $response = $this->actingAs($student)->get(route('classrooms.quizzes.show', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]));

        $response->assertForbidden();
    });

    it('prevents guests from accessing quiz questions', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $response = $this->get(route('classrooms.quizzes.show', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]));

        $response->assertRedirect(route('login'));
    });
});

describe('Validation & Quiz Business Rules', function () {
    it('validates that a quiz must belong to the specified classroom', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom1 = Classroom::factory()->create(['educator_id' => $educator->id]);
        $classroom2 = Classroom::factory()->create(['educator_id' => $educator->id]);

        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom1->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $response = $this->actingAs($educator)->put(route('classrooms.quizzes.update-questions', [
            'classroom' => $classroom2->slug, // Wrong classroom
            'quiz' => $quiz->id,
        ]), [
            'questions' => [],
        ]);

        $response->assertNotFound();
    });

    it('validates that each question must have at least 2 options', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $response = $this->actingAs($educator)->put(route('classrooms.quizzes.update-questions', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]), [
            'questions' => [
                [
                    'type' => 'PG',
                    'question' => ['type' => 'doc', 'content' => []],
                    'solution' => ['type' => 'doc', 'content' => []],
                    'options' => [
                        ['option' => ['type' => 'doc', 'content' => []], 'is_correct' => true],
                        // Only 1 option
                    ],
                ],
            ],
        ]);

        $response->assertSessionHasErrors('questions.0.options');
    });

    it('validates that exactly one option per question must be marked correct', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $response = $this->actingAs($educator)->put(route('classrooms.quizzes.update-questions', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]), [
            'questions' => [
                [
                    'type' => 'PG',
                    'question' => ['type' => 'doc', 'content' => []],
                    'solution' => ['type' => 'doc', 'content' => []],
                    'options' => [
                        ['option' => ['type' => 'doc', 'content' => []], 'is_correct' => false],
                        ['option' => ['type' => 'doc', 'content' => []], 'is_correct' => false],
                        // No correct options
                    ],
                ],
            ],
        ]);

        $response->assertSessionHasErrors('questions.0.options');

        $response2 = $this->actingAs($educator)->put(route('classrooms.quizzes.update-questions', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]), [
            'questions' => [
                [
                    'type' => 'PG',
                    'question' => ['type' => 'doc', 'content' => []],
                    'solution' => ['type' => 'doc', 'content' => []],
                    'options' => [
                        ['option' => ['type' => 'doc', 'content' => []], 'is_correct' => true],
                        ['option' => ['type' => 'doc', 'content' => []], 'is_correct' => true],
                        // Multiple correct options
                    ],
                ],
            ],
        ]);

        $response2->assertSessionHasErrors('questions.0.options');
    });

    it('allows PG MCMA and PG K to have multiple correct answers', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $response = $this->actingAs($educator)->put(route('classrooms.quizzes.update-questions', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]), [
            'questions' => [
                [
                    'type' => 'PG MCMA',
                    'question' => ['type' => 'doc', 'content' => []],
                    'options' => [
                        ['option' => ['type' => 'doc', 'content' => []], 'is_correct' => true],
                        ['option' => ['type' => 'doc', 'content' => []], 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'PG K',
                    'question' => ['type' => 'doc', 'content' => []],
                    'options' => [
                        ['option' => ['type' => 'doc', 'content' => []], 'is_correct' => false],
                        ['option' => ['type' => 'doc', 'content' => []], 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertSessionDoesntHaveErrors();
    });
});

describe('Sync & Database Integrity', function () {
    it('saves new questions and options successfully', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $questionJson = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Test Question']]]]];
        $option1Json = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Option 1']]]]];
        $option2Json = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Option 2']]]]];

        $response = $this->actingAs($educator)->put(route('classrooms.quizzes.update-questions', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]), [
            'questions' => [
                [
                    'type' => 'PG',
                    'question' => $questionJson,
                    'solution' => null,
                    'options' => [
                        ['option' => $option1Json, 'is_correct' => true],
                        ['option' => $option2Json, 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('quiz_questions', 1);
        $this->assertDatabaseCount('quiz_options', 2);

        $question = QuizQuestion::first();
        expect($question->question)->toEqual($questionJson);
        expect($question->position)->toBe(1);

        $this->assertDatabaseHas('quiz_options', [
            'question_id' => $question->id,
            'is_correct' => 1,
        ]);

        $this->assertDatabaseHas('quiz_options', [
            'question_id' => $question->id,
            'is_correct' => 0,
        ]);
    });

    it('updates existing questions and options while preserving database IDs', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'PG',
            'question' => ['type' => 'doc'],
            'solution' => ['type' => 'doc', 'text' => 'old solution'],
            'position' => 1,
        ]);

        $option1 = QuizOption::create([
            'question_id' => $question->id,
            'option' => ['type' => 'doc', 'text' => 'old 1'],
            'is_correct' => true,
        ]);

        $option2 = QuizOption::create([
            'question_id' => $question->id,
            'option' => ['type' => 'doc', 'text' => 'old 2'],
            'is_correct' => false,
        ]);

        $newQuestionJson = ['type' => 'doc', 'text' => 'new question'];
        $newOption1Json = ['type' => 'doc', 'text' => 'new option 1'];

        $response = $this->actingAs($educator)->put(route('classrooms.quizzes.update-questions', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]), [
            'questions' => [
                [
                    'id' => $question->id,
                    'type' => 'PG',
                    'question' => $newQuestionJson,
                    'solution' => ['type' => 'doc', 'text' => 'new solution'],
                    'options' => [
                        ['id' => $option1->id, 'option' => $newOption1Json, 'is_correct' => false],
                        ['id' => $option2->id, 'option' => ['type' => 'doc', 'text' => 'old 2'], 'is_correct' => true],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('quiz_questions', 1);
        $this->assertDatabaseCount('quiz_options', 2);

        $question->refresh();
        expect($question->question)->toEqual($newQuestionJson);

        $option1->refresh();
        expect($option1->option)->toEqual($newOption1Json);
        expect($option1->is_correct)->toBeFalse();

        $option2->refresh();
        expect($option2->is_correct)->toBeTrue();
    });

    it('deletes removed questions and options from the database', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $question1 = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'PG',
            'question' => ['type' => 'doc'],
            'position' => 1,
        ]);

        $question2 = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'PG',
            'question' => ['type' => 'doc'],
            'position' => 2,
        ]);

        $option1 = QuizOption::create([
            'question_id' => $question1->id,
            'option' => ['type' => 'doc'],
            'is_correct' => true,
        ]);

        $option2 = QuizOption::create([
            'question_id' => $question1->id,
            'option' => ['type' => 'doc'],
            'is_correct' => false,
        ]);

        $response = $this->actingAs($educator)->put(route('classrooms.quizzes.update-questions', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]), [
            'questions' => [
                [
                    'id' => $question1->id,
                    'type' => 'PG',
                    'question' => ['type' => 'doc'],
                    'options' => [
                        ['id' => $option1->id, 'option' => ['type' => 'doc'], 'is_correct' => true],
                        ['option' => ['type' => 'doc'], 'is_correct' => false], // New option replacing option2
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseMissing('quiz_questions', ['id' => $question2->id]);
        $this->assertDatabaseMissing('quiz_options', ['id' => $option2->id]);
        $this->assertDatabaseCount('quiz_questions', 1);
        $this->assertDatabaseCount('quiz_options', 2);
    });

    it('reorders question positions sequentially on update', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $quiz = Quiz::factory()->create(['created_by' => $educator->id]);
        ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
        ]);

        $question1 = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'PG',
            'question' => ['type' => 'doc'],
            'position' => 1,
        ]);

        $question2 = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => 'PG',
            'question' => ['type' => 'doc'],
            'position' => 2,
        ]);

        $response = $this->actingAs($educator)->put(route('classrooms.quizzes.update-questions', [
            'classroom' => $classroom->slug,
            'quiz' => $quiz->id,
        ]), [
            'questions' => [
                [
                    'id' => $question2->id,
                    'type' => 'PG',
                    'question' => ['type' => 'doc'],
                    'options' => [
                        ['option' => ['type' => 'doc'], 'is_correct' => true],
                        ['option' => ['type' => 'doc'], 'is_correct' => false],
                    ],
                ],
                [
                    'id' => $question1->id,
                    'type' => 'PG',
                    'question' => ['type' => 'doc'],
                    'options' => [
                        ['option' => ['type' => 'doc'], 'is_correct' => true],
                        ['option' => ['type' => 'doc'], 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();

        $question1->refresh();
        $question2->refresh();

        expect($question2->position)->toBe(1);
        expect($question1->position)->toBe(2);
    });
});
