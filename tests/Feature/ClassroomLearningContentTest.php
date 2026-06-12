<?php

use App\Models\Classroom;
use App\Models\ClassroomModule;
use App\Models\LearningContent;
use App\Models\ModuleObject;
use App\Models\User;

test('teacher can create learning content object in module', function () {
    $teacher = User::factory()->create(['role' => 'educator']);
    $classroom = Classroom::factory()->create(['educator_id' => $teacher->id]);
    $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);

    $response = $this->actingAs($teacher)->post(route('classrooms.objects.store', [
        'classroom' => $classroom->slug,
        'module' => $module->id,
    ]), [
        'type' => 'learning_content',
        'title' => 'My New Content',
    ]);

    $response->assertRedirect();
    
    $this->assertDatabaseHas('learning_contents', [
        'title' => 'My New Content',
        'created_by' => $teacher->id,
    ]);

    $content = LearningContent::where('title', 'My New Content')->first();

    $this->assertDatabaseHas('module_objects', [
        'module_id' => $module->id,
        'object_id' => $content->id,
        'object_type' => LearningContent::class,
    ]);
});

test('teacher can update learning content title', function () {
    $teacher = User::factory()->create(['role' => 'educator']);
    $classroom = Classroom::factory()->create(['educator_id' => $teacher->id]);
    $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
    
    $content = LearningContent::factory()->create([
        'title' => 'Old Title',
        'created_by' => $teacher->id,
        'content' => [],
    ]);
    
    $moduleObject = ModuleObject::factory()->create([
        'module_id' => $module->id,
        'object_id' => $content->id,
        'object_type' => LearningContent::class,
    ]);

    $response = $this->actingAs($teacher)->put(route('classrooms.objects.update', [
        'classroom' => $classroom->slug,
        'object' => $moduleObject->id,
    ]), [
        'type' => 'learning_content',
        'title' => 'New Title',
    ]);

    $response->assertRedirect();
    
    $this->assertDatabaseHas('learning_contents', [
        'id' => $content->id,
        'title' => 'New Title',
    ]);
});

test('teacher can get learning content data', function () {
    $teacher = User::factory()->create(['role' => 'educator']);
    $classroom = Classroom::factory()->create(['educator_id' => $teacher->id]);
    $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
    
    $content = LearningContent::factory()->create([
        'title' => 'Some Content',
        'created_by' => $teacher->id,
        'content' => ['ops' => [['insert' => 'Hello']]],
    ]);
    
    $moduleObject = ModuleObject::factory()->create([
        'module_id' => $module->id,
        'object_id' => $content->id,
        'object_type' => LearningContent::class,
    ]);

    $response = $this->actingAs($teacher)->get(route('classrooms.learning-contents.show', [
        'classroom' => $classroom->slug,
        'learningContent' => $content->id,
    ]));

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'id' => $content->id,
            'content' => ['ops' => [['insert' => 'Hello']]],
        ]
    ]);
});

test('teacher can update learning content json', function () {
    $teacher = User::factory()->create(['role' => 'educator']);
    $classroom = Classroom::factory()->create(['educator_id' => $teacher->id]);
    $module = ClassroomModule::factory()->create(['classroom_id' => $classroom->id]);
    
    $content = LearningContent::factory()->create([
        'title' => 'Some Content',
        'created_by' => $teacher->id,
        'content' => [],
    ]);
    
    $moduleObject = ModuleObject::factory()->create([
        'module_id' => $module->id,
        'object_id' => $content->id,
        'object_type' => LearningContent::class,
    ]);

    $newContent = ['ops' => [['insert' => 'Updated content']]];

    $response = $this->actingAs($teacher)->put(route('classrooms.learning-contents.update-content', [
        'classroom' => $classroom->slug,
        'learningContent' => $content->id,
    ]), [
        'content' => $newContent,
    ]);

    $response->assertRedirect();
    
    $this->assertDatabaseHas('learning_contents', [
        'id' => $content->id,
        'content' => json_encode($newContent),
    ]);
});
