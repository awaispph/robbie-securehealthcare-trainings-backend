<?php

namespace Database\Seeders;

use App\Models\Candidate;
use Illuminate\Database\Seeder;

class CandidateSeeder extends Seeder
{
    public function run(): void
    {
        $candidates = [
            ['first_name' => 'John', 'last_name' => 'Smith', 'email' => 'john.smith@example.com', 'phone' => '07700 900001'],
            ['first_name' => 'Sarah', 'last_name' => 'Johnson', 'email' => 'sarah.johnson@example.com', 'phone' => '07700 900002'],
            ['first_name' => 'Michael', 'last_name' => 'Williams', 'email' => 'michael.williams@example.com', 'phone' => '07700 900003'],
            ['first_name' => 'Emma', 'last_name' => 'Brown', 'email' => 'emma.brown@example.com', 'phone' => '07700 900004'],
            ['first_name' => 'James', 'last_name' => 'Taylor', 'email' => 'james.taylor@example.com', 'phone' => '07700 900005'],
            ['first_name' => 'Emily', 'last_name' => 'Davies', 'email' => 'emily.davies@example.com', 'phone' => '07700 900006'],
            ['first_name' => 'David', 'last_name' => 'Wilson', 'email' => 'david.wilson@example.com', 'phone' => '07700 900007'],
            ['first_name' => 'Sophie', 'last_name' => 'Evans', 'email' => 'sophie.evans@example.com', 'phone' => '07700 900008'],
            ['first_name' => 'Daniel', 'last_name' => 'Thomas', 'email' => 'daniel.thomas@example.com', 'phone' => '07700 900009'],
            ['first_name' => 'Olivia', 'last_name' => 'Roberts', 'email' => 'olivia.roberts@example.com', 'phone' => '07700 900010'],
            ['first_name' => 'Matthew', 'last_name' => 'Walker', 'email' => 'matthew.walker@example.com', 'phone' => '07700 900011'],
            ['first_name' => 'Charlotte', 'last_name' => 'Wright', 'email' => 'charlotte.wright@example.com', 'phone' => '07700 900012'],
            ['first_name' => 'Christopher', 'last_name' => 'Hall', 'email' => 'christopher.hall@example.com', 'phone' => '07700 900013'],
            ['first_name' => 'Amelia', 'last_name' => 'Green', 'email' => 'amelia.green@example.com', 'phone' => '07700 900014'],
            ['first_name' => 'Andrew', 'last_name' => 'Adams', 'email' => 'andrew.adams@example.com', 'phone' => '07700 900015'],
            ['first_name' => 'Jessica', 'last_name' => 'Baker', 'email' => 'jessica.baker@example.com', 'phone' => '07700 900016'],
            ['first_name' => 'Thomas', 'last_name' => 'Nelson', 'email' => 'thomas.nelson@example.com', 'phone' => '07700 900017'],
            ['first_name' => 'Grace', 'last_name' => 'Carter', 'email' => 'grace.carter@example.com', 'phone' => '07700 900018'],
            ['first_name' => 'William', 'last_name' => 'Mitchell', 'email' => 'william.mitchell@example.com', 'phone' => '07700 900019'],
            ['first_name' => 'Mia', 'last_name' => 'Perez', 'email' => 'mia.perez@example.com', 'phone' => '07700 900020'],
            ['first_name' => 'Joseph', 'last_name' => 'Roberts', 'email' => 'joseph.roberts@example.com', 'phone' => '07700 900021'],
            ['first_name' => 'Lily', 'last_name' => 'Turner', 'email' => 'lily.turner@example.com', 'phone' => '07700 900022'],
            ['first_name' => 'Alexander', 'last_name' => 'Phillips', 'email' => 'alexander.phillips@example.com', 'phone' => '07700 900023'],
            ['first_name' => 'Ella', 'last_name' => 'Campbell', 'email' => 'ella.campbell@example.com', 'phone' => '07700 900024'],
            ['first_name' => 'Benjamin', 'last_name' => 'Parker', 'email' => 'benjamin.parker@example.com', 'phone' => '07700 900025'],
            ['first_name' => 'Chloe', 'last_name' => 'Edwards', 'email' => 'chloe.edwards@example.com', 'phone' => '07700 900026'],
            ['first_name' => 'Henry', 'last_name' => 'Collins', 'email' => 'henry.collins@example.com', 'phone' => '07700 900027'],
            ['first_name' => 'Isabella', 'last_name' => 'Stewart', 'email' => 'isabella.stewart@example.com', 'phone' => '07700 900028'],
            ['first_name' => 'Samuel', 'last_name' => 'Morris', 'email' => 'samuel.morris@example.com', 'phone' => '07700 900029'],
            ['first_name' => 'Ava', 'last_name' => 'Murphy', 'email' => 'ava.murphy@example.com', 'phone' => '07700 900030'],
            ['first_name' => 'George', 'last_name' => 'Rogers', 'email' => 'george.rogers@example.com', 'phone' => '07700 900031'],
            ['first_name' => 'Ruby', 'last_name' => 'Reed', 'email' => 'ruby.reed@example.com', 'phone' => '07700 900032'],
            ['first_name' => 'Jack', 'last_name' => 'Cook', 'email' => 'jack.cook@example.com', 'phone' => '07700 900033'],
            ['first_name' => 'Freya', 'last_name' => 'Morgan', 'email' => 'freya.morgan@example.com', 'phone' => '07700 900034'],
            ['first_name' => 'Oscar', 'last_name' => 'Bell', 'email' => 'oscar.bell@example.com', 'phone' => '07700 900035'],
        ];

        foreach ($candidates as $candidate) {
            Candidate::create(array_merge($candidate, ['status' => 'active']));
        }
    }
}
