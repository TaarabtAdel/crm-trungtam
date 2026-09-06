<?php

namespace App\Console\Commands;

use App\Services\Finance\DebtReminderService;
use Illuminate\Console\Command;

class RemindDebtsCommand extends Command
{
    protected $signature = 'finance:remind-debts {--days=3 : Số ngày trước hạn để nhắc}';

    protected $description = 'Gửi nhắc nợ học phí (email/notification + SMS stub)';

    public function handle(DebtReminderService $service): int
    {
        $stats = $service->run((int) $this->option('days'));
        $this->info('Nhắc nợ xong: email='.$stats['email'].', notification='.$stats['notification'].', sms_stub='.$stats['sms']);

        return self::SUCCESS;
    }
}
