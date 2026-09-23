<?php

namespace App\Console\Commands;

use App\Models\CourseClass;
use App\Models\Invoice;
use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindMonthlyInvoicesCommand extends Command
{
    protected $signature = 'finance:remind-monthly-invoices
                            {--month= : Tháng HĐ Y-m (mặc định tháng hiện tại)}';

    protected $description = 'Nhắc tạo hóa đơn học phí tháng cho lớp thu theo tháng';

    public function handle(AutoTaskService $auto): int
    {
        $month = $this->option('month')
            ? Carbon::parse((string) $this->option('month').'-01')->format('Y-m')
            : now()->format('Y-m');

        $classes = CourseClass::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('tuition_type', 'monthly')
                    ->orWhereNull('tuition_type');
            })
            ->withCount('students')
            ->having('students_count', '>', 0)
            ->get();

        /** @var array<int, list<array{class: CourseClass, need: int, total: int}>> $byBranch */
        $byBranch = [];
        foreach ($classes as $class) {
            $studentIds = $class->students()->pluck('students.id');
            if ($studentIds->isEmpty()) {
                continue;
            }

            $invoiced = Invoice::query()
                ->where('class_id', $class->id)
                ->where('billing_month', $month)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id')
                ->unique();

            $need = $studentIds->diff($invoiced)->count();
            if ($need > 0) {
                $branchKey = $class->branch_id ? (int) $class->branch_id : 0;
                $byBranch[$branchKey][] = [
                    'class' => $class,
                    'need' => $need,
                    'total' => $studentIds->count(),
                ];
            }
        }

        if ($byBranch === []) {
            $this->info("Tháng {$month}: mọi lớp monthly đã có HĐ đủ.");

            return self::SUCCESS;
        }

        $label = Carbon::parse($month.'-01')->format('m/Y');
        $monthNum = (int) str_replace('-', '', $month); // YYYYMM
        $createdBranches = 0;

        foreach ($byBranch as $branchId => $missing) {
            $forBranch = $branchId > 0 ? $branchId : null;
            $recipients = Notifier::recipientsForPermission('finance.invoices.manage', [], $forBranch);
            if ($recipients->isEmpty()) {
                continue;
            }

            $lines = collect($missing)->map(function ($row) {
                /** @var CourseClass $class */
                $class = $row['class'];

                return '- '.$class->name.': còn '.$row['need'].'/'.$row['total'].' HV chưa có HĐ';
            })->implode("\n");

            // source_id = YYYYMM * 10000 + branch (HQ/null = 0)
            $sourceId = ($monthNum * 10000) + (int) $branchId;

            $auto->ensureForUsers(
                $recipients,
                AutoTaskService::SOURCE_MONTHLY_INVOICE,
                $sourceId,
                [
                    'title' => 'Tạo HĐ học phí tháng '.$label,
                    'description' => "Các lớp còn thiếu hóa đơn tháng {$label}:\n{$lines}\n"
                        .'Mở: '.route('admin.invoices.index', absolute: false),
                    'priority' => 'high',
                    'due_date' => Carbon::parse($month.'-01')->endOfMonth()->setTime(18, 0),
                    'branch_id' => $forBranch,
                    'status' => 'todo',
                ]
            );
            $createdBranches++;
        }

        $this->info('Đã tạo việc nhắc HĐ tháng '.$label.' ('.$createdBranches.' chi nhánh).');

        return self::SUCCESS;
    }
}
