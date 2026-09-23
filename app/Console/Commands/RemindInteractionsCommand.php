<?php

namespace App\Console\Commands;

use App\Services\Tasks\InteractionTaskService;
use Illuminate\Console\Command;

class RemindInteractionsCommand extends Command
{
    protected $signature = 'crm:remind-interactions
                            {--hours=48 : Cửa sổ sắp tới (giờ)}
                            {--overdue-days=3 : Quá hạn trong N ngày gần đây}';

    protected $description = 'Tạo / cập nhật việc Công việc cho lịch hẹn CRM sắp tới hoặc quá hạn';

    public function handle(InteractionTaskService $service): int
    {
        $n = $service->remindUpcoming(
            max(1, (int) $this->option('hours')),
            max(0, (int) $this->option('overdue-days'))
        );
        $this->info("Đã đồng bộ {$n} lịch hẹn lên board Công việc.");

        return self::SUCCESS;
    }
}
