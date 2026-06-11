<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = [
            'name' => 'Administrator',
            'email' => 'admin@example.com',
        ];

        $educator = [
            [
                'name' => 'Educator 1',
                'email' => 'educator1@example.com',
            ],
            [
                'name' => 'Educator 2',
                'email' => 'educator2@example.com',
            ],
        ];

        $student = [
            [
                'name' => 'Student 1',
                'email' => 'student1@example.com',
            ],
            [
                'name' => 'Student 2',
                'email' => 'student2@example.com',
            ],
            [
                'name' => 'Student 3',
                'email' => 'student3@example.com',
            ],
        ];

        User::factory()->administrator()->create($admin);

        User::factory()->educator()->createMany($educator);

        User::factory()->student()->createMany($student);
    }
}
