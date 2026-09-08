<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Support\CurrentBranch;
use Carbon\Carbon;

class StaffPayrollService
{
    /**
     * @return array{month:int, year:int, billing_month:string, rows:array<int, array>, totals:array{accrued:float, paid:float, remaining:float, days:float}}
     */
    public function report(int $month, int $year): array
    {
        $billingMonth = sprintf('%04d-%02d', $year, $month);
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $users = CurrentBranch::apply(User::with('branch'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $paidByUser = Expense::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->where('category', 'staff_salary')
            ->where('billing_month', $billingMonth)
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->whereNotNull('user_id')
            ->selectRaw('user_id, sum(amount) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $rows = [];
        $totals = ['accrued' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'days' => 0.0];

        foreach ($users as $user) {
            $attendances = StaffAttendance::query()
                ->where('user_id', $user->id)
                ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
                ->get();

            $dayUnits = round($attendances->sum(fn (StaffAttendance $a) => $a->dayUnits()), 2);
            $rate = (float) $user->daily_rate;
            $accrued = (float) $attendances->sum(fn (StaffAttendance $a) => $a->payAmount($rate));
            $paid = (float) ($paidByUser[$user->id] ?? 0);
            $remaining = max(0, $accrued - $paid);

            if ($attendances->isEmpty() && $paid <= 0) {
                continue;
            }

            $rows[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'branch' => $user->branch?->name,
                'branch_id' => $user->branch_id,
                'rate' => $rate,
                'days' => $dayUnits,
                'present_count' => $attendances->where('status', 'present')->count(),
                'half_count' => $attendances->where('status', 'half')->count(),
                'accrued' => $accrued,
                'paid' => $paid,
                'remaining' => $remaining,
            ];

            $totals['accrued'] += $accrued;
            $totals['paid'] += $paid;
            $totals['remaining'] += $remaining;
            $totals['days'] += $dayUnits;
        }

        $totals['days'] = round($totals['days'], 2);

        return [
            'month' => $month,
            'year' => $year,
            'billing_month' => $billingMonth,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    public function accruedInRange(?int $branchId, Carbon $from, Carbon $to): float
    {
        $attendances = StaffAttendance::query()
            ->with('user')
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->when($branchId, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('branch_id', $branchId)))
            ->get();

        return (float) round($attendances->sum(fn (StaffAttendance $a) => $a->payAmount()));
    }
}
