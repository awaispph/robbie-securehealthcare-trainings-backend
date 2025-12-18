<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Course;
use App\Models\Location;
use App\Models\Trainer;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::all();
        $locations = Location::all();
        $trainers = Trainer::where('status', 'active')->get();
        
        $events = [
            ['title' => 'January Security Training', 'status' => 'completed', 'days_offset' => -60],
            ['title' => 'February First Aid Course', 'status' => 'completed', 'days_offset' => -45],
            ['title' => 'March Fire Safety Workshop', 'status' => 'completed', 'days_offset' => -30],
            ['title' => 'April Health & Safety Refresher', 'status' => 'completed', 'days_offset' => -20],
            ['title' => 'May Door Supervisor Training', 'status' => 'completed', 'days_offset' => -15],
            ['title' => 'June CCTV Operator Course', 'status' => 'completed', 'days_offset' => -10],
            ['title' => 'July Security Guard Training', 'status' => 'published', 'days_offset' => -5],
            ['title' => 'August Close Protection Course', 'status' => 'published', 'days_offset' => 0],
            ['title' => 'September Emergency Response', 'status' => 'published', 'days_offset' => 5],
            ['title' => 'October Conflict Management', 'status' => 'published', 'days_offset' => 10],
            ['title' => 'November Physical Intervention', 'status' => 'draft', 'days_offset' => 15],
            ['title' => 'December Annual Review Training', 'status' => 'draft', 'days_offset' => 20],
            ['title' => 'Winter Security Fundamentals', 'status' => 'draft', 'days_offset' => 25],
            ['title' => 'New Year Safety Induction', 'status' => 'draft', 'days_offset' => 30],
            ['title' => 'Q1 Refresher Course', 'status' => 'draft', 'days_offset' => 35],
            ['title' => 'Spring Door Supervisor Renewal', 'status' => 'draft', 'days_offset' => 40],
            ['title' => 'Advanced Security Techniques', 'status' => 'draft', 'days_offset' => 45],
            ['title' => 'Team Leader Development', 'status' => 'draft', 'days_offset' => 50],
            ['title' => 'Customer Service Excellence', 'status' => 'draft', 'days_offset' => 55],
            ['title' => 'Risk Assessment Workshop', 'status' => 'draft', 'days_offset' => 60],
            ['title' => 'Summer Intensive Training', 'status' => 'draft', 'days_offset' => 65],
            ['title' => 'Venue Security Specialist', 'status' => 'draft', 'days_offset' => 70],
            ['title' => 'Retail Security Course', 'status' => 'draft', 'days_offset' => 75],
            ['title' => 'Corporate Security Training', 'status' => 'draft', 'days_offset' => 80],
            ['title' => 'Event Security Management', 'status' => 'draft', 'days_offset' => 85],
        ];

        foreach ($events as $eventData) {
            $startDate = Carbon::now()->addDays($eventData['days_offset']);
            $endDate = $startDate->copy()->addDays(rand(1, 3));
            
            $event = Event::create([
                'title' => $eventData['title'],
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '17:00',
                'location_id' => $locations->isNotEmpty() ? $locations->random()->id : null,
                'trainer_id' => $trainers->isNotEmpty() ? $trainers->random()->id : null,
                'status' => $eventData['status'],
                'notes' => 'Training event for security professionals.',
            ]);

            // Attach 1-3 random courses
            if ($courses->isNotEmpty()) {
                $event->courses()->attach(
                    $courses->random(min(rand(1, 3), $courses->count()))->pluck('id')
                );
            }
        }
    }
}
