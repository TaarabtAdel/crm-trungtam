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

        $missing = [];
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
                $missing[] = [
                    'class' => $class,
                    'need' => $need,
                    'total' => $studentIds->count(),
                ];
            }
        }

        if ($missing === []) {
            $this->info("Tháng {$month}: mọi lớp monthly đã có HĐ đủ.");

            return self::SUCCESS;
        }

        $recipients = Notifier::recipientsForPermission('finance.invoices.manage');
        if ($recipients->isEmpty()) {
            $this->warn('Không có user finance.invoices.manage.');

            return self::SUCCESS;
        }

        $lines = collect($missing)->map(function ($row) {
            /** @var CourseClass $class */
            $class = $row['class'];

            return '- '.$class->name.': còn '.$row['need'].'/'.$row['total'].' HV chưa có HĐ';
        })->implode("\n");

        $sourceId = (int) str_replace('-', '', $month); // YYYYMM
        $label = Carbon::parse($month.'-01')->format('m/Y');

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
                'status' => 'todo',
            ]
        );

        $this->info('Đã tạo việc nhắc HĐ tháng '.$label.' ('.count($missing).' lớp thiếu).');

        return self::SUCCESS;
    }
}
