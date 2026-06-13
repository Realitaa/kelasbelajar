<?php

use App\Models\Classroom;
use App\Models\ClassroomEnrollment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

describe('student classroom discovery listing', function () {
    it('redirects guests to login page when accessing discovery', function () {
        $this->get(route('classrooms.discovery'))
            ->assertRedirect(route('login'));
    });

    it('prevents educators from accessing discovery page', function () {
        $educator = User::factory()->educator()->create();

        actingAs($educator)
            ->get(route('classrooms.discovery'))
            ->assertForbidden();
    });

    it('prevents administrators from accessing discovery page', function () {
        $admin = User::factory()->administrator()->create();

        actingAs($admin)
            ->get(route('classrooms.discovery'))
            ->assertForbidden();
    });

    it('allows students to access discovery page', function () {
        $student = User::factory()->student()->create();

        actingAs($student)
            ->get(route('classrooms.discovery'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('classrooms/Discovery')
            );
    });

    it('only displays published classrooms in discovery', function () {
        $student = User::factory()->student()->create();
        Classroom::factory()->create(['is_published' => true, 'title' => 'Published Class']);
        Classroom::factory()->create(['is_published' => false, 'title' => 'Draft Class']);

        actingAs($student)
            ->get(route('classrooms.discovery'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classrooms.data', 1)
                ->where('classrooms.data.0.title', 'Published Class')
            );
    });

    it('does not display soft deleted classrooms in discovery', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);
        $classroom->delete(); // Soft delete

        actingAs($student)
            ->get(route('classrooms.discovery'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classrooms.data', 0)
            );
    });

    it('filters classrooms by title query', function () {
        $student = User::factory()->student()->create();
        Classroom::factory()->create(['is_published' => true, 'title' => 'Laravel basics']);
        Classroom::factory()->create(['is_published' => true, 'title' => 'Vue.js Advanced']);

        actingAs($student)
            ->get(route('classrooms.discovery', ['query' => 'Laravel']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classrooms.data', 1)
                ->where('classrooms.data.0.title', 'Laravel basics')
            );
    });

    it('filters classrooms by educator name query', function () {
        $student = User::factory()->student()->create();
        $educatorA = User::factory()->educator()->create(['name' => 'Taylor Otwell']);
        $educatorB = User::factory()->educator()->create(['name' => 'Evan You']);

        Classroom::factory()->create(['is_published' => true, 'educator_id' => $educatorA->id, 'title' => 'Laravel Framework']);
        Classroom::factory()->create(['is_published' => true, 'educator_id' => $educatorB->id, 'title' => 'Vue Framework']);

        actingAs($student)
            ->get(route('classrooms.discovery', ['query' => 'Taylor']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classrooms.data', 1)
                ->where('classrooms.data.0.title', 'Laravel Framework')
            );
    });

    it('filters classrooms by classroom unique code query', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true, 'unique_code' => 'MATH1234']);
        Classroom::factory()->create(['is_published' => true, 'unique_code' => 'PHYS5678']);

        actingAs($student)
            ->get(route('classrooms.discovery', ['query' => 'MATH1234']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classrooms.data', 1)
                ->where('classrooms.data.0.unique_code', 'MATH1234')
            );
    });

    it('paginates classrooms correctly', function () {
        $student = User::factory()->student()->create();
        Classroom::factory()->count(15)->create(['is_published' => true]);

        actingAs($student)
            ->get(route('classrooms.discovery'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('classrooms.data', 12) // Assuming 12 per page
                ->where('classrooms.current_page', 1)
                ->where('classrooms.last_page', 2)
            );
    });
});

describe('student classroom enrollment', function () {
    it('redirects guests to login page when trying to enroll', function () {
        $classroom = Classroom::factory()->create(['is_published' => true]);

        $this->post(route('classrooms.enroll', $classroom->slug))
            ->assertRedirect(route('login'));
    });

    it('prevents educators from enrolling in a classroom', function () {
        $educator = User::factory()->educator()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);

        actingAs($educator)
            ->post(route('classrooms.enroll', $classroom->slug))
            ->assertForbidden();
    });

    it('prevents administrators from enrolling in a classroom', function () {
        $admin = User::factory()->administrator()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);

        actingAs($admin)
            ->post(route('classrooms.enroll', $classroom->slug))
            ->assertForbidden();
    });

    it('allows students to successfully enroll in a published classroom', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);

        actingAs($student)
            ->post(route('classrooms.enroll', $classroom->slug))
            ->assertRedirect()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => "Berhasil bergabung ke kelas {$classroom->title}.",
            ]);

        $this->assertDatabaseHas('classroom_enrollments', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);
    });

    it('does not allow students to enroll in an unpublished classroom', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => false]);

        actingAs($student)
            ->post(route('classrooms.enroll', $classroom->slug))
            ->assertNotFound();

        $this->assertDatabaseMissing('classroom_enrollments', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);
    });

    it('does not allow students to enroll in a soft-deleted classroom', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);
        $classroom->delete(); // Soft delete

        actingAs($student)
            ->post(route('classrooms.enroll', $classroom->slug))
            ->assertNotFound();

        $this->assertDatabaseMissing('classroom_enrollments', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);
    });

    it('prevents duplicate enrollments for the same classroom', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);

        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        actingAs($student)
            ->post(route('classrooms.enroll', $classroom->slug))
            ->assertRedirect(); // Should redirect back gracefully without error

        $this->assertDatabaseCount('classroom_enrollments', 1);
    });
});
