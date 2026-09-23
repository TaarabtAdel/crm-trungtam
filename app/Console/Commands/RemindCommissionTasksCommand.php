<?php

namespace App\Console\Commands;

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
        $sourceId = (int) $month->format('Ym');
        $pending = Commission::query()->where('status', 'unpaid')->count();

        if ($pending === 0) {
            $this->info('Không có hoa hồng chờ chi.');

            return self::SUCCESS;
        }

        $recipients = Notifier::recipientsForPermission('finance.commissions.manage');
        if ($recipients->isEmpty()) {
            $recipients = Notifier::recipientsForPermission('finance.commissions.view');
        }
        if ($recipients->isEmpty()) {
            $this->warn('Không có user commissions.');

            return self::SUCCESS;
        }

        $auto->ensureForUsers(
            $recipients,
            AutoTaskService::SOURCE_COMMISSION,
            $sourceId,
            [
                'title' => 'Chốt hoa hồng · '.$label,
                'description' => "Có {$pending} khoản hoa hồng chưa chi.\n"
                    .'Mở: '.route('admin.commissions.index', ['status' => 'unpaid'], absolute: false),
                'priority' => 'medium',
                'due_date' => $month->copy()->endOfMonth()->addDays(7)->setTime(18, 0),
                'status' => 'todo',
            ]
        );

        $this->info("Đã tạo việc hoa hồng {$label} ({$pending} pending).");

        return self::SUCCESS;
    }
}
