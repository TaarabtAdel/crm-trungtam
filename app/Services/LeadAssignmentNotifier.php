<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use App\Services\Tasks\AutoTaskService;

class LeadAssignmentNotifier
{
    public function notifyIfAssigned(?Lead $lead, ?int $salesId, ?int $actorId = null): void
    {
        if (! $lead || ! $salesId) {
            return;
        }

        if ($actorId && (int) $actorId === (int) $salesId) {
            // Tự gán cho mình: vẫn tạo việc, không gửi notify
            $this->ensureAssignedTask($lead, $salesId, $actorId);

            return;
        }

        $sales = User::query()->where('id', $salesId)->where('is_active', true)->first();
        if (! $sales) {
            return;
        }

        $sales->notify(new LeadAssignedNotification($lead));
        $this->ensureAssignedTask($lead, $salesId, $actorId);
    }

    public function notifyOnChange(Lead $lead, ?int $oldSalesId, ?int $newSalesId, ?int $actorId = null): void
    {
        if (! $newSalesId || (int) $newSalesId === (int) $oldSalesId) {
            return;
        }

        $this->notifyIfAssigned($lead, $newSalesId, $actorId);
    }

    protected function ensureAssignedTask(Lead $lead, int $salesId, ?int $actorId = null): void
    {
        try {
            $sales = User::query()->find($salesId);
            if (! $sales || ! $sales->is_active) {
                return;
            }

            $due = $lead->follow_up_at ?: now()->addDays(2)->endOfDay();
            $overdue = optional($lead->follow_up_at)->isPast();

            app(AutoTaskService::class)->ensureForUser(
                $sales,
                AutoTaskService::SOURCE_LEAD_FOLLOWUP,
                (int) $lead->id,
                [
                    'title' => 'Lead được giao: '.($lead->name ?? 'Lead #'.$lead->id),
                    'description' => 'Trạng thái: '.($lead->status ?? 'new')
                        ."\nHạn xử lý: ".(optional($lead->follow_up_at)->format('d/m/Y H:i') ?: '—')
                        ."\nMở: ".route('admin.leads.show', $lead, absolute: false),
                    'priority' => $overdue ? 'urgent' : 'high',
                    'due_date' => $due,
                    'branch_id' => $lead->branch_id,
                    'creator_id' => $actorId ?: $salesId,
                    'status' => 'todo',
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
