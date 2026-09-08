<?php

namespace App\Services\Finance;

use App\Models\ClassSession;
use App\Models\Expense;
use App\Models\Teacher;
use App\Support\CurrentBranch;
use Carbon\Carbon;

class TeacherPayrollService
{
    /**
     * @return array{month:int, year:int, billing_month:string, rows:array<int, array>, totals:array{accrued:float, paid:float, remaining:float, sessions:int, hours:float}}
     */
    public function report(int $month, int $year): array
    {
        $billingMonth = sprintf('%04d-%02d', $year, $month);
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $teachers = CurrentBranch::apply(Teacher::with('branch'))
            ->orderBy('name')
            ->get();

        $paidByTeacher = Expense::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->where('category', 'salary')
            ->where('billing_month', $billingMonth)
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->whereNotNull('teacher_id')
            ->selectRaw('teacher_id, sum(amount) as total')
            ->groupBy('teacher_id')
            ->pluck('total', 'teacher_id');

        $rows = [];
        $totals = ['accrued' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'sessions' => 0, 'hours' => 0.0];

        foreach ($teachers as $teacher) {
            $sessions = ClassSession::query()
                ->with('courseClass')
                ->where('teacher_id', $teacher->id)
                ->where('status', 'completed')
                ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
                ->get();

            $hours = round($sessions->sum(fn (ClassSession $s) => $s->hours()), 2);
            $accrued = (float) $sessions->sum(fn (ClassSession $s) => $s->teacherPayAmount());
            $defaultRate = (float) $teacher->hourly_rate;
            $hasClassOverride = $sessions->contains(fn (ClassSession $s) => $s->courseClass?->hasCustomTeacherRate());
            $paid = (float) ($paidByTeacher[$teacher->id] ?? 0);
            $remaining = max(0, $accrued - $paid);

            if ($sessions->isEmpty() && $paid <= 0) {
                continue;
            }

            $rows[] = [
                'teacher_id' => $teacher->id,
                'name' => $teacher->name,
                'branch' => $teacher->branch?->name,
                'branch_id' => $teacher->branch_id,
                'rate' => $defaultRate,
                'rate_mixed' => $hasClassOverride,
                'sessions' => $sessions->count(),
                'hours' => $hours,
                'accrued' => $accrued,
                'paid' => $paid,
                'remaining' => $remaining,
            ];

            $totals['accrued'] += $accrued;
            $totals['paid'] += $paid;
            $totals['remaining'] += $remaining;
            $totals['sessions'] += $sessions->count();
            $totals['hours'] += $hours;
        }

        $totals['hours'] = round($totals['hours'], 2);

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
        $sessions = ClassSession::query()
            ->with(['teacher', 'courseClass'])
            ->where('status', 'completed')
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('courseClass', fn ($c) => $c->where('branch_id', $branchId));
            })
            ->get();

        return (float) round($sessions->sum(fn (ClassSession $s) => $s->teacherPayAmount()));
    }
}
