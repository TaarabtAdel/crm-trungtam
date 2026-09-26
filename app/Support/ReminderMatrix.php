<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class ReminderMatrix
{
    /**
     * Jobs cho ReminderScheduler (chỉ field kỹ thuật).
     *
     * @return list<array<string, mixed>>
     */
    public static function schedulerJobs(): array
    {
        $jobs = config('reminder_matrix.scheduler_jobs', []);

        return array_map(function (array $job) {
            return array_filter([
                'key' => $job['key'] ?? null,
                'command' => $job['command'] ?? null,
                'options' => $job['options'] ?? null,
                'after' => $job['after'] ?? null,
                'interval' => $job['interval'] ?? 'daily',
                'weekday' => $job['weekday'] ?? null,
                'day' => $job['day'] ?? null,
            ], fn ($v) => $v !== null);
        }, $jobs);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public static function schedulerGrouped(): array
    {
        $groups = [];
        foreach (config('reminder_matrix.scheduler_jobs', []) as $job) {
            $g = (string) ($job['group'] ?? 'Khác');
            $groups[$g][] = $job;
        }

        return $groups;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public static function eventGrouped(): array
    {
        $groups = [];
        foreach (config('reminder_matrix.event_entries', []) as $entry) {
            $g = (string) ($entry['group'] ?? 'Khác');
            $groups[$g][] = $entry;
        }

        return $groups;
    }

    public static function outputLabel(string $output): string
    {
        return match ($output) {
            'task' => 'Board công việc',
            'notify' => 'Thông báo',
            'both' => 'Task + thông báo',
            default => $output,
        };
    }

    /**
     * Trạng thái cache “đã chạy” cho job scheduler (tick gần nhất).
     */
    public static function schedulerRunHint(array $job): ?string
    {
        $key = $job['key'] ?? null;
        if (! $key) {
            return null;
        }
        $interval = $job['interval'] ?? 'daily';
        $suffix = match ($interval) {
            'hourly' => now()->format('Y-m-d-H'),
            'every_15m' => now()->format('Y-m-d-H').'-'.(int) floor(now()->minute / 15),
            'weekly' => now()->format('o-\WW'),
            'monthly' => now()->format('Y-m'),
            default => now()->toDateString(),
        };
        $cacheKey = 'reminder_scheduler.ran.'.$key.'.'.$suffix;
        $ran = Cache::get($cacheKey);

        return $ran ? (string) $ran : null;
    }
}
