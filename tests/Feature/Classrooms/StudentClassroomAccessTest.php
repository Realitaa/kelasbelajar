<?php

use App\Models\Classroom;
use App\Models\ClassroomEnrollment;
use App\Models\ClassroomModule;
use App\Models\LearningContent;
use App\Models\ModuleObject;
use App\Models\Quiz;
use App\Models\QuizSubmission;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

describe('Student Classroom Content Access', function () {
    it('allows enrolled students to view the classroom details', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);

        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        actingAs($student)
            ->get(route('classrooms.show', $classroom->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('classrooms/Show')
                ->where('classroom.id', $classroom->id)
            );
    });

    it('prevents non-enrolled students from viewing the classroom', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]); // not enrolled

        actingAs($student)
            ->get(route('classrooms.show', $classroom->slug))
            ->assertForbidden();
    });

    it('enforces sequential access to classroom modules and objects', function () {
        $student = User::factory()->student()->create();
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create([
            'educator_id' => $educator->id,
            'is_published' => true,
        ]);

        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        // Create 2 modules
        $module = ClassroomModule::factory()->create([
            'classroom_id' => $classroom->id,
            'position' => 1,
        ]);

        // Object 1: Learning Content (always completed)
        $lc = LearningContent::factory()->create(['created_by' => $educator->id]);
        $obj1 = ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $lc->id,
            'object_type' => LearningContent::class,
            'position' => 1,
        ]);

        // Object 2: Quiz with passing grade 80
        $quiz = Quiz::factory()->create([
            'created_by' => $educator->id,
            'passing_grade' => 80,
        ]);
        $obj2 = ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
            'position' => 2,
        ]);

        // Object 3: Learning Content after Quiz
        $lc2 = LearningContent::factory()->create(['created_by' => $educator->id]);
        $obj3 = ModuleObject::factory()->create([
            'module_id' => $module->id,
            'object_id' => $lc2->id,
            'object_type' => LearningContent::class,
            'position' => 3,
        ]);

        // Verify initial access:
        // - O_1 (LC) should be accessible
        // - O_2 (Quiz) should be accessible (since O_1 is LC, always completed)
        // - O_3 (LC2) should be LOCKED because Quiz (O_2) has no passing submission
        actingAs($student)
            ->get(route('classrooms.show', $classroom->slug))
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($obj1, $obj2, $obj3) {
                $page->component('classrooms/Show')
                    ->has('classroom.modules.0.objects', 3)
                    ->where('classroom.modules.0.objects.0.id', $obj1->id)
                    ->where('classroom.modules.0.objects.0.can_access', true)
                    ->where('classroom.modules.0.objects.1.id', $obj2->id)
                    ->where('classroom.modules.0.objects.1.can_access', true)
                    ->where('classroom.modules.0.objects.2.id', $obj3->id)
                    ->where('classroom.modules.0.objects.2.can_access', false);
            });

        // Case 1: Student submits quiz with score < passing_grade (e.g. 70)
        $submission = QuizSubmission::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => 70,
            'submitted_at' => now(),
        ]);

        // O_3 should still be locked
        actingAs($student)
            ->get(route('classrooms.show', $classroom->slug))
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $page->component('classrooms/Show')
                    ->where('classroom.modules.0.objects.2.can_access', false);
            });

        // Case 2: Student submits quiz with score >= passing_grade (e.g. 85)
        $submission2 = QuizSubmission::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => 85,
            'submitted_at' => now(),
        ]);

        // O_3 should now be UNLOCKED
        actingAs($student)
            ->get(route('classrooms.show', $classroom->slug))
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $page->component('classrooms/Show')
                    ->where('classroom.modules.0.objects.2.can_access', true);
            });

        // Case 3: Educator increases passing grade of Quiz to 90
        $quiz->update(['passing_grade' => 90]);

        // Student's highest score is 85, which is now < 90.
        // O_3 should be RE-LOCKED!
        actingAs($student)
            ->get(route('classrooms.show', $classroom->slug))
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $page->component('classrooms/Show')
                    ->where('classroom.modules.0.objects.2.can_access', false);
            });
    });
});
