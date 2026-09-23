<?php

namespace App\Console\Commands;

use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindPayrollTasksCommand extends Command
{
    protected $signature = 'finance:remind-payroll
                            {--month= : Tháng lương Y-m (mặc định tháng trước)}';

    protected $description = 'Nhắc chốt lương GV và lương NV cuối tháng';

    public function handle(AutoTaskService $auto): int
    {
        $month = $this->option('month')
            ? Carbon::parse((string) $this->option('month').'-01')
            : now()->subMonthNoOverflow()->startOfMonth();

        $billing = $month->format('Y-m');
        $label = $month->format('m/Y');
        $sourceId = (int) $month->format('Ym');
        $due = $month->copy()->endOfMonth()->addDays(5)->setTime(18, 0);

        $teacherRecipients = Notifier::recipientsForPermission('training.teachers.payroll');
        if ($teacherRecipients->isNotEmpty()) {
            $auto->ensureForUsers(
                $teacherRecipients,
                AutoTaskService::SOURCE_TEACHER_PAYROLL,
                $sourceId,
                [
                    'title' => 'Chốt lương giáo viên · '.$label,
                    'description' => "Rà soát công dạy / điều chỉnh / chi lương GV tháng {$label}.\n"
                        .'Mở: '.route('admin.finance.teacher-payroll', [
                            'month' => $month->month,
                            'year' => $month->year,
                        ], absolute: false),
                    'priority' => 'high',
                    'due_date' => $due,
                    'status' => 'todo',
                ]
            );
            $this->info('Đã tạo việc lương GV '.$label);
        }

        $staffRecipients = Notifier::recipientsForPermission('finance.staff_payroll.view');
        // ưu tiên ai có manage expenses / admin cũng xem được payroll
        if ($staffRecipients->isNotEmpty()) {
            $auto->ensureForUsers(
                $staffRecipients,
                AutoTaskService::SOURCE_STAFF_PAYROLL,
                $sourceId,
                [
                    'title' => 'Chốt lương nhân viên · '.$label,
                    'description' => "Rà soát công NV / thưởng phạt / chi lương tháng {$label}.\n"
                        .'Mở: '.route('admin.finance.staff-payroll', [
                            'month' => $month->month,
                            'year' => $month->year,
                        ], absolute: false),
                    'priority' => 'high',
                    'due_date' => $due,
                    'status' => 'todo',
                ]
            );
            $this->info('Đã tạo việc lương NV '.$label);
        }

        return self::SUCCESS;
    }
}
