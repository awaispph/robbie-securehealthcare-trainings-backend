<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['name' => 'First Aid at Work', 'status' => 'active', 'expiry_duration' => 3, 'expiry_unit' => 'year'],
            ['name' => 'Fire Safety Awareness', 'status' => 'active', 'expiry_duration' => 1, 'expiry_unit' => 'year'],
            ['name' => 'Manual Handling', 'status' => 'active', 'expiry_duration' => 3, 'expiry_unit' => 'year'],
            ['name' => 'Health & Safety Induction', 'status' => 'active', 'expiry_duration' => null, 'expiry_unit' => null],
            ['name' => 'Food Hygiene Level 2', 'status' => 'active', 'expiry_duration' => 3, 'expiry_unit' => 'year'],
            ['name' => 'Safeguarding Adults', 'status' => 'active', 'expiry_duration' => 3, 'expiry_unit' => 'year'],
            ['name' => 'Infection Control', 'status' => 'active', 'expiry_duration' => 1, 'expiry_unit' => 'year'],
            ['name' => 'Moving & Positioning', 'status' => 'active', 'expiry_duration' => 3, 'expiry_unit' => 'year'],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
