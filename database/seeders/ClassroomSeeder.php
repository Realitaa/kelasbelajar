<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\ClassroomEnrollment;
use App\Models\ClassroomModule;
use App\Models\LearningContent;
use App\Models\ModuleObject;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $educator1 = User::where('email', 'educator1@example.com')->first();
        $educator2 = User::where('email', 'educator2@example.com')->first();
        $student1 = User::where('email', 'student1@example.com')->first();
        $student2 = User::where('email', 'student2@example.com')->first();
        $student3 = User::where('email', 'student3@example.com')->first();

        if (! $educator1 || ! $educator2) {
            return;
        }

        // 1. Create Classrooms
        // Educator 1 classrooms
        $classroom1 = Classroom::factory()->create([
            'educator_id' => $educator1->id,
            'title' => 'Dasar-Dasar Pemrograman Laravel',
            'is_published' => true,
        ]);

        $classroom2 = Classroom::factory()->create([
            'educator_id' => $educator1->id,
            'title' => 'Pengembangan Frontend dengan Vue.js 3',
            'is_published' => true,
        ]);

        $classroom3 = Classroom::factory()->unpublished()->create([
            'educator_id' => $educator1->id,
            'title' => 'Keamanan Web Tingkat Lanjut (Draf)',
        ]);

        // Educator 2 classrooms
        $classroom4 = Classroom::factory()->create([
            'educator_id' => $educator2->id,
            'title' => 'Pemodelan Basis Data Relasional',
            'is_published' => true,
        ]);

        $classroom5 = Classroom::factory()->create([
            'educator_id' => $educator2->id,
            'title' => 'Pengujian PHP dengan Pest',
            'is_published' => true,
        ]);

        // 2. Enroll Students
        $enrollments = [
            [$classroom1, $student1],
            [$classroom1, $student2],
            [$classroom2, $student1],
            [$classroom4, $student2],
            [$classroom5, $student3],
        ];

        foreach ($enrollments as [$classroom, $student]) {
            if ($student) {
                ClassroomEnrollment::factory()->create([
                    'classroom_id' => $classroom->id,
                    'student_id' => $student->id,
                    'enrolled_at' => now()->subDays(rand(1, 10)),
                ]);
            }
        }

        // 3. Create Modules and Objects for Classroom 1 (Laravel Basics)
        $module1 = ClassroomModule::factory()->create([
            'classroom_id' => $classroom1->id,
            'title' => 'Modul 1: Pengenalan Laravel & Instalasi',
            'position' => 1,
        ]);

        $module2 = ClassroomModule::factory()->create([
            'classroom_id' => $classroom1->id,
            'title' => 'Modul 2: Routing dan Controller',
            'position' => 2,
        ]);

        // Add Learning Content to Module 1
        $content1 = LearningContent::factory()->create([
            'created_by' => $educator1->id,
            'title' => 'Pengenalan Framework Laravel',
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Laravel adalah framework aplikasi web PHP dengan sintaks yang elegan dan ekspresif. Modul ini membahas langkah instalasi awal dan struktur folder.',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        ModuleObject::factory()->create([
            'module_id' => $module1->id,
            'object_id' => $content1->id,
            'object_type' => LearningContent::class,
            'position' => 1,
        ]);

        // Add Quiz to Module 2
        $quiz = Quiz::factory()->create([
            'created_by' => $educator1->id,
            'title' => 'Kuis Routing & Controllers',
            'description' => 'Evaluasi pemahaman Anda mengenai HTTP routing dan controller di Laravel.',
        ]);

        ModuleObject::factory()->create([
            'module_id' => $module2->id,
            'object_id' => $quiz->id,
            'object_type' => Quiz::class,
            'position' => 1,
        ]);

        // Add Questions & Options to Quiz
        $question1 = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Method apa yang digunakan untuk mendefinisikan route yang merespons semua HTTP verb?',
                            ],
                        ],
                    ],
                ],
            ],
            'position' => 1,
        ]);

        QuizOption::create([
            'question_id' => $question1->id,
            'option' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => 'Route::any()']],
                    ],
                ],
            ],
            'is_correct' => true,
        ]);

        QuizOption::create([
            'question_id' => $question1->id,
            'option' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => 'Route::all()']],
                    ],
                ],
            ],
            'is_correct' => false,
        ]);

        QuizOption::create([
            'question_id' => $question1->id,
            'option' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => 'Route::match()']],
                    ],
                ],
            ],
            'is_correct' => false,
        ]);

        $question2 = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Bagaimana cara mendefinisikan route parameter opsional?',
                            ],
                        ],
                    ],
                ],
            ],
            'position' => 2,
        ]);

        QuizOption::create([
            'question_id' => $question2->id,
            'option' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => 'Menggunakan tanda tanya (?) di akhir parameter']],
                    ],
                ],
            ],
            'is_correct' => true,
        ]);

        QuizOption::create([
            'question_id' => $question2->id,
            'option' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [['type' => 'text', 'text' => 'Menggunakan kurung siku []']],
                    ],
                ],
            ],
            'is_correct' => false,
        ]);
    }
}
