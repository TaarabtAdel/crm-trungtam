<?php

namespace App\Console\Commands;

use App\Services\Tasks\TaskReminderService;
use Illuminate\Console\Command;

class RemindTaskDeadlinesCommand extends Command
{
    protected $signature = 'tasks:remind-deadlines';

    protected $description = 'Nhắc hạn công việc (sắp đến hạn / đến hạn / quá hạn)';

    public function handle(TaskReminderService $service): int
    {
        $result = $service->run();
        $this->info('Task reminders: '.json_encode($result));

        return self::SUCCESS;
    }
}
