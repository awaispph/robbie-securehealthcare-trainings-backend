<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['name' => 'Wolverhampton Training Centre', 'address' => '55 Waterloo Road, Wolverhampton, WV1 4QQ', 'status' => 'active'],
            ['name' => 'Birmingham Conference Room', 'address' => '123 High Street, Birmingham, B1 1AA', 'status' => 'active'],
            ['name' => 'Manchester Training Suite', 'address' => '45 Deansgate, Manchester, M3 2BA', 'status' => 'active'],
            ['name' => 'Online / Virtual', 'address' => 'Virtual Training Session', 'status' => 'active'],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}
