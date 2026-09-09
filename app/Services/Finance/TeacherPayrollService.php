<?php

namespace App\Services\Finance;

use App\Models\ClassSession;
use App\Models\Expense;
use App\Models\Teacher;
use App\Support\CurrentBranch;
use Carbon\Carbon;

class TeacherPayrollService
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

        $adjByTeacher = $this->adjustments->sumsForTeachers($billingMonth);

        $rows = [];
        $totals = [
            'accrued' => 0.0, 'bonus' => 0.0, 'penalty' => 0.0, 'advance' => 0.0,
            'net' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'sessions' => 0, 'hours' => 0.0,
        ];

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
            $adj = $adjByTeacher[$teacher->id] ?? ['bonus' => 0.0, 'penalty' => 0.0, 'advance' => 0.0];
            $calc = PayrollAdjustmentService::applyToAccrued($accrued, $paid, $adj);

            if ($sessions->isEmpty() && $paid <= 0 && $calc['bonus'] <= 0 && $calc['penalty'] <= 0 && $calc['advance'] <= 0) {
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
            $totals['sessions'] += $sessions->count();
            $totals['hours'] += $hours;
        }

        $totals['hours'] = round($totals['hours'], 2);
        $totals['net'] = round($totals['net'], 0);

        return [
            'month' => $month,
            'year' => $year,
            'billing_month' => $billingMonth,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'totals' => $totals,
            'adjustments' => $this->adjustments->listForMonth('teacher', $billingMonth),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Teacher $teacher, int $month, int $year): array
    {
        $billingMonth = sprintf('%04d-%02d', $year, $month);
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $sessions = ClassSession::query()
            ->with('courseClass')
            ->where('teacher_id', $teacher->id)
            ->where('status', 'completed')
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        $paid = (float) Expense::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->where('category', 'salary')
            ->where('billing_month', $billingMonth)
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->sum('amount');

        $hours = round($sessions->sum(fn (ClassSession $s) => $s->hours()), 2);
        $accrued = (float) $sessions->sum(fn (ClassSession $s) => $s->teacherPayAmount());
        $adj = $this->adjustments->sumsForTeacher($teacher->id, $billingMonth);
        $calc = PayrollAdjustmentService::applyToAccrued($accrued, $paid, $adj);

        return [
            'teacher' => $teacher->loadMissing('branch'),
            'month' => $month,
            'year' => $year,
            'billing_month' => $billingMonth,
            'from' => $from,
            'to' => $to,
            'sessions' => $sessions,
            'hours' => $hours,
            'accrued' => $accrued,
            'rate' => (float) $teacher->hourly_rate,
            'rate_mixed' => $sessions->contains(fn (ClassSession $s) => $s->courseClass?->hasCustomTeacherRate()),
            'bonus' => $calc['bonus'],
            'penalty' => $calc['penalty'],
            'advance' => $calc['advance'],
            'net' => $calc['net'],
            'paid' => $paid,
            'remaining' => $calc['remaining'],
            'adjustments' => $this->adjustments->listForTeacher($teacher->id, $billingMonth),
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
