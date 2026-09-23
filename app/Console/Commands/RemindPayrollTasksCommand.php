<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindPayrollTasksCommand extends Command
{
    protected $signature = 'finance:remind-payroll
                            {--month= : Tháng lương Y-m (mặc định tháng trước)}';

    protected $description = 'Nhắc chốt lương GV và lương NV cuối tháng (theo từng chi nhánh)';

    public function handle(AutoTaskService $auto): int
    {
        $month = $this->option('month')
            ? Carbon::parse((string) $this->option('month').'-01')
            : now()->subMonthNoOverflow()->startOfMonth();

        $label = $month->format('m/Y');
        $ym = (int) $month->format('Ym');
        $due = $month->copy()->endOfMonth()->addDays(5)->setTime(18, 0);

        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get();
        if ($branches->isEmpty()) {
            $this->warn('Không có chi nhánh active.');

            return self::SUCCESS;
        }

        foreach ($branches as $branch) {
            $branchId = (int) $branch->id;
            $sourceId = ($ym * 10000) + $branchId;

            $teacherRecipients = Notifier::recipientsForPermission('training.teachers.payroll', [], $branchId);
            if ($teacherRecipients->isNotEmpty()) {
                $auto->ensureForUsers(
                    $teacherRecipients,
                    AutoTaskService::SOURCE_TEACHER_PAYROLL,
                    $sourceId,
                    [
                        'title' => 'Chốt lương giáo viên · '.$branch->name.' · '.$label,
                        'description' => "Rà soát công dạy / điều chỉnh / chi lương GV tháng {$label} — {$branch->name}.\n"
                            .'Mở: '.route('admin.finance.teacher-payroll', [
                                'month' => $month->month,
                                'year' => $month->year,
                            ], absolute: false),
                        'priority' => 'high',
                        'due_date' => $due,
                        'branch_id' => $branchId,
                        'status' => 'todo',
                    ]
                );
                $this->info('Đã tạo việc lương GV '.$label.' · '.$branch->name);
            }

            $staffRecipients = Notifier::recipientsForPermission('finance.staff_payroll.view', [], $branchId);
            if ($staffRecipients->isNotEmpty()) {
                $auto->ensureForUsers(
                    $staffRecipients,
                    AutoTaskService::SOURCE_STAFF_PAYROLL,
                    $sourceId,
                    [
                        'title' => 'Chốt lương nhân viên · '.$branch->name.' · '.$label,
                        'description' => "Rà soát công NV / thưởng phạt / chi lương tháng {$label} — {$branch->name}.\n"
                            .'Mở: '.route('admin.finance.staff-payroll', [
                                'month' => $month->month,
                                'year' => $month->year,
                            ], absolute: false),
                        'priority' => 'high',
                        'due_date' => $due,
                        'branch_id' => $branchId,
                        'status' => 'todo',
                    ]
                );
                $this->info('Đã tạo việc lương NV '.$label.' · '.$branch->name);
            }
        }

        return self::SUCCESS;
    }
}
