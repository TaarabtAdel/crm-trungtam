<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\CourseClass;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class ClassTimetableGenerator
{
    /** @var array<string, int> ISO day: 1=Mon ... 7=Sun */
    public const DAY_MAP = [
        'T2' => 1,
        'T3' => 2,
        'T4' => 3,
        'T5' => 4,
        'T6' => 5,
        'T7' => 6,
        'CN' => 7,
    ];

    /**
     * @return array{created:int, skipped:int, deleted:int, dates:array<int, string>}
     */
    public function generate(
        CourseClass $class,
        string $from,
        string $to,
        bool $replaceScheduled = true,
    ): array {
        $days = collect($class->schedule_days ?? [])
            ->map(fn ($d) => self::DAY_MAP[$d] ?? null)
            ->filter()
            ->values()
            ->all();

        if ($days === []) {
            throw new \InvalidArgumentException('Lớp chưa có lịch học (thứ trong tuần). Vui lòng cập nhật lớp trước.');
        }

        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->startOfDay();
        if ($toDate->lt($fromDate)) {
            throw new \InvalidArgumentException('Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.');
        }

        if ($fromDate->diffInDays($toDate) > 366) {
            throw new \InvalidArgumentException('Khoảng tạo thời khóa biểu tối đa 12 tháng.');
        }

        $created = 0;
        $skipped = 0;
        $deleted = 0;
        $dates = [];

        DB::transaction(function () use ($class, $fromDate, $toDate, $days, $replaceScheduled, &$created, &$skipped, &$deleted, &$dates) {
            if ($replaceScheduled) {
                $deleted = ClassSession::query()
                    ->where('class_id', $class->id)
                    ->where('status', 'scheduled')
                    ->whereDate('session_date', '>=', $fromDate->toDateString())
                    ->whereDate('session_date', '<=', $toDate->toDateString())
                    ->delete();
            }

            $period = CarbonPeriod::create($fromDate, $toDate);
            foreach ($period as $date) {
                /** @var Carbon $date */
                if (! in_array($date->dayOfWeekIso, $days, true)) {
                    continue;
                }

                $dateStr = $date->toDateString();
                $existing = ClassSession::query()
                    ->where('class_id', $class->id)
                    ->whereDate('session_date', $dateStr)
                    ->first();

                if ($existing) {
                    if ($existing->status !== 'scheduled') {
                        $skipped++;
                        continue;
                    }
                    $existing->update([
                        'teacher_id' => $class->teacher_id,
                        'start_time' => $class->start_time,
                        'end_time' => $class->end_time,
                        'status' => 'scheduled',
                    ]);
                    $skipped++;
                    $dates[] = $dateStr;
                    continue;
                }

                ClassSession::query()->create([
                    'class_id' => $class->id,
                    'teacher_id' => $class->teacher_id,
                    'session_date' => $dateStr,
                    'start_time' => $class->start_time,
                    'end_time' => $class->end_time,
                    'status' => 'scheduled',
                    'notes' => null,
                ]);
                $created++;
                $dates[] = $dateStr;
            }
        });

        return compact('created', 'skipped', 'deleted', 'dates');
    }

    public static function dayLabel(Carbon|string $date): string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $labels = [1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN'];

        return $labels[$carbon->dayOfWeekIso] ?? '';
    }
}
