<?php

namespace App\Console\Commands;

use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
use Illuminate\Console\Command;

class RemindBackupTasksCommand extends Command
{
    protected $signature = 'system:remind-backup';

    protected $description = 'Nhắc tạo backup CSDL định kỳ';

    public function handle(AutoTaskService $auto): int
    {
        $recipients = Notifier::recipientsForPermission('system.backups.manage');
        if ($recipients->isEmpty()) {
            $this->warn('Không có user system.backups.manage.');

            return self::SUCCESS;
        }

        // source_id = ngày ISO tuần (YYYYWW)
        $sourceId = (int) now()->format('oW');

        $auto->ensureForUsers(
            $recipients,
            AutoTaskService::SOURCE_BACKUP,
            $sourceId,
            [
                'title' => 'Backup CSDL · tuần '.now()->format('W/Y'),
                'description' => "Tạo bản sao lưu database định kỳ.\n"
                    .'Mở: '.route('admin.backups.index', absolute: false),
                'priority' => 'medium',
                'due_date' => now()->endOfWeek()->setTime(18, 0),
                'status' => 'todo',
            ]
        );

        $this->info('Đã tạo việc nhắc backup tuần này.');

        return self::SUCCESS;
    }
}
