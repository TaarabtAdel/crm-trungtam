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

    /**
     * Mô tả hình thức nhắc (gộp board + thông báo) cho người dùng nghiệp vụ.
     */
    public static function deliverySummary(array $row): string
    {
        $parts = [];
        $out = (string) ($row['output'] ?? '');

        if ($out === 'task' || $out === 'both') {
            $parts[] = 'Tạo việc trên board Công việc';
        }

        $notify = trim((string) ($row['notifications'] ?? ''));
        if ($notify !== '' && $notify !== '—') {
            $parts[] = self::humanizeNotifyText($notify);
        } elseif ($out === 'notify') {
            $parts[] = 'Chuông thông báo trong app';
        }

        return $parts !== [] ? implode('. ', $parts) : '—';
    }

    public static function humanizeNotifyText(string $text): string
    {
        $map = [
            'In-app' => 'Chuông thông báo trong app',
            'in-app' => 'Chuông thông báo trong app',
            'Email' => 'Email',
            'Zalo PH' => 'Zalo phụ huynh',
            'Zalo' => 'Zalo',
        ];
        $out = $text;
        foreach ($map as $from => $to) {
            $out = str_replace($from, $to, $out);
        }
        $out = preg_replace('/Mẫu session_reminder/', 'Theo mẫu tin nhắn trước buổi học', $out);
        $out = preg_replace('/TaskEventNotification/', 'Thông báo công việc', $out);
        $out = preg_replace('/ZNS/', 'Zalo', $out);

        return trim(preg_replace('/\s+/', ' ', $out));
    }

    public static function formatRanAt(?string $ran): ?string
    {
        if ($ran === null || $ran === '') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($ran)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $ran;
        }
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
