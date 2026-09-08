<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadFollowUpReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemindLeadFollowUpsCommand extends Command
{
    protected $signature = 'crm:remind-lead-followups {--days=1 : Số ngày trước hạn để nhắc (kèm quá hạn)}';

    protected $description = 'Nhắc Sales về lead đang Mới sắp tới hạn / quá hạn xử lý';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $until = now()->startOfDay()->addDays($days);

        $leads = Lead::query()
            ->with('assignedSales')
            ->where('status', 'new')
            ->whereNotNull('assigned_sales_id')
            ->whereNotNull('follow_up_at')
            ->whereDate('follow_up_at', '<=', $until->toDateString())
            ->get();

        $sent = 0;
        foreach ($leads as $lead) {
            /** @var User|null $sales */
            $sales = $lead->assignedSales;
            if (! $sales || ! $sales->is_active) {
                continue;
            }

            $already = DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $sales->id)
                ->where('type', LeadFollowUpReminderNotification::class)
                ->whereDate('created_at', now()->toDateString())
                ->where('data', 'like', '%"lead_id":'.$lead->id.'%')
                ->exists();

            if ($already) {
                continue;
            }

            $sales->notify(new LeadFollowUpReminderNotification($lead));
            $sent++;
        }

        $this->info("Đã gửi {$sent} nhắc hạn lead.");

        return self::SUCCESS;
    }
}
