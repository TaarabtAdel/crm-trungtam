<?php

namespace App\Services;

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
     * @return array<int, array{key: string, command: string, options?: array<string, mixed>, after?: string, interval: string}>
     */
    protected function jobs(): array
    {
        return [
            [
                'key' => 'finance_remind_debts',
                'command' => 'finance:remind-debts',
                'options' => ['--days' => 3],
                'after' => '08:00',
                'interval' => 'daily',
            ],
            [
                'key' => 'crm_remind_lead_followups',
                'command' => 'crm:remind-lead-followups',
                'options' => ['--days' => 1],
                'after' => '08:15',
                'interval' => 'daily',
            ],
            [
                'key' => 'crm_remind_stale_sessions',
                'command' => 'crm:remind-stale-sessions',
                'options' => ['--days' => 7],
                'interval' => 'hourly',
            ],
            [
                'key' => 'crm_remind_upcoming_sessions',
                'command' => 'crm:remind-upcoming-sessions',
                'options' => ['--minutes' => 120, '--window' => 12],
                'interval' => 'every_15m',
            ],
        ];
    }

    /**
     * @param  array{key: string, after?: string, interval: string}  $job
     */
    protected function isDue(array $job): bool
    {
        if (Cache::has($this->cacheKey($job))) {
            return false;
        }

        if (($job['interval'] ?? '') === 'daily' && isset($job['after'])) {
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
            default => now()->toDateString(),
        };

        return 'reminder_scheduler.ran.'.$job['key'].'.'.$suffix;
    }
}
