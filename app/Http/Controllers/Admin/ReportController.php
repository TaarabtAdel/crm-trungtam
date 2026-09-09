<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\CourseClass;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\CurrentBranch;

class ReportController extends Controller
{
    public function index()
    {
        $branchId = CurrentBranch::id();
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $totalLeads = CurrentBranch::apply(Lead::query())->count();
        $wonLeads = CurrentBranch::apply(Lead::query())->where('status', 'won')->count();

        $attendanceRows = Attendance::query()
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->when($branchId, fn ($q) => $q->whereHas('courseClass', fn ($c) => $c->where('branch_id', $branchId)))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $attTotal = (int) $attendanceRows->sum();
        $attPresent = (int) ($attendanceRows['present'] ?? 0) + (int) ($attendanceRows['late'] ?? 0);
        $attendanceRate = $attTotal > 0 ? round($attPresent / $attTotal * 100, 1) : 0;

        $studyingNow = CurrentBranch::apply(Student::query())->where('status', 'studying')->count();
        $studyingMonthStart = CurrentBranch::apply(Student::query())
            ->where('status', 'studying')
            ->where('created_at', '<', $from)
            ->count();
        // Retention proxy: HV đang học tạo trước tháng này / HV đang học (đơn giản)
        $retention = $studyingNow > 0
            ? round(min($studyingMonthStart, $studyingNow) / $studyingNow * 100, 1)
            : 0;

        $debtBuckets = [
            'current' => 0.0,
            '1_30' => 0.0,
            '31_60' => 0.0,
            '61_90' => 0.0,
            '90_plus' => 0.0,
        ];
        $debtInvoices = Invoice::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('remaining_amount', '>', 0)
            ->get(['id', 'remaining_amount', 'due_date', 'branch_id']);

        foreach ($debtInvoices as $inv) {
            $amt = (float) $inv->remaining_amount;
            if (! $inv->due_date || $inv->due_date->gte(now()->startOfDay())) {
                $debtBuckets['current'] += $amt;
                continue;
            }
            $days = $inv->due_date->diffInDays(now()->startOfDay());
            if ($days <= 30) {
                $debtBuckets['1_30'] += $amt;
            } elseif ($days <= 60) {
                $debtBuckets['31_60'] += $amt;
            } elseif ($days <= 90) {
                $debtBuckets['61_90'] += $amt;
            } else {
                $debtBuckets['90_plus'] += $amt;
            }
        }

        $debtByBranch = Invoice::query()
            ->with('branch')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('remaining_amount', '>', 0)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('branch_id, sum(remaining_amount) as total')
            ->groupBy('branch_id')
            ->get();

        $report = [
            'leads' => $totalLeads,
            'won' => $wonLeads,
            'conversion' => $totalLeads > 0 ? round($wonLeads / $totalLeads * 100, 1) : 0,
            'expected_revenue' => CurrentBranch::apply(Lead::query())->sum('expected_revenue'),
            'paid_revenue' => CurrentBranch::applyThrough(Invoice::query()->where('status', 'paid'), 'student')->sum('amount'),
            'unpaid_revenue' => (float) $debtInvoices->sum('remaining_amount'),
            'students' => CurrentBranch::apply(Student::query())->count(),
            'studying' => $studyingNow,
            'classes' => CurrentBranch::apply(CourseClass::query())->count(),
            'teachers' => CurrentBranch::apply(Teacher::query())->count(),
            'attendance_rate' => $attendanceRate,
            'attendance_total' => $attTotal,
            'attendance_present' => $attPresent,
            'retention' => $retention,
            'debt_buckets' => $debtBuckets,
            'debt_by_branch' => $debtByBranch,
            'period_label' => $from->format('m/Y'),
        ];

        return view('admin.system.reports', compact('report'));
    }
}
