<?php

use App\Models\Classroom;
use App\Models\ClassroomModule;
use App\Models\LearningContent;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('Authorization and Access Control', function () {
    it('allows educators who own the classroom to access the manage page', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);

        $this->actingAs($educator)
            ->get(route('classrooms.manage', $classroom->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('classrooms/Manage')
                ->has('classroom')
            );
    });

    it('prevents educators who do not own the classroom from accessing the manage page', function () {
        $educator1 = User::factory()->create(['role' => 'educator']);
        $educator2 = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator1->id]);

        $this->actingAs($educator2)
            ->get(route('classrooms.manage', $classroom->slug))
            ->assertForbidden();
    });

    it('prevents students from accessing the manage page', function () {
        $student = User::factory()->create(['role' => 'student']);
        $classroom = Classroom::factory()->create();

        $this->actingAs($student)
            ->get(route('classrooms.manage', $classroom->slug))
            ->assertForbidden();
    });

    it('redirects guests to the login page when accessing the manage page', function () {
        $classroom = Classroom::factory()->create();

        $this->get(route('classrooms.manage', $classroom->slug))
            ->assertRedirect(route('login'));
    });
});

describe('Classroom Module CRUD Operations', function () {
    it('allows educator owner to create a classroom module', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);

        $response = $this->actingAs($educator)
            ->post(route('classrooms.modules.store', $classroom->slug), [
                'title' => 'Introduction to PHP',
            ]);

        $response->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Modul berhasil ditambahkan.']);

        $this->assertDatabaseHas('classroom_modules', [
            'classroom_id' => $classroom->id,
            'title' => 'Introduction to PHP',
            'position' => 1,
        ]);
    });

    it('appends the new module at the end of the position list', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        ClassroomModule::factory()->create(['classroom_id' => $classroom->id, 'position' => 1]);
        ClassroomModule::factory()->create(['classroom_id' => $classroom->id, 'position' => 2]);

        $this->actingAs($educator)
            ->post(route('classrooms.modules.store', $classroom->slug), [
                'title' => 'Third Module',
            ]);

        $this->assertDatabaseHas('classroom_modules', [
            'classroom_id' => $classroom->id,
            'title' => 'Third Module',
            'position' => 3,
        ]);
    });

    it('validates classroom module title is required', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);

        $this->actingAs($educator)
            ->post(route('classrooms.modules.store', $classroom->slug), [
                'title' => '',
            ])
            ->assertSessionHasErrors('title');
    });

    it('allows educator owner to update a classroom module title', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id, 'title' => 'Old Title']);

        $response = $this->actingAs($educator)
            ->put(route('classrooms.modules.update', [$classroom->slug, $module->id]), [
                'title' => 'New Title',
            ]);

        $response->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Modul berhasil diperbarui.']);

        $this->assertDatabaseHas('classroom_modules', [
            'id' => $module->id,
            'title' => 'New Title',
        ]);
    });

    it('allows educator owner to delete a classroom module and cascade deletes module objects', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $content = LearningContent::factory()->create(['created_by' => $educator->id]);

        $moduleObject = $module->objects()->create([
            'object_id' => $content->id,
            'object_type' => LearningContent::class,
            'position' => 1,
        ]);

        $response = $this->actingAs($educator)
            ->delete(route('classrooms.modules.destroy', [$classroom->slug, $module->id]));

        $response->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Modul berhasil dihapus.']);

        $this->assertDatabaseMissing('classroom_modules', ['id' => $module->id]);
        $this->assertDatabaseMissing('module_objects', ['id' => $moduleObject->id]);
        $this->assertDatabaseHas('learning_contents', ['id' => $content->id]);
    });

    it('reorders remaining classroom modules sequentially when a module is deleted', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module1 = ClassroomModule::factory()->create(['classroom_id' => $classroom->id, 'position' => 1]);
        $module2 = ClassroomModule::factory()->create(['classroom_id' => $classroom->id, 'position' => 2]);
        $module3 = ClassroomModule::factory()->create(['classroom_id' => $classroom->id, 'position' => 3]);

        $this->actingAs($educator)
            ->delete(route('classrooms.modules.destroy', [$classroom->slug, $module2->id]));

        $this->assertDatabaseHas('classroom_modules', ['id' => $module1->id, 'position' => 1]);
        $this->assertDatabaseHas('classroom_modules', ['id' => $module3->id, 'position' => 2]);
    });

    it('prevents non-owners from performing CRUD operations on classroom modules', function () {
        $educator1 = User::factory()->create(['role' => 'educator']);
        $educator2 = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator1->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($educator2)
            ->post(route('classrooms.modules.store', $classroom->slug), ['title' => 'Test'])
            ->assertForbidden();

        $this->actingAs($educator2)
            ->put(route('classrooms.modules.update', [$classroom->slug, $module->id]), ['title' => 'Test'])
            ->assertForbidden();

        $this->actingAs($educator2)
            ->delete(route('classrooms.modules.destroy', [$classroom->slug, $module->id]))
            ->assertForbidden();
    });
});

describe('Reordering Modules and Objects', function () {
    it('allows educator owner to reorder modules within a classroom', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module1 = ClassroomModule::factory()->create(['classroom_id' => $classroom->id, 'position' => 1]);
        $module2 = ClassroomModule::factory()->create(['classroom_id' => $classroom->id, 'position' => 2]);
        $module3 = ClassroomModule::factory()->create(['classroom_id' => $classroom->id, 'position' => 3]);

        $response = $this->actingAs($educator)
            ->post(route('classrooms.modules.reorder', $classroom->slug), [
                'modules' => [
                    ['id' => $module1->id, 'position' => 2],
                    ['id' => $module2->id, 'position' => 3],
                    ['id' => $module3->id, 'position' => 1],
                ],
            ]);

        $response->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Urutan modul berhasil disimpan.']);

        $this->assertDatabaseHas('classroom_modules', ['id' => $module1->id, 'position' => 2]);
        $this->assertDatabaseHas('classroom_modules', ['id' => $module2->id, 'position' => 3]);
        $this->assertDatabaseHas('classroom_modules', ['id' => $module3->id, 'position' => 1]);
    });

    it('allows educator owner to reorder objects within the same module', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $content1 = LearningContent::factory()->create();
        $content2 = LearningContent::factory()->create();

        $obj1 = $module->objects()->create(['object_id' => $content1->id, 'object_type' => LearningContent::class, 'position' => 1]);
        $obj2 = $module->objects()->create(['object_id' => $content2->id, 'object_type' => LearningContent::class, 'position' => 2]);

        $response = $this->actingAs($educator)
            ->post(route('classrooms.objects.reorder', $classroom->slug), [
                'objects' => [
                    ['id' => $obj1->id, 'module_id' => $module->id, 'position' => 2],
                    ['id' => $obj2->id, 'module_id' => $module->id, 'position' => 1],
                ],
            ]);

        $response->dump();
        $response->assertRedirect();
        $this->assertDatabaseHas('module_objects', ['id' => $obj1->id, 'position' => 2]);
        $this->assertDatabaseHas('module_objects', ['id' => $obj2->id, 'position' => 1]);
    });

    it('allows educator owner to move objects across modules', function () {
        $educator = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator->id]);
        $moduleA = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $moduleB = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
        $content = LearningContent::factory()->create();

        $obj = $moduleA->objects()->create(['object_id' => $content->id, 'object_type' => LearningContent::class, 'position' => 1]);

        $response = $this->actingAs($educator)
            ->post(route('classrooms.objects.reorder', $classroom->slug), [
                'objects' => [
                    ['id' => $obj->id, 'module_id' => $moduleB->id, 'position' => 1],
                ],
            ]);

        $response->dump();
        $response->assertRedirect();
        $this->assertDatabaseHas('module_objects', ['id' => $obj->id, 'module_id' => $moduleB->id, 'position' => 1]);
    });

    it('prevents non-owners from reordering modules and objects', function () {
        $educator1 = User::factory()->create(['role' => 'educator']);
        $educator2 = User::factory()->create(['role' => 'educator']);
        $classroom = Classroom::factory()->create(['educator_id' => $educator1->id]);

        $this->actingAs($educator2)
            ->post(route('classrooms.modules.reorder', $classroom->slug), ['modules' => []])
            ->assertForbidden();

        $this->actingAs($educator2)
            ->post(route('classrooms.objects.reorder', $classroom->slug), ['objects' => []])
            ->assertForbidden();
    });
});
