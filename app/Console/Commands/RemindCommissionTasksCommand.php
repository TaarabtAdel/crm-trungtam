<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Commission;
use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindCommissionTasksCommand extends Command
{
    protected $signature = 'finance:remind-commissions
                            {--month= : Tháng Y-m (mặc định tháng trước)}';

    protected $description = 'Nhắc chốt / chi hoa hồng tháng';

    public function handle(AutoTaskService $auto): int
    {
        $month = $this->option('month')
            ? Carbon::parse((string) $this->option('month').'-01')
            : now()->subMonthNoOverflow()->startOfMonth();

        $label = $month->format('m/Y');
        $ym = (int) $month->format('Ym');

        $pendingByBranch = Commission::query()
            ->where('commissions.status', 'unpaid')
            ->leftJoin('invoices', 'commissions.invoice_id', '=', 'invoices.id')
            ->selectRaw('COALESCE(invoices.branch_id, 0) as branch_key, COUNT(*) as cnt')
            ->groupBy('branch_key')
            ->pluck('cnt', 'branch_key');

        if ($pendingByBranch->isEmpty()) {
            $this->info('Không có hoa hồng chờ chi.');

            return self::SUCCESS;
        }

        $created = 0;
        foreach ($pendingByBranch as $branchKey => $pending) {
            $branchId = (int) $branchKey > 0 ? (int) $branchKey : null;
            $recipients = Notifier::recipientsForPermission('finance.commissions.manage', [], $branchId);
            if ($recipients->isEmpty()) {
                $recipients = Notifier::recipientsForPermission('finance.commissions.view', [], $branchId);
            }
            if ($recipients->isEmpty()) {
                continue;
            }

            $branchName = $branchId
                ? (Branch::query()->find($branchId)?->name ?? ('CN #'.$branchId))
                : 'Chưa gắn CN';

            $auto->ensureForUsers(
                $recipients,
                AutoTaskService::SOURCE_COMMISSION,
                ($ym * 10000) + (int) $branchKey,
                [
                    'title' => 'Chốt hoa hồng · '.$branchName.' · '.$label,
                    'description' => "Có {$pending} khoản hoa hồng chưa chi ({$branchName}).\n"
                        .'Mở: '.route('admin.commissions.index', ['status' => 'unpaid'], absolute: false),
                    'priority' => 'medium',
                    'due_date' => $month->copy()->endOfMonth()->addDays(7)->setTime(18, 0),
                    'branch_id' => $branchId,
                    'status' => 'todo',
                ]
            );
            $created++;
        }

        if ($created === 0) {
            $this->warn('Không có user commissions phù hợp chi nhánh.');
        } else {
            $this->info("Đã tạo việc hoa hồng {$label} ({$created} chi nhánh).");
        }

        return self::SUCCESS;
    }
}
