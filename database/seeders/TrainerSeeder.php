<?php

namespace Database\Seeders;

use App\Models\Trainer;
use Illuminate\Database\Seeder;

class TrainerSeeder extends Seeder
{
    public function run(): void
    {
        $trainers = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@securetrainingservices.co.uk',
                'phone' => '07700 900001',
                'status' => 'active',
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@securetrainingservices.co.uk',
                'phone' => '07700 900002',
                'status' => 'active',
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'michael.brown@securetrainingservices.co.uk',
                'phone' => '07700 900003',
                'status' => 'active',
            ],
            [
                'name' => 'Emma Wilson',
                'email' => 'emma.wilson@securetrainingservices.co.uk',
                'phone' => '07700 900004',
                'status' => 'active',
            ],
            [
                'name' => 'David Taylor',
                'email' => null,
                'phone' => '07700 900005',
                'status' => 'inactive',
            ],
        ];

        foreach ($trainers as $trainer) {
            Trainer::create($trainer);
        }
    }
}
