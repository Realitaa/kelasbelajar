<?php

use App\Models\Classroom;
use App\Models\ClassroomEnrollment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

describe('student classroom listing', function () {
    it('allows students to view their enrolled classrooms', function () {
        $student = User::factory()->student()->create();
        $classroom1 = Classroom::factory()->create(['is_published' => true]);
        $classroom2 = Classroom::factory()->create(['is_published' => true]);

        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom1->id,
            'student_id' => $student->id,
        ]);

        actingAs($student)
            ->get(route('classrooms.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('classrooms/Index')
                ->has('classrooms', 1)
                ->where('classrooms.0.id', $classroom1->id)
            );
    });

    it('does not display classrooms the student is not enrolled in', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]); // not enrolled

        actingAs($student)
            ->get(route('classrooms.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('classrooms/Index')
                ->has('classrooms', 0)
            );
    });

    it('does not display unpublished classrooms even if the student is enrolled', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => false]);

        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        actingAs($student)
            ->get(route('classrooms.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('classrooms/Index')
                ->has('classrooms', 0)
            );
    });

    it('does not display soft deleted classrooms even if the student is enrolled', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);

        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $classroom->delete(); // soft delete

        actingAs($student)
            ->get(route('classrooms.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('classrooms/Index')
                ->has('classrooms', 0)
            );
    });
});

describe('student classroom authorization restrictions', function () {
    it('prevents students from creating classrooms', function () {
        $student = User::factory()->student()->create();

        actingAs($student)
            ->post(route('classrooms.store'), [
                'title' => 'Student Tried To Create',
                'description' => 'Should fail',
            ])
            ->assertForbidden();
    });

    it('prevents students from updating classrooms', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create();

        actingAs($student)
            ->put(route('classrooms.update', $classroom->id), [
                'title' => 'Updated by student',
            ])
            ->assertForbidden();
    });

    it('prevents students from deleting classrooms', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create();

        actingAs($student)
            ->delete(route('classrooms.destroy', $classroom->id))
            ->assertForbidden();
    });

    it('prevents students from publishing classrooms', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => false]);

        actingAs($student)
            ->post(route('classrooms.publish', $classroom->id))
            ->assertForbidden();
    });

    it('prevents students from unpublishing classrooms', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);

        actingAs($student)
            ->post(route('classrooms.unpublish', $classroom->id))
            ->assertForbidden();
    });

    it('prevents students from accessing the manage classroom view', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create();

        actingAs($student)
            ->get(route('classrooms.manage', $classroom->slug))
            ->assertForbidden();
    });

    it('prevents students from fetching the classroom students list', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create();

        actingAs($student)
            ->get(route('classrooms.students', $classroom->slug))
            ->assertForbidden();
    });
});
