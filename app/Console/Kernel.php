<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        // Other commands...
        \App\Console\Commands\RefreshModules::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('queue:work --stop-when-empty --tries=3')
            ->everyMinute()
            ->appendOutputTo(storage_path('logs/queue.log'));

        if (config('app.env') !== 'production' && config('database-refresh.enabled')) {
            $hours = config('database-refresh.hours');

            if ($hours) {
                $schedule->command('migrate:fresh --seed')
                    ->hourlyAt($hours);

                // 2. Then, refresh non-protected modules
                $schedule->command('modules:refresh --force')
                    ->hourlyAt($hours)
                    ->after(function () {
                        Log::info('Modules refreshed after database migration');
                    });

                // 3. Finally, clear all caches
                $schedule->command('optimize:clear')->hourlyAt($hours);
            }
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
