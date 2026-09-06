<?php

namespace App\Services\Finance;

use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Invoice;

class CommissionService
{
    public function resolvePercent(?int $classId, float $revenue = 0): float
    {
        $classRule = null;
        if ($classId) {
            $classRule = CommissionRule::query()
                ->where('is_active', true)
                ->where('scope', 'class')
                ->where('class_id', $classId)
                ->orderByDesc('tier_min_revenue')
                ->get()
                ->first(function (CommissionRule $rule) use ($revenue) {
                    return $rule->tier_min_revenue === null || (float) $rule->tier_min_revenue <= $revenue;
                });
        }

        if ($classRule) {
            return (float) $classRule->percent;
        }

        $global = CommissionRule::query()
            ->where('is_active', true)
            ->where('scope', 'global')
            ->orderByDesc('tier_min_revenue')
            ->get()
            ->first(function (CommissionRule $rule) use ($revenue) {
                return $rule->tier_min_revenue === null || (float) $rule->tier_min_revenue <= $revenue;
            });

        return $global ? (float) $global->percent : 0.0;
    }

    public function createForPaidInvoice(Invoice $invoice): ?Commission
    {
        if (! $invoice->sales_id || $invoice->status !== 'paid') {
            return null;
        }

        $existing = Commission::where('invoice_id', $invoice->id)->where('sales_id', $invoice->sales_id)->first();
        if ($existing) {
            return $existing;
        }

        $percent = $this->resolvePercent($invoice->class_id, (float) $invoice->amount);
        if ($percent <= 0) {
            return null;
        }

        $amount = round(((float) $invoice->amount) * $percent / 100);

        return Commission::create([
            'sales_id' => $invoice->sales_id,
            'invoice_id' => $invoice->id,
            'percent' => $percent,
            'amount' => $amount,
            'status' => 'unpaid',
        ]);
    }

    public function markPaid(Commission $commission): Commission
    {
        $commission->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $commission->fresh();
    }
}
