<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;

/**
 * Ngày làm việc của trung tâm (ISO: 1 = T2 … 7 = CN).
 * Dùng cho việc tự tạo chấm công NV và các lịch theo ngày.
 */
class WorkingDays
{
    /** @var list<int> */
    public const DEFAULT = [1, 2, 3, 4, 5];

    /** @return array<int, string> */
    public static function labels(): array
    {
        return [
            1 => 'Thứ 2',
            2 => 'Thứ 3',
            3 => 'Thứ 4',
            4 => 'Thứ 5',
            5 => 'Thứ 6',
            6 => 'Thứ 7',
            7 => 'Chủ nhật',
        ];
    }

    /**
     * @return list<int>
     */
    public static function activeIsoWeekdays(): array
    {
        return self::parse((string) Setting::get('working_days', implode(',', self::DEFAULT)));
    }

    /**
     * @return list<int>
     */
    public static function parse(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', trim($raw)) ?: [];
        $days = [];
        foreach ($parts as $part) {
            $d = (int) $part;
            if ($d >= 1 && $d <= 7) {
                $days[$d] = $d;
            }
        }
        $days = array_values($days);
        sort($days);

        return $days !== [] ? $days : self::DEFAULT;
    }

    public static function isWorkingDay(Carbon $date): bool
    {
        return in_array((int) $date->dayOfWeekIso, self::activeIsoWeekdays(), true);
    }

    /**
     * @param  list<int|string>|null  $checked
     */
    public static function storeFromRequest(?array $checked): void
    {
        $days = collect($checked ?? [])
            ->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d >= 1 && $d <= 7)
            ->unique()
            ->sort()
            ->values();

        if ($days->isEmpty()) {
            $days = collect(self::DEFAULT);
        }

        Setting::set('working_days', $days->implode(','));
    }
}
