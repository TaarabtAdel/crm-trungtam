<?php

namespace App\Services\Tasks;

use App\Models\Interaction;
use App\Models\User;

/**
 * Tạo / đóng việc từ lịch hẹn CRM (Interaction).
 */
class InteractionTaskService
{
    public function __construct(protected AutoTaskService $auto) {}

    public function sync(Interaction $interaction, ?int $actorId = null): void
    {
        $interaction->loadMissing(['lead', 'sales']);

        if ($interaction->status !== 'upcoming' || ! $interaction->scheduled_at || ! $interaction->sales_id) {
            $this->auto->completeBySource(
                AutoTaskService::SOURCE_INTERACTION,
                (int) $interaction->id,
                $actorId
            );

            return;
        }

        $sales = $interaction->sales;
        if (! $sales || ! $sales->is_active) {
            $sales = User::query()->find($interaction->sales_id);
        }
        if (! $sales || ! $sales->is_active) {
            return;
        }

        $when = $interaction->scheduled_at;
        $leadName = $interaction->lead?->name ?? 'Lead';
        $overdue = $when->isPast();

        $this->auto->ensureForUser(
            $sales,
            AutoTaskService::SOURCE_INTERACTION,
            (int) $interaction->id,
            [
                'title' => ($overdue ? 'Lịch hẹn quá hạn: ' : 'Lịch hẹn: ')
                    .$interaction->type.' · '.$leadName,
                'description' => 'Thời gian: '.$when->format('d/m/Y H:i')
                    .($interaction->notes ? "\nGhi chú: ".$interaction->notes : '')
                    ."\nMở: ".route('admin.interactions.index', absolute: false)
                    .( $interaction->lead_id
                        ? "\nLead: ".route('admin.leads.show', $interaction->lead_id, absolute: false)
                        : ''),
                'priority' => $overdue ? 'urgent' : 'high',
                'due_date' => $when,
                'branch_id' => $interaction->branch_id ?? $sales->branch_id,
                'creator_id' => $actorId ?: $sales->id,
                'status' => 'todo',
            ]
        );
    }

    public function forget(Interaction $interaction, ?int $actorId = null): void
    {
        $this->auto->completeBySource(
            AutoTaskService::SOURCE_INTERACTION,
            (int) $interaction->id,
            $actorId
        );
    }

    /**
     * Cron: nhắc lịch upcoming trong cửa sổ sắp tới / đã quá hạn gần đây.
     */
    public function remindUpcoming(int $withinHours = 48, int $overdueDays = 3): int
    {
        $from = now()->subDays($overdueDays);
        $to = now()->addHours($withinHours);

        $items = Interaction::query()
            ->with(['lead', 'sales'])
            ->where('status', 'upcoming')
            ->whereNotNull('scheduled_at')
            ->whereNotNull('sales_id')
            ->whereBetween('scheduled_at', [$from, $to])
            ->get();

        $n = 0;
        foreach ($items as $item) {
            try {
                $this->sync($item);
                $n++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $n;
    }
}
