<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateStaffAttendanceTasksCommand extends Command
{
    protected $signature = 'tasks:create-staff-attendance
                            {--date= : Ngày công (Y-m-d), mặc định hôm nay}
                            {--force : Tạo cả cuối tuần}';

    protected $description = 'Tạo việc “Chấm công nhân viên” trên board Công việc (chạy sáng sớm mỗi ngày)';

    public function handle(AutoTaskService $auto): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->startOfDay();

        if (! $this->option('force') && ! WorkingDays::isWorkingDay($date)) {
            $this->info('Không phải ngày làm việc theo Cài đặt — bỏ qua tạo việc chấm công (dùng --force nếu cần).');

            return self::SUCCESS;
        }

        $label = $date->format('d/m/Y');
        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get();
        if ($branches->isEmpty()) {
            $this->warn('Không có chi nhánh active.');

            return self::SUCCESS;
        }

        $created = 0;
        foreach ($branches as $branch) {
            $recipients = Notifier::recipientsForPermission(
                'system.staff_attendances.manage',
                [],
                (int) $branch->id
            );
            if ($recipients->isEmpty()) {
                continue;
            }

            // source_id = YYYYMMDD + branch (tránh đụng giữa các CN)
            $sourceId = (int) ($date->format('Ymd').sprintf('%04d', $branch->id));
            $url = route('admin.staff-attendances.index', [
                'mode' => 'day',
                'date' => $date->toDateString(),
            ], absolute: false);

            $auto->ensureForUsers(
                $recipients,
                AutoTaskService::SOURCE_STAFF_ATTENDANCE,
                $sourceId,
                [
                    'title' => 'Chấm công NV · '.$branch->name.' · '.$label,
                    'description' => "Chấm công ngày {$label} — chi nhánh {$branch->name}.\n"
                        ."Mở: {$url}",
                    'priority' => 'high',
                    'due_date' => $date->copy()->setTime(18, 0),
                    'branch_id' => $branch->id,
                    'status' => 'todo',
                ]
            );
            $created += $recipients->count();
        }

        $this->info("Đã tạo/cập nhật {$created} việc chấm công cho ngày {$label}.");

        return self::SUCCESS;
    }
}
