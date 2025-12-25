<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Smart reminders: H-3 (morning), H-1/H-0 (morning + evening)
        $schedule->command('notifications:send-smart')
                 ->dailyAt('08:00')
                 ->timezone('Asia/Jakarta')
                 ->onSuccess(function () {
                     \Log::info('Smart reminders sent (morning)');
                 })
                 ->onFailure(function () {
                     \Log::error('Smart reminders failed (morning)');
                 });

        $schedule->command('notifications:send-smart')
                 ->dailyAt('17:00')
                 ->timezone('Asia/Jakarta')
                 ->onSuccess(function () {
                     \Log::info('Smart reminders sent (evening)');
                 })
                 ->onFailure(function () {
                     \Log::error('Smart reminders failed (evening)');
                 });
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
