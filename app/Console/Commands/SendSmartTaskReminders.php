<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSmartTaskReminders extends Command
{
    protected $signature = 'notifications:send-smart';
    protected $description = 'Send smart reminders for tasks due in 3, 1, and 0 days';

    public function handle()
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->copy()->startOfDay();
        $timeBucket = $now->hour < 12 ? 'morning' : 'evening';

        $this->info('Starting smart reminder process...');

        $tasks = Task::where('status', 'berjalan')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $today->copy()->addDays(3))
            ->whereHas('user', function ($query) {
                $query->whereIn('subscription_plan', ['pro', 'plus']);
            })
            ->orderBy('due_date', 'asc')
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No tasks found for smart reminders.');
            return 0;
        }

        $sentCount = 0;

        foreach ($tasks as $task) {
            $dueDate = Carbon::parse($task->due_date)->startOfDay();
            $daysUntil = $today->diffInDays($dueDate, false);

            if (!in_array($daysUntil, [3, 1, 0], true)) {
                continue;
            }

            if ($daysUntil === 3 && $timeBucket !== 'morning') {
                continue;
            }

            $type = $this->buildType($task->id, $daysUntil, $timeBucket, $today);

            if (Notification::where('user_id', $task->user_id)->where('type', $type)->exists()) {
                continue;
            }

            $message = $this->buildMessage($task->title, $daysUntil, $dueDate);

            Notification::create([
                'user_id' => $task->user_id,
                'type' => $type,
                'message' => $message,
                'read' => false,
            ]);

            $sentCount++;
        }

        Log::info('Smart reminders sent', [
            'sent' => $sentCount,
            'time_bucket' => $timeBucket,
            'date' => $today->toDateString(),
        ]);

        $this->info("Smart reminders sent: {$sentCount}");

        return 0;
    }

    private function buildType(int $taskId, int $daysUntil, string $timeBucket, Carbon $date): string
    {
        $suffix = $date->format('Ymd');
        $type = "task_reminder_h{$daysUntil}_task{$taskId}_{$suffix}";

        if (in_array($daysUntil, [1, 0], true)) {
            $type .= "_{$timeBucket}";
        }

        return $type;
    }

    private function buildMessage(string $title, int $daysUntil, Carbon $dueDate): string
    {
        if ($daysUntil === 0) {
            return "Reminder H-0: Task \"{$title}\" jatuh tempo hari ini ({$dueDate->format('d M Y')}).";
        }

        return "Reminder H-{$daysUntil}: Task \"{$title}\" jatuh tempo {$daysUntil} hari lagi ({$dueDate->format('d M Y')}).";
    }
}
