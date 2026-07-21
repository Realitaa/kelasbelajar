<?php

use App\Models\Classroom;
use App\Models\ClassroomModule;
use App\Models\ModuleObject;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizSession;
use App\Models\QuizSubmission;
use App\Models\User;
use App\Services\StudentQuizService;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->educator = User::factory()->create();
    $this->student = User::factory()->create();

    $this->classroom = Classroom::factory()->create([
        'educator_id' => $this->educator->id,
        'is_published' => true,
    ]);

    // Enroll student
    $this->classroom->enrollments()->create(['student_id' => $this->student->id, 'enrolled_at' => now()]);

    $this->module = ClassroomModule::factory()->create([
        'classroom_id' => $this->classroom->id,
    ]);

    $this->quiz = Quiz::factory()->create([
        'created_by' => $this->educator->id,
        'time_limit' => 30,
        'passing_grade' => 70,
    ]);

    $this->moduleObject = ModuleObject::factory()->create([
        'module_id' => $this->module->id,
        'object_id' => $this->quiz->id,
        'object_type' => Quiz::class,
    ]);

    // Create question 1
    $this->question1 = QuizQuestion::create(['quiz_id' => $this->quiz->id, 'question' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Sample Question']]]], 'position' => 1]);
    $this->option1A = QuizOption::create(['question_id' => $this->question1->id, 'is_correct' => true, 'option' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Option A']]]]]);
    $this->option1B = QuizOption::create(['question_id' => $this->question1->id, 'is_correct' => false, 'option' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Option B']]]]]);
});

it('allows student to start a quiz session', function () {
    actingAs($this->student);

    $response = post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));

    $session = QuizSession::where('student_id', $this->student->id)->where('quiz_id', $this->quiz->id)->first();
    expect($session)->not->toBeNull();
    expect($session->started_at->diffInMinutes($session->expires_at))->toEqual(30);

    $response->assertRedirect(route('quizzes.take', $session->id));
});

it('forbids non-enrolled user from starting quiz', function () {
    $stranger = User::factory()->create();
    actingAs($stranger);

    $response = post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));
    $response->assertForbidden();
});

it('loads existing session without resetting time', function () {
    actingAs($this->student);

    post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));
    $session1 = QuizSession::first();

    // Fast forward 5 minutes
    $this->travel(5)->minutes();

    $response = post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));
    $session2 = QuizSession::first();

    expect($session1->id)->toBe($session2->id);
    expect($session1->expires_at->toDateTimeString())->toBe($session2->expires_at->toDateTimeString());

    $response->assertRedirect(route('quizzes.take', $session1->id));
});

it('allows retake if previous score is below passing grade', function () {
    actingAs($this->student);
    QuizSubmission::create([
        'quiz_id' => $this->quiz->id,
        'student_id' => $this->student->id,
        'score' => 50, // Below 70
        'submitted_at' => now(),
    ]);

    $response = post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));
    $response->assertRedirect();
    expect(QuizSession::count())->toBe(1);
});

it('fetches a question properly during take without exposing is_correct', function () {
    actingAs($this->student);
    post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));
    $session = QuizSession::first();

    $response = get(route('quizzes.question', [$session->id, 0]));
    $response->assertOk();

    $data = $response->json();
    expect($data['id'])->toBe($this->question1->id);
    expect($data['options'][0])->not->toHaveKey('is_correct');
});

it('saves answer and updates session state', function () {
    actingAs($this->student);
    post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));
    $session = QuizSession::first();

    $response = post(route('quizzes.answer', $session->id), [
        'question_id' => $this->question1->id,
        'answer' => $this->option1B->id,
    ]);

    $response->assertOk();
    $session->refresh();
    expect($session->answers[$this->question1->id])->toBe($this->option1B->id);
});

it('submits quiz properly and deletes session', function () {
    actingAs($this->student);
    post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));
    $session = QuizSession::first();

    // Save correct answer
    post(route('quizzes.answer', $session->id), [
        'question_id' => $this->question1->id,
        'answer' => $this->option1A->id,
    ]);

    $response = post(route('quizzes.submit', $session->id));

    $submission = QuizSubmission::first();
    $response->assertRedirect(route('quizzes.submissions.show', $submission->id));

    expect(QuizSession::count())->toBe(0);

    $submission = QuizSubmission::first();
    expect($submission)->not->toBeNull();
    expect($submission->score)->toBe(100);
});

it('auto submits and calculates score when answering after time is up', function () {
    actingAs($this->student);
    post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));
    $session = QuizSession::first();

    // Save correct answer before time is up
    post(route('quizzes.answer', $session->id), [
        'question_id' => $this->question1->id,
        'answer' => $this->option1A->id,
    ]);

    // Fast forward 31 minutes
    $this->travel(31)->minutes();

    // Try to save answer after time is up
    $response = post(route('quizzes.answer', $session->id), [
        'question_id' => $this->question1->id,
        'answer' => $this->option1B->id,
    ]);

    $response->assertStatus(400);

    // Try to fetch question
    $response = get(route('quizzes.question', [$session->id, 0]));
    $response->assertStatus(400);

    // Session still exists until they hit start, take, or submit.
    // Try to hit start -> should auto submit
    $response = post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));

    expect(QuizSession::count())->toBe(0);
    $submission = QuizSubmission::first();
    $response->assertRedirect(route('quizzes.submissions.show', $submission->id));
    expect($submission->score)->toBe(100); // 100 because the correct answer was saved before time was up
});

it('grades PG MCMA questions correctly with all or nothing logic', function () {
    // Create a PG MCMA question
    $mcmaQuestion = QuizQuestion::create(['quiz_id' => $this->quiz->id, 'type' => 'PG MCMA', 'question' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'MCMA']]]], 'position' => 2]);
    $opt1 = QuizOption::create(['question_id' => $mcmaQuestion->id, 'is_correct' => true, 'option' => []]);
    $opt2 = QuizOption::create(['question_id' => $mcmaQuestion->id, 'is_correct' => true, 'option' => []]);
    $opt3 = QuizOption::create(['question_id' => $mcmaQuestion->id, 'is_correct' => false, 'option' => []]);

    actingAs($this->student);
    post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));
    $session = QuizSession::first();

    // Partial correct (missed one correct option) -> Should be 0 points
    post(route('quizzes.answer', $session->id), [
        'question_id' => $mcmaQuestion->id,
        'answer' => [$opt1->id],
    ]);

    // Also answer question 1 incorrectly to isolate scoring to mcmaQuestion
    post(route('quizzes.answer', $session->id), [
        'question_id' => $this->question1->id,
        'answer' => $this->option1B->id, // Wrong
    ]);

    // Fast calculate
    $service = app(StudentQuizService::class);
    $session->refresh();
    $service->calculateAndSubmit($session);

    $submission = QuizSubmission::orderBy('id', 'desc')->first();
    expect($submission->score)->toBe(0);

    // New session, perfect answer
    QuizSubmission::query()->delete();
    $session = $service->startSession($this->classroom, $this->quiz, $this->student->id);

    post(route('quizzes.answer', $session->id), [
        'question_id' => $mcmaQuestion->id,
        'answer' => [$opt1->id, $opt2->id], // Perfect
    ]);
    $session->refresh();
    $service->calculateAndSubmit($session);

    $submission = QuizSubmission::orderBy('id', 'desc')->first();
    // 1 out of 2 questions correct = 50%
    expect($submission->score)->toBe(50);
});

it('grades PG K questions correctly with partial scoring', function () {
    // Create a PG K question
    $pgkQuestion = QuizQuestion::create(['quiz_id' => $this->quiz->id, 'type' => 'PG K', 'question' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'PGK']]]], 'position' => 2]);
    $opt1 = QuizOption::create(['question_id' => $pgkQuestion->id, 'is_correct' => true, 'option' => []]); // True
    $opt2 = QuizOption::create(['question_id' => $pgkQuestion->id, 'is_correct' => false, 'option' => []]); // False

    actingAs($this->student);
    post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));
    $session = QuizSession::first();

    // Answer PG K partially correct (opt1 correct, opt2 incorrect evaluation)
    post(route('quizzes.answer', $session->id), [
        'question_id' => $pgkQuestion->id,
        'answer' => [
            $opt1->id => true, // Correct evaluation
            $opt2->id => true, // Wrong evaluation (should be false)
        ],
    ]);

    // Also answer question 1 incorrectly
    post(route('quizzes.answer', $session->id), [
        'question_id' => $this->question1->id,
        'answer' => $this->option1B->id, // Wrong
    ]);

    $service = app(StudentQuizService::class);
    $session->refresh();
    $service->calculateAndSubmit($session);

    $submission = QuizSubmission::orderBy('id', 'desc')->first();
    // PG K points = 1/2 = 0.5. Total points = 0.5. Total questions = 2. Score = 0.5 / 2 * 100 = 25
    expect($submission->score)->toBe(25);
});

it('denies starting quiz if student has reached max attempts limit', function () {
    $this->quiz->update(['max_attempts' => 2]);

    actingAs($this->student);

    // Attempt 1
    QuizSubmission::create([
        'quiz_id' => $this->quiz->id,
        'student_id' => $this->student->id,
        'score' => 80,
        'submitted_at' => now(),
    ]);

    // Attempt 2
    QuizSubmission::create([
        'quiz_id' => $this->quiz->id,
        'student_id' => $this->student->id,
        'score' => 90,
        'submitted_at' => now(),
    ]);

    // Attempt 3 (Should be blocked)
    $response = post(route('classrooms.quizzes.start', [$this->classroom->slug, $this->quiz->id]));
    $response->assertRedirect();
    $response->assertSessionHas('toast', [
        'type' => 'error',
        'message' => 'Batas maksimum percobaan kuis telah tercapai.',
    ]);

    expect(QuizSession::where('student_id', $this->student->id)->where('quiz_id', $this->quiz->id)->exists())->toBeFalse();
});

it('hides solution in submission details if min attempts is not met', function () {
    $this->quiz->update(['min_attempts_for_solution' => 2]);
    $this->question1->update(['solution' => ['ops' => [['insert' => 'This is the explanation']]]]);

    actingAs($this->student);

    // Attempt 1
    $submission = QuizSubmission::create([
        'quiz_id' => $this->quiz->id,
        'student_id' => $this->student->id,
        'score' => 80,
        'submitted_at' => now(),
    ]);

    // View submission 1 (attemptsCount = 1 < 2) -> solution should be hidden/null
    $response = get(route('quizzes.submissions.show', $submission->id));
    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('canShowSolution', false)
        ->missing('quiz.questions.0.solution')
    );
});

it('shows solution in submission details if min attempts is met', function () {
    $this->quiz->update(['min_attempts_for_solution' => 2]);
    $this->question1->update(['solution' => ['ops' => [['insert' => 'This is the explanation']]]]);

    actingAs($this->student);

    // Attempt 1
    QuizSubmission::create([
        'quiz_id' => $this->quiz->id,
        'student_id' => $this->student->id,
        'score' => 80,
        'submitted_at' => now(),
    ]);

    // Attempt 2
    $submission = QuizSubmission::create([
        'quiz_id' => $this->quiz->id,
        'student_id' => $this->student->id,
        'score' => 90,
        'submitted_at' => now(),
    ]);

    // View submission 2 (attemptsCount = 2 >= 2) -> solution should be shown
    $response = get(route('quizzes.submissions.show', $submission->id));
    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('canShowSolution', true)
        ->where('quiz.questions.0.solution', ['ops' => [['insert' => 'This is the explanation']]])
    );
});
