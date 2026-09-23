<?php

namespace App\Console\Commands;

use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
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

        if (! $this->option('force') && $date->isWeekend()) {
            $this->info('Cuối tuần — bỏ qua tạo việc chấm công (dùng --force nếu cần).');

            return self::SUCCESS;
        }

        $recipients = Notifier::recipientsForPermission('system.staff_attendances.manage');
        if ($recipients->isEmpty()) {
            $this->warn('Không có user nào có quyền system.staff_attendances.manage.');

            return self::SUCCESS;
        }

        $sourceId = (int) $date->format('Ymd');
        $label = $date->format('d/m/Y');
        $url = route('admin.staff-attendances.index', [
            'mode' => 'day',
            'date' => $date->toDateString(),
        ], absolute: false);

        $auto->ensureForUsers(
            $recipients,
            AutoTaskService::SOURCE_STAFF_ATTENDANCE,
            $sourceId,
            [
                'title' => 'Chấm công nhân viên · '.$label,
                'description' => "Chấm công ngày {$label} cho nhân viên (có thể chấm nhiều người cùng lúc).\n"
                    ."Mở: {$url}",
                'priority' => 'high',
                'due_date' => $date->copy()->setTime(18, 0),
                'status' => 'todo',
            ]
        );

        $this->info('Đã tạo/cập nhật '.$recipients->count().' việc chấm công cho ngày '.$label.'.');

        return self::SUCCESS;
    }
}
