<?php

namespace App\Services;

use App\Support\ReminderMatrix;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReminderScheduler
{
    /**
     * Chạy các lệnh nhắc đến hạn (dùng khi không có cron / cPanel).
     *
     * @return array{ran: array<int, string>, skipped: array<int, string>, locked: bool}
     */
    public function tick(bool $force = false): array
    {
        $lock = Cache::lock('reminder_scheduler.tick', 55);

        if (! $lock->get()) {
            return ['ran' => [], 'skipped' => [], 'locked' => true];
        }

        try {
            $ran = [];
            $skipped = [];

            foreach ($this->jobs() as $job) {
                if (! $force && ! $this->isDue($job)) {
                    $skipped[] = $job['command'];

                    continue;
                }

                try {
                    Artisan::call($job['command'], $job['options'] ?? []);
                    $this->markRan($job);
                    $ran[] = $job['command'];
                } catch (\Throwable $e) {
                    Log::warning('ReminderScheduler failed: '.$job['command'], [
                        'message' => $e->getMessage(),
                    ]);
                    $skipped[] = $job['command'].' (error)';
                }
            }

            return ['ran' => $ran, 'skipped' => $skipped, 'locked' => false];
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<int, array{key: string, command: string, options?: array<string, mixed>, after?: string, interval: string, weekday?: int, day?: int}>
     */
    protected function jobs(): array
    {
        return ReminderMatrix::schedulerJobs();
    }

    /**
     * @param  array{key: string, after?: string, interval: string, weekday?: int, day?: int}  $job
     */
    protected function isDue(array $job): bool
    {
        if (Cache::has($this->cacheKey($job))) {
            return false;
        }

        $interval = $job['interval'] ?? 'daily';

        if ($interval === 'weekly') {
            $weekday = (int) ($job['weekday'] ?? 1); // 1=Mon … 7=Sun (ISO)
            if ((int) now()->dayOfWeekIso !== $weekday) {
                return false;
            }
        }

        if ($interval === 'monthly') {
            $day = (int) ($job['day'] ?? 1);
            // Nếu tháng không có ngày đó (31), chạy vào ngày cuối tháng
            $targetDay = min($day, (int) now()->daysInMonth);
            if ((int) now()->day !== $targetDay) {
                return false;
            }
        }

        if (isset($job['after']) && in_array($interval, ['daily', 'weekly', 'monthly'], true)) {
            [$h, $m] = array_map('intval', explode(':', $job['after']));
            if (now()->lt(now()->copy()->setTime($h, $m, 0))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{key: string, interval: string}  $job
     */
    protected function markRan(array $job): void
    {
        $ttl = match ($job['interval'] ?? '') {
            'hourly' => now()->addHour(),
            'every_15m' => now()->addMinutes(15),
            'weekly' => now()->endOfWeek(),
            'monthly' => now()->endOfMonth(),
            default => now()->endOfDay(),
        };

        Cache::put($this->cacheKey($job), now()->toDateTimeString(), $ttl);
    }

    /**
     * @param  array{key: string, interval: string}  $job
     */
    protected function cacheKey(array $job): string
    {
        $suffix = match ($job['interval'] ?? '') {
            'hourly' => now()->format('Y-m-d-H'),
            'every_15m' => now()->format('Y-m-d-H').'-'.(int) floor(now()->minute / 15),
            'weekly' => now()->format('o-\WW'),
            'monthly' => now()->format('Y-m'),
            default => now()->toDateString(),
        };

        return 'reminder_scheduler.ran.'.$job['key'].'.'.$suffix;
    }
}
