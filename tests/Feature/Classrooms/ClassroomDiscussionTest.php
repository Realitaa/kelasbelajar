<?php

use App\Models\Classroom;
use App\Models\ClassroomComment;
use App\Models\ClassroomEnrollment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;

describe('Classroom Discussion Forum', function () {
    it('allows enrolled students and class educators to access the forum', function () {
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

        // Student can access
        actingAs($student)
            ->get(route('classrooms.discussion.index', $classroom->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('classrooms/Discussion')
                ->where('classroom.id', $classroom->id)
            );

        // Educator can access
        actingAs($educator)
            ->get(route('classrooms.discussion.index', $classroom->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('classrooms/Discussion')
                ->where('classroom.id', $classroom->id)
            );
    });

    it('prevents non-enrolled students from accessing the forum', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);

        actingAs($student)
            ->get(route('classrooms.discussion.index', $classroom->slug))
            ->assertForbidden();
    });

    it('redirects guests to login page when accessing the forum', function () {
        $classroom = Classroom::factory()->create(['is_published' => true]);

        $this->get(route('classrooms.discussion.index', $classroom->slug))
            ->assertRedirect(route('login'));
    });

    it('allows posting a comment (level 1)', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);
        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $commentData = [
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => 'Halo ini komentar pertama saya!']],
                    ],
                ],
            ],
        ];

        actingAs($student)
            ->post(route('classrooms.discussion.comments.store', $classroom->slug), $commentData)
            ->assertRedirect();

        $this->assertDatabaseHas('classroom_comments', [
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'parent_id' => null,
        ]);
    });

    it('allows posting a reply (level 2)', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);
        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $comment = ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'content' => ['type' => 'doc', 'content' => []],
        ]);

        $replyData = [
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => 'Ini balasannya']],
                    ],
                ],
            ],
            'parent_id' => $comment->id,
        ];

        actingAs($student)
            ->post(route('classrooms.discussion.comments.store', $classroom->slug), $replyData)
            ->assertRedirect();

        $this->assertDatabaseHas('classroom_comments', [
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'parent_id' => $comment->id,
        ]);
    });

    it('prevents nesting deeper than 2 levels', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);
        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $comment = ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'content' => ['type' => 'doc', 'content' => []],
        ]);

        $reply = ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'parent_id' => $comment->id,
            'content' => ['type' => 'doc', 'content' => []],
        ]);

        // Attempting to reply to a reply (level 3) should fail
        $nestedReplyData = [
            'content' => [
                'type' => 'doc',
                'content' => [],
            ],
            'parent_id' => $reply->id,
        ];

        actingAs($student)
            ->post(route('classrooms.discussion.comments.store', $classroom->slug), $nestedReplyData)
            ->assertRedirect();

        // Assert no level-3 reply was added
        $this->assertDatabaseMissing('classroom_comments', [
            'parent_id' => $reply->id,
        ]);
    });

    it('allows the author to edit their comment/reply', function () {
        $student = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);
        ClassroomEnrollment::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $comment = ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'content' => ['type' => 'doc', 'content' => [['type' => 'text', 'text' => 'Konten lama']]],
        ]);

        $updatedData = [
            'content' => ['type' => 'doc', 'content' => [['type' => 'text', 'text' => 'Konten baru']]],
        ];

        actingAs($student)
            ->put(route('classrooms.discussion.comments.update', [$classroom->slug, $comment->id]), $updatedData)
            ->assertRedirect();

        $comment->refresh();
        expect($comment->content)->toBe($updatedData['content']);
    });

    it('prevents non-authors from editing a comment', function () {
        $student1 = User::factory()->student()->create();
        $student2 = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);

        ClassroomEnrollment::factory()->create(['classroom_id' => $classroom->id, 'student_id' => $student1->id]);
        ClassroomEnrollment::factory()->create(['classroom_id' => $classroom->id, 'student_id' => $student2->id]);

        $comment = ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $student1->id,
            'content' => ['type' => 'doc', 'content' => [['type' => 'text', 'text' => 'Konten asli']]],
        ]);

        $updatedData = [
            'content' => ['type' => 'doc', 'content' => [['type' => 'text', 'text' => 'Diubah orang lain']]],
        ];

        actingAs($student2)
            ->put(route('classrooms.discussion.comments.update', [$classroom->slug, $comment->id]), $updatedData)
            ->assertForbidden();
    });

    it('allows authors and class educators to delete comments, soft deleting them', function () {
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

        $comment1 = ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'content' => ['type' => 'doc', 'content' => []],
        ]);

        $comment2 = ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'content' => ['type' => 'doc', 'content' => []],
        ]);

        // Student deletes their own comment (comment1)
        actingAs($student)
            ->delete(route('classrooms.discussion.comments.destroy', [$classroom->slug, $comment1->id]))
            ->assertRedirect();

        assertSoftDeleted('classroom_comments', ['id' => $comment1->id]);

        // Educator deletes student's comment (comment2)
        actingAs($educator)
            ->delete(route('classrooms.discussion.comments.destroy', [$classroom->slug, $comment2->id]))
            ->assertRedirect();

        assertSoftDeleted('classroom_comments', ['id' => $comment2->id]);
    });

    it('prevents random students from deleting other comments', function () {
        $student1 = User::factory()->student()->create();
        $student2 = User::factory()->student()->create();
        $classroom = Classroom::factory()->create(['is_published' => true]);

        ClassroomEnrollment::factory()->create(['classroom_id' => $classroom->id, 'student_id' => $student1->id]);
        ClassroomEnrollment::factory()->create(['classroom_id' => $classroom->id, 'student_id' => $student2->id]);

        $comment = ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $student1->id,
            'content' => ['type' => 'doc', 'content' => []],
        ]);

        actingAs($student2)
            ->delete(route('classrooms.discussion.comments.destroy', [$classroom->slug, $comment->id]))
            ->assertForbidden();
    });

    it('queries soft deleted comments but shields their original content on retrieval', function () {
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

        $comment = ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'content' => ['type' => 'doc', 'content' => [['type' => 'text', 'text' => 'Ini rahasia yang akan dihapus']]],
        ]);

        $reply = ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'parent_id' => $comment->id,
            'content' => ['type' => 'doc', 'content' => [['type' => 'text', 'text' => 'Balasan pembicaraan']]],
        ]);

        // Soft delete the main comment
        $comment->delete();

        // Get discussion forum index page and check Inertia props
        actingAs($student)
            ->get(route('classrooms.discussion.index', $classroom->slug))
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($comment, $reply) {
                $page->component('classrooms/Discussion')
                    // Verify that the comment is still returned so the reply is visible
                    ->has('comments', 1)
                    ->where('comments.0.id', $comment->id)
                    // The deleted comment's content is nullified / shielded!
                    ->where('comments.0.content', [])
                    ->where('comments.0.deleted_at', fn ($val) => ! is_null($val))
                    ->has('comments.0.replies', 1)
                    ->where('comments.0.replies.0.id', $reply->id)
                    ->where('comments.0.replies.0.content', ['type' => 'doc', 'content' => [['type' => 'text', 'text' => 'Balasan pembicaraan']]]);
            });
    });

    it('does not shield soft-deleted comments content from the classroom educator', function () {
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

        $comment = ClassroomComment::create([
            'classroom_id' => $classroom->id,
            'user_id' => $student->id,
            'content' => ['type' => 'doc', 'content' => [['type' => 'text', 'text' => 'Ini rahasia yang akan dihapus']]],
        ]);

        $comment->delete();

        // Get discussion forum index page as educator and verify content is NOT nullified
        actingAs($educator)
            ->get(route('classrooms.discussion.index', $classroom->slug))
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($comment) {
                $page->component('classrooms/Discussion')
                    ->has('comments', 1)
                    ->where('comments.0.id', $comment->id)
                    ->where('comments.0.content', ['type' => 'doc', 'content' => [['type' => 'text', 'text' => 'Ini rahasia yang akan dihapus']]]);
            });
    });
});
