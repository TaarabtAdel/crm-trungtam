<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadFollowUpReminderNotification;
use App\Services\Tasks\AutoTaskService;
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

            try {
                $overdue = optional($lead->follow_up_at)->isPast();
                app(AutoTaskService::class)->ensureForUser(
                    $sales,
                    AutoTaskService::SOURCE_LEAD_FOLLOWUP,
                    (int) $lead->id,
                    [
                        'title' => ($overdue ? 'Follow-up quá hạn: ' : 'Follow-up lead: ').($lead->name ?? 'Lead #'.$lead->id),
                        'description' => 'Hạn xử lý: '.(optional($lead->follow_up_at)->format('d/m/Y H:i') ?: '—')
                            ."\nMở: ".route('admin.leads.show', $lead, absolute: false),
                        'priority' => $overdue ? 'urgent' : 'high',
                        'due_date' => $lead->follow_up_at,
                        'branch_id' => $lead->branch_id,
                        'status' => 'todo',
                    ]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info("Đã gửi {$sent} nhắc hạn lead.");

        return self::SUCCESS;
    }
}
