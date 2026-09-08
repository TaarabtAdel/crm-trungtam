<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;

class LeadAssignmentNotifier
{
    public function notifyIfAssigned(?Lead $lead, ?int $salesId, ?int $actorId = null): void
    {
        if (! $lead || ! $salesId) {
            return;
        }

        if ($actorId && (int) $actorId === (int) $salesId) {
            return;
        }

        $sales = User::query()->where('id', $salesId)->where('is_active', true)->first();
        if (! $sales) {
            return;
        }

        $sales->notify(new LeadAssignedNotification($lead));
    }

    public function notifyOnChange(Lead $lead, ?int $oldSalesId, ?int $newSalesId, ?int $actorId = null): void
    {
        if (! $newSalesId || (int) $newSalesId === (int) $oldSalesId) {
            return;
        }

        $this->notifyIfAssigned($lead, $newSalesId, $actorId);
    }
}
