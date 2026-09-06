<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\CurrentBranch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $branchId = CurrentBranch::id();
        $month = $request->get('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            $month = now()->format('Y-m');
        }
        $year = (int) $request->get('year', now()->year);
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();
        $prevMonthStart = (clone $monthStart)->subMonth()->startOfMonth();
        $prevMonthEnd = (clone $monthStart)->subMonth()->endOfMonth();

        $stats = [
            'teachers' => CurrentBranch::apply(Teacher::query())->count(),
            'students' => CurrentBranch::apply(Student::query())->count(),
            'classes' => CurrentBranch::apply(CourseClass::query())->count(),
            'sessions' => CurrentBranch::applyThrough(ClassSession::query(), 'courseClass')->count(),
            'revenue' => $this->paidRevenueQuery($branchId)->sum('amount'),
        ];

        $incomeThisMonth = $this->paidRevenueInRange($branchId, $monthStart, $monthEnd);
        $incomePrevMonth = $this->paidRevenueInRange($branchId, $prevMonthStart, $prevMonthEnd);
        $costThisMonth = $this->payrollCostInRange($branchId, $monthStart, $monthEnd);
        $costPrevMonth = $this->payrollCostInRange($branchId, $prevMonthStart, $prevMonthEnd);

        $daysInMonth = $monthEnd->day;
        $dailyRevenue = [];
        $dailyLabels = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dailyLabels[] = (string) $d;
            $day = (clone $monthStart)->day($d);
            $dailyRevenue[] = $this->paidRevenueInRange($branchId, $day->copy()->startOfDay(), $day->copy()->endOfDay());
        }

        $yearRevenue = $this->paidRevenueInYear($branchId, $year);
        $lastYearRevenue = $this->paidRevenueInYear($branchId, $year - 1);
        $yearCost = $this->payrollCostInYear($branchId, $year);

        $monthlyRevenue = [];
        $monthlyCost = [];
        $monthLabels = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfMonth();
            $end = (clone $start)->endOfMonth();
            $monthLabels[] = $start->translatedFormat('M');
            $monthlyRevenue[] = $this->paidRevenueInRange($branchId, $start, $end);
            $monthlyCost[] = $this->payrollCostInRange($branchId, $start, $end);
        }

        $studentsNow = CurrentBranch::apply(Student::query())->count();
        $studentsPrev = CurrentBranch::apply(Student::query())
            ->where('created_at', '<', $monthStart)
            ->count();
        $classesNow = CurrentBranch::apply(CourseClass::query())->where('status', 'active')->count();
        $classesPrev = CurrentBranch::apply(CourseClass::query())
            ->where('created_at', '<', $monthStart)
            ->where('status', 'active')
            ->count();

        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = now()->endOfWeek(Carbon::SUNDAY);
        $prevWeekStart = (clone $weekStart)->subWeek();
        $prevWeekEnd = (clone $weekEnd)->subWeek();
        $weekIncome = $this->paidRevenueInRange($branchId, $weekStart, $weekEnd);
        $prevWeekIncome = $this->paidRevenueInRange($branchId, $prevWeekStart, $prevWeekEnd);
        $weeklyLabels = [];
        $weeklyData = [];
        for ($i = 0; $i < 7; $i++) {
            $day = (clone $weekStart)->addDays($i);
            $weeklyLabels[] = $day->translatedFormat('D');
            $weeklyData[] = $this->paidRevenueInRange($branchId, $day->copy()->startOfDay(), $day->copy()->endOfDay());
        }

        $teacherWorkload = $this->teacherWorkload($branchId, $monthStart, $monthEnd);
        $topClasses = $this->topClassesByRevenue($branchId, $year);

        $pct = fn (float $current, float $previous) => $previous > 0
            ? round(($current - $previous) / $previous * 100, 1)
            : ($current > 0 ? 100.0 : 0.0);

        $compare = [
            'income_month_pct' => $pct($incomeThisMonth, $incomePrevMonth),
            'cost_month_pct' => $pct($costThisMonth, $costPrevMonth),
            'year_pct' => $pct($yearRevenue, $lastYearRevenue),
            'students_pct' => $pct((float) $studentsNow, (float) $studentsPrev),
            'classes_pct' => $pct((float) $classesNow, (float) $classesPrev),
            'week_pct' => $pct($weekIncome, $prevWeekIncome),
        ];

        $monthOptions = collect(range(0, 11))->map(function ($i) {
            $d = now()->subMonths($i);

            return ['value' => $d->format('Y-m'), 'label' => 'Tháng '.$d->format('n').' năm '.$d->format('Y')];
        });

        return view('admin.dashboard', compact(
            'stats', 'month', 'year', 'monthOptions',
            'incomeThisMonth', 'costThisMonth',
            'dailyLabels', 'dailyRevenue',
            'yearRevenue', 'yearCost', 'lastYearRevenue',
            'monthLabels', 'monthlyRevenue', 'monthlyCost',
            'studentsNow', 'classesNow',
            'weekIncome', 'weeklyLabels', 'weeklyData',
            'teacherWorkload', 'topClasses', 'compare'
        ));
    }

    protected function paidRevenueQuery(?int $branchId)
    {
        $query = Invoice::query()->where('status', 'paid');
        if ($branchId) {
            $query->whereHas('student', fn ($s) => $s->where('branch_id', $branchId));
        }

        return $query;
    }

    protected function paidRevenueInRange(?int $branchId, Carbon $from, Carbon $to): float
    {
        return (float) $this->paidRevenueQuery($branchId)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('paid_at', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->whereNull('paid_at')
                            ->whereBetween('updated_at', [$from, $to]);
                    });
            })
            ->sum('amount');
    }

    protected function paidRevenueInYear(?int $branchId, int $year): float
    {
        return $this->paidRevenueInRange(
            $branchId,
            Carbon::create($year, 1, 1)->startOfDay(),
            Carbon::create($year, 12, 31)->endOfDay()
        );
    }

    protected function payrollCostInRange(?int $branchId, Carbon $from, Carbon $to): float
    {
        $sessions = ClassSession::query()
            ->with('teacher')
            ->where('status', 'completed')
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->when($branchId, fn ($q) => $q->whereHas('courseClass', fn ($c) => $c->where('branch_id', $branchId)))
            ->get();

        return (float) $sessions->sum(function (ClassSession $session) {
            $rate = (float) ($session->teacher?->hourly_rate ?? 0);

            return $session->hours() * $rate;
        });
    }

    protected function payrollCostInYear(?int $branchId, int $year): float
    {
        return $this->payrollCostInRange(
            $branchId,
            Carbon::create($year, 1, 1)->startOfDay(),
            Carbon::create($year, 12, 31)->endOfDay()
        );
    }

    protected function teacherWorkload(?int $branchId, Carbon $from, Carbon $to): array
    {
        $sessions = ClassSession::query()
            ->with('teacher')
            ->where('status', 'completed')
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->when($branchId, fn ($q) => $q->whereHas('courseClass', fn ($c) => $c->where('branch_id', $branchId)))
            ->whereNotNull('teacher_id')
            ->get()
            ->groupBy('teacher_id');

        return $sessions->map(function ($items) {
            /** @var \Illuminate\Support\Collection $items */
            $teacher = $items->first()->teacher;
            $hours = $items->sum(fn (ClassSession $s) => $s->hours());
            $rate = (float) ($teacher?->hourly_rate ?? 0);

            return [
                'name' => $teacher?->name ?? '—',
                'sessions' => $items->count(),
                'hours' => round($hours, 2),
                'amount' => round($hours * $rate),
            ];
        })
            ->sortByDesc('amount')
            ->take(8)
            ->values()
            ->all();
    }

    protected function topClassesByRevenue(?int $branchId, int $year): array
    {
        $query = Invoice::query()
            ->select('class_id', DB::raw('SUM(amount) as total'))
            ->where('status', 'paid')
            ->whereNotNull('class_id')
            ->where(function ($q) use ($year) {
                $q->whereYear('paid_at', $year)
                    ->orWhere(function ($q2) use ($year) {
                        $q2->whereNull('paid_at')->whereYear('updated_at', $year);
                    });
            })
            ->when($branchId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('branch_id', $branchId)))
            ->groupBy('class_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $classes = CourseClass::query()->whereIn('id', $query->pluck('class_id'))->get()->keyBy('id');

        return $query->map(function ($row) use ($classes) {
            return [
                'name' => $classes[$row->class_id]->name ?? ('Lớp #'.$row->class_id),
                'total' => (float) $row->total,
            ];
        })->all();
    }
}
