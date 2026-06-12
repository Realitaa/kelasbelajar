<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('user can upload file to temporary folder', function () {
    $user = User::factory()->create();
    $fileOriginalName = 'materi-tambahan.jpg';
    $file = UploadedFile::fake()->image($fileOriginalName)->size(1024);

    $response = $this->actingAs($user)->postJson(route('file.upload'), [
        'file' => $file,
    ]);

    $response->assertSuccessful();

    $response->assertJsonStructure([
        'success',
        'file' => ['id', 'filename', 'original_name'],
    ]);

    $uploadedFilename = $response->json('file.filename');
    Storage::disk('public')->assertExists("tmp/$uploadedFilename");

    $this->assertDatabaseHas('media', [
        'uploaded_by' => $user->id,
        'original_name' => $fileOriginalName,
    ]);
});

test('user can remove file from temporary folder', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('materi-tambahan.jpg')->size(1024);

    $response = $this->actingAs($user)
        ->postJson(route('file.upload'), [
            'file' => $file,
        ])
        ->assertSuccessful();

    $uploadedFilename = $response->json('file.filename');
    Storage::disk('public')->assertExists("tmp/$uploadedFilename");

    $file = $response->json('file')['id'];

    $this->actingAs($user)
        ->deleteJson(route('file.remove', $file))
        ->assertSuccessful();

    $this->assertDatabaseMissing('media', [
        'id' => $file,
    ]);

    Storage::disk('public')->assertMissing("tmp/$uploadedFilename");
});

test(
    'user trying to upload file exceeding upload size limit should failed',
    function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('materi-tambahan.jpg')->size(10240);

        $response = $this->actingAs($user)->postJson(route('file.upload'), [
            'file' => $file,
        ]);

        $response->assertJsonValidationErrors(['file']);

        $uploadedFilename = $response->json('file.filename');
        Storage::disk('public')->assertMissing("tmp/$uploadedFilename");
    },
);

test('user cannot remove another user file from temporary folder', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $file = UploadedFile::fake()->image('materi-tambahan.jpg')->size(1024);

    $response = $this->actingAs($user1)
        ->postJson(route('file.upload'), [
            'file' => $file,
        ])
        ->assertSuccessful();

    $uploadedFilename = $response->json('file.filename');
    Storage::disk('public')->assertExists("tmp/$uploadedFilename");

    $file = $response->json('file')['id'];

    $this->actingAs($user2)
        ->deleteJson(route('file.remove', $file))
        ->assertForbidden();

    $this->assertDatabaseHas('media', [
        'id' => $file,
    ]);

    Storage::disk('public')->assertExists("tmp/$uploadedFilename");
});

test('guest trying to upload file should failed', function () {
    $file = UploadedFile::fake()->image('materi-tambahan.jpg')->size(1024);

    $response = $this->postJson(route('file.upload'), [
        'file' => $file,
    ]);

    $response->assertUnauthorized();

    $uploadedFilename = $response->json('file.filename');
    Storage::disk('public')->assertMissing("tmp/$uploadedFilename");
});

test('not found file in database handed gracefully', function () {
    $user = User::factory()->create();

    // change to accessing file directly
    $this->actingAs($user)
        ->get(route('file.show', 999))
        ->assertJson([
            'success' => false,
            'message' => 'File tidak ditemukan.',
        ]);
});

test('not found file physically handed gracefully', function () {
    $user = User::factory()->create();
    $file = Media::factory()->create([
        'fileable_id' => 1, // Dummy
        'fileable_type' => 'App\Models\LearningContent', // Dummy
        'uploaded_by' => $user->id,
    ]);
    Storage::disk('public')->delete($file->path);

    // change to accessing file directly
    $this->actingAs($user)
        ->get(route('file.show', $file->id))
        ->assertJson([
            'success' => false,
            'message' => 'File tidak ditemukan.',
        ]);
});

test('guest cannot access file', function () {
    $response = $this->get(route('file.show', 1));

    $response->assertRedirect(route('login'));
});

test('orphan temporary files can be cleaned up after 24 hour', function () {
    $user = User::factory()->create();

    // Create a temporary media record that is older than 24 hours (25 hours ago)
    $oldMedia = Media::factory()->create([
        'status' => 'temporary',
        'created_at' => now()->subHours(25),
        'disk' => 'public',
        'path' => 'tmp/old-file.jpg',
        'filename' => 'old-file.jpg',
        'uploaded_by' => $user->id,
    ]);
    Storage::disk('public')->put('tmp/old-file.jpg', 'content');

    // Create a temporary media record that is recent (2 hours ago)
    $newMedia = Media::factory()->create([
        'status' => 'temporary',
        'created_at' => now()->subHours(2),
        'disk' => 'public',
        'path' => 'tmp/new-file.jpg',
        'filename' => 'new-file.jpg',
        'uploaded_by' => $user->id,
    ]);
    Storage::disk('public')->put('tmp/new-file.jpg', 'content');

    // Create a permanent media record that is old (should not be deleted)
    $permanentMedia = Media::factory()->create([
        'status' => 'permanent',
        'created_at' => now()->subHours(25),
        'disk' => 'public',
        'path' => 'tmp/permanent-file.jpg',
        'filename' => 'permanent-file.jpg',
        'uploaded_by' => $user->id,
    ]);
    Storage::disk('public')->put('tmp/permanent-file.jpg', 'content');

    // Run the Artisan command to clean up
    $this->artisan('media:clean')->assertSuccessful();

    // Assert the old temporary file and record are deleted
    $this->assertDatabaseMissing('media', ['id' => $oldMedia->id]);
    Storage::disk('public')->assertMissing('tmp/old-file.jpg');

    // Assert the new temporary file and record still exist
    $this->assertDatabaseHas('media', ['id' => $newMedia->id]);
    Storage::disk('public')->assertExists('tmp/new-file.jpg');

    // Assert the permanent old file and record still exist
    $this->assertDatabaseHas('media', ['id' => $permanentMedia->id]);
    Storage::disk('public')->assertExists('tmp/permanent-file.jpg');
});
