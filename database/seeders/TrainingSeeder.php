<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CourseSeeder::class,
            LocationSeeder::class,
            TrainerSeeder::class,
        ]);
    }
}
