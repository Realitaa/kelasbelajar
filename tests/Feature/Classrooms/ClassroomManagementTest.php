<?php

use App\Models\Classroom;
use App\Models\ClassroomEnrollment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

describe('classroom listing', function () {
    it('allows educators to view their own classrooms', function () {
        $educator = User::factory()->educator()->create();
        Classroom::factory()->count(2)->create(['educator_id' => $educator->id]);

        actingAs($educator)
            ->get(route('classrooms.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('classrooms/Index')
                ->has('classrooms', 2)
            );
    });

    it('only displays classrooms owned by the authenticated educator', function () {
        $educatorA = User::factory()->educator()->create();
        $educatorB = User::factory()->educator()->create();

        Classroom::factory()->count(2)->create(['educator_id' => $educatorA->id]);
        Classroom::factory()->count(3)->create(['educator_id' => $educatorB->id]);

        actingAs($educatorA)
            ->get(route('classrooms.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classrooms', 2)
            );
    });
});

describe('classroom creation', function () {
    it('creates a new classroom as unpublished by default', function () {
        $educator = User::factory()->educator()->create();

        actingAs($educator)
            ->post(route('classrooms.store'), [
                'title' => 'New Classroom',
                'description' => 'A new classroom description',
            ])
            ->assertRedirect(route('classrooms.index'))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Kelas berhasil dibuat.',
            ]);

        $classroom = Classroom::first();

        expect($classroom)->not->toBeNull();
        expect($classroom->educator_id)->toBe($educator->id);
        expect($classroom->is_published)->toBeFalse();
        expect($classroom->slug)->not->toBeEmpty();
        expect($classroom->unique_code)->not->toBeEmpty();
    });

    it('assigns classroom ownership to the authenticated educator', function () {
        $educator = User::factory()->educator()->create();

        actingAs($educator)
            ->post(route('classrooms.store'), [
                'title' => 'New Classroom',
                'description' => 'A new classroom description',
            ]);

        $classroom = Classroom::first();
        expect($classroom->educator_id)->toBe($educator->id);
    });
});

describe('classroom update', function () {
    it('allows educators to update classrooms they own', function () {
        $educator = User::factory()->educator()->create();
        $classroom = Classroom::factory()->create([
            'educator_id' => $educator->id,
            'title' => 'Old Title',
            'description' => 'Old Description',
        ]);

        actingAs($educator)
            ->put(route('classrooms.update', $classroom->id), [
                'title' => 'New Title',
                'description' => 'New Description',
            ])
            ->assertRedirect(route('classrooms.index'))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Kelas berhasil diperbarui.',
            ]);

        assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'title' => 'New Title',
            'description' => 'New Description',
        ]);
    });

    it('prevents educators from updating classrooms owned by another educator', function () {
        $educatorA = User::factory()->educator()->create();
        $educatorB = User::factory()->educator()->create();

        $classroom = Classroom::factory()->create([
            'educator_id' => $educatorB->id,
            'title' => 'Old Title',
        ]);

        actingAs($educatorA)
            ->put(route('classrooms.update', $classroom->id), [
                'title' => 'New Title',
            ])
            ->assertForbidden();

        assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'title' => 'Old Title',
        ]);
    });

    it('ignores attempts to modify the classroom unique code', function () {
        $educator = User::factory()->educator()->create();
        $classroom = Classroom::factory()->create([
            'educator_id' => $educator->id,
            'title' => 'Old Title',
            'unique_code' => 'ORIGINAL',
        ]);

        actingAs($educator)
            ->put(route('classrooms.update', $classroom->id), [
                'title' => 'New Title',
                'unique_code' => 'HACKED123',
            ])
            ->assertRedirect(); // Update succeeds for allowed attributes

        assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'title' => 'New Title',
            'unique_code' => 'ORIGINAL',
        ]);
    });

    it('regenerates slug when title is updated', function () {
        $educator = User::factory()->educator()->create();
        $classroom = Classroom::factory()->create([
            'title' => 'Laravel Dasar',
            'unique_code' => 'AB12CD34',
            'slug' => 'laravel-dasar-ab12cd34',
            'educator_id' => $educator->id,
        ]);

        actingAs($educator)
            ->put(route('classrooms.update', $classroom->id), [
                'title' => 'Laravel Lanjutan',
            ])
            ->assertRedirect();

        expect($classroom->fresh()->slug)
            ->toBe('laravel-lanjutan-ab12cd34');
    });
});

describe('classroom publication', function () {
    it('allows educators to publish their own classroom', function () {
        $educator = User::factory()->educator()->create();
        $classroom = Classroom::factory()->create([
            'educator_id' => $educator->id,
            'is_published' => false,
        ]);

        actingAs($educator)
            ->post(route('classrooms.publish', $classroom->id))
            ->assertRedirect()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Kelas berhasil dipublikasikan.',
            ]);

        expect($classroom->fresh()->is_published)->toBeTrue();
    });

    it('prevents educators from publishing classrooms owned by another educator', function () {
        $educatorA = User::factory()->educator()->create();
        $educatorB = User::factory()->educator()->create();
        $classroom = Classroom::factory()->create([
            'educator_id' => $educatorB->id,
            'is_published' => false,
        ]);

        actingAs($educatorA)
            ->post(route('classrooms.publish', $classroom->id))
            ->assertForbidden();

        expect($classroom->fresh()->is_published)->toBeFalse();
    });

    it('allows educators to unpublish classrooms that have no enrollments', function () {
        $educator = User::factory()->educator()->create();
        $classroom = Classroom::factory()->create([
            'educator_id' => $educator->id,
            'is_published' => true,
        ]);

        actingAs($educator)
            ->post(route('classrooms.unpublish', $classroom->id))
            ->assertRedirect()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Publikasi kelas dibatalkan.',
            ]);

        expect($classroom->fresh()->is_published)->toBeFalse();
    });

    it('prevents educators from unpublishing classrooms that already have enrolled students', function () {
        $educator = User::factory()->educator()->create();
        $student = User::factory()->student()->create();

        $classroom = Classroom::factory()->create([
            'educator_id' => $educator->id,
            'is_published' => true,
        ]);

        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        actingAs($educator)
            ->post(route('classrooms.unpublish', $classroom->id))
            ->assertRedirectBack()
            ->assertInertiaFlash('toast', [
                'type' => 'error',
                'message' => 'Kelas tidak dapat dibatalkan karena memiliki peserta.',
            ]);

        expect($classroom->fresh()->is_published)->toBeTrue();
    });
});

describe('classroom deletion', function () {
    it('allows educators to soft delete classrooms they own', function () {
        $educator = User::factory()->educator()->create();
        $classroom = Classroom::factory()->create([
            'educator_id' => $educator->id,
        ]);

        actingAs($educator)
            ->delete(route('classrooms.destroy', $classroom->id))
            ->assertRedirect()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Kelas berhasil dihapus.',
            ]);

        assertSoftDeleted('classrooms', [
            'id' => $classroom->id,
        ]);
    });

    it('prevents educators from deleting classrooms owned by another educator', function () {
        $educatorA = User::factory()->educator()->create();
        $educatorB = User::factory()->educator()->create();
        $classroom = Classroom::factory()->create([
            'educator_id' => $educatorB->id,
        ]);

        actingAs($educatorA)
            ->delete(route('classrooms.destroy', $classroom->id))
            ->assertForbidden();

        expect($classroom->fresh())->not->toBeNull();
    });
});

describe('classroom unique code generation', function () {
    it('generates a unique classroom code when creating a classroom', function () {
        $educator = User::factory()->educator()->create();

        actingAs($educator)
            ->post(route('classrooms.store'), [
                'title' => 'New Classroom',
                'description' => 'A new classroom description',
            ]);

        $classroom = Classroom::first();
        expect($classroom->unique_code)->not->toBeEmpty();
    });
});

describe('classroom students list', function () {
    it('allows educators to view enrolled students of their own classrooms', function () {
        $educator = User::factory()->educator()->create();
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);

        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        actingAs($educator)
            ->get(route('classrooms.students', $classroom->slug))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'enrolled_at'],
                ],
            ])
            ->assertJsonFragment([
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ]);
    });

    it('prevents educators from viewing enrolled students of classrooms owned by another educator', function () {
        $educatorA = User::factory()->educator()->create();
        $educatorB = User::factory()->educator()->create();
        $classroom = Classroom::factory()->create(['educator_id' => $educatorB->id]);

        actingAs($educatorA)
            ->get(route('classrooms.students', $classroom->slug))
            ->assertForbidden();
    });

    it('prevents students from viewing classroom students', function () {
        $studentA = User::factory()->student()->create();
        $studentB = User::factory()->student()->create();
        $classroom = Classroom::factory()->create();

        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $studentB->id,
        ]);

        actingAs($studentA)
            ->get(route('classrooms.students', $classroom->slug))
            ->assertForbidden();
    });
});
