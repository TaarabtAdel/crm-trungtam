<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Support\CurrentBranch;
use Carbon\Carbon;

class StaffPayrollService
{
    public function __construct(
        protected PayrollAdjustmentService $adjustments,
    ) {}

    /**
     * @return array{month:int, year:int, billing_month:string, rows:array<int, array>, totals:array, adjustments:\Illuminate\Support\Collection}
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

        $adjByUser = $this->adjustments->sumsForUsers($billingMonth);

        $rows = [];
        $totals = [
            'accrued' => 0.0, 'bonus' => 0.0, 'penalty' => 0.0, 'advance' => 0.0,
            'net' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'days' => 0.0,
        ];

        foreach ($users as $user) {
            $attendances = StaffAttendance::query()
                ->where('user_id', $user->id)
                ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
                ->get();

            $dayUnits = round($attendances->sum(fn (StaffAttendance $a) => $a->dayUnits()), 2);
            $rate = (float) $user->daily_rate;
            $accrued = (float) $attendances->sum(fn (StaffAttendance $a) => $a->payAmount($rate));
            $paid = (float) ($paidByUser[$user->id] ?? 0);
            $adj = $adjByUser[$user->id] ?? ['bonus' => 0.0, 'penalty' => 0.0, 'advance' => 0.0];
            $calc = PayrollAdjustmentService::applyToAccrued($accrued, $paid, $adj);

            if ($attendances->isEmpty() && $paid <= 0 && $calc['bonus'] <= 0 && $calc['penalty'] <= 0 && $calc['advance'] <= 0) {
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
                'bonus' => $calc['bonus'],
                'penalty' => $calc['penalty'],
                'advance' => $calc['advance'],
                'net' => $calc['net'],
                'paid' => $paid,
                'remaining' => $calc['remaining'],
            ];

            $totals['accrued'] += $accrued;
            $totals['bonus'] += $calc['bonus'];
            $totals['penalty'] += $calc['penalty'];
            $totals['advance'] += $calc['advance'];
            $totals['net'] += $calc['net'];
            $totals['paid'] += $paid;
            $totals['remaining'] += $calc['remaining'];
            $totals['days'] += $dayUnits;
        }

        $totals['days'] = round($totals['days'], 2);
        $totals['net'] = round($totals['net'], 0);

        return [
            'month' => $month,
            'year' => $year,
            'billing_month' => $billingMonth,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'totals' => $totals,
            'adjustments' => $this->adjustments->listForMonth('staff', $billingMonth),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(User $user, int $month, int $year): array
    {
        $billingMonth = sprintf('%04d-%02d', $year, $month);
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $attendances = StaffAttendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('work_date')
            ->get();

        $paid = (float) Expense::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->where('category', 'staff_salary')
            ->where('billing_month', $billingMonth)
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->sum('amount');

        $rate = (float) $user->daily_rate;
        $days = round($attendances->sum(fn (StaffAttendance $a) => $a->dayUnits()), 2);
        $accrued = (float) $attendances->sum(fn (StaffAttendance $a) => $a->payAmount($rate));
        $adj = $this->adjustments->sumsForUser($user->id, $billingMonth);
        $calc = PayrollAdjustmentService::applyToAccrued($accrued, $paid, $adj);

        return [
            'user' => $user->loadMissing('branch'),
            'month' => $month,
            'year' => $year,
            'billing_month' => $billingMonth,
            'from' => $from,
            'to' => $to,
            'attendances' => $attendances,
            'days' => $days,
            'present_count' => $attendances->where('status', 'present')->count(),
            'half_count' => $attendances->where('status', 'half')->count(),
            'accrued' => $accrued,
            'rate' => $rate,
            'bonus' => $calc['bonus'],
            'penalty' => $calc['penalty'],
            'advance' => $calc['advance'],
            'net' => $calc['net'],
            'paid' => $paid,
            'remaining' => $calc['remaining'],
            'adjustments' => $this->adjustments->listForUser($user->id, $billingMonth),
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
