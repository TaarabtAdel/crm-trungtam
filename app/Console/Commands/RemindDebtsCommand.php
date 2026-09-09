<?php

namespace App\Console\Commands;

use App\Services\Finance\DebtReminderService;
use Illuminate\Console\Command;

class RemindDebtsCommand extends Command
{
    protected $signature = 'finance:remind-debts {--days=3 : Số ngày trước hạn để nhắc}';

    protected $description = 'Gửi nhắc nợ học phí (email / in-app / Zalo PH)';

    public function handle(DebtReminderService $service): int
    {
        $stats = $service->run((int) $this->option('days'));
        $this->info('Nhắc nợ xong: email='.($stats['email'] ?? 0)
            .', notification='.($stats['notification'] ?? 0)
            .', zalo='.($stats['zalo'] ?? 0));

        return self::SUCCESS;
    }
}
