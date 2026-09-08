<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\Finance\StaffPayrollService;
use App\Services\Finance\TeacherPayrollService;
use App\Support\CurrentBranch;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month');
        [$from, $to, $label] = $this->range($period, $request);

        $data = $this->buildReport($from, $to);

        return view('admin.finance.reports', compact('data', 'period', 'from', 'to', 'label'));
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $period = $request->get('period', 'month');
        [$from, $to] = $this->range($period, $request);
        $data = $this->buildReport($from, $to);

        $sheet = new Spreadsheet;
        $ws = $sheet->getActiveSheet();
        $ws->fromArray([
            ['Chỉ tiêu', 'Giá trị'],
            ['Doanh thu (đã thu)', $data['revenue']],
            ['Hoàn tiền', $data['refunds']],
            ['Thu ròng', $data['net_revenue']],
            ['Chi phí', $data['expenses']],
            ['Trong đó lương GV đã chi', $data['salary_paid']],
            ['Lương GV tạm tính (buổi HT)', $data['payroll_accrued']],
            ['Trong đó lương NV đã chi', $data['staff_salary_paid']],
            ['Lương NV tạm tính (công)', $data['staff_payroll_accrued']],
            ['Lãi/Lỗ', $data['profit']],
            [],
            ['Ngày', 'Thu', 'Chi'],
        ], null, 'A1');

        $row = 9;
        foreach ($data['daily'] as $day) {
            $ws->fromArray([$day['date'], $day['in'], $day['out']], null, "A{$row}");
            $row++;
        }

        return response()->streamDownload(function () use ($sheet) {
            (new Xlsx($sheet))->save('php://output');
        }, 'bao-cao-tai-chinh-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $period = $request->get('period', 'month');
        [$from, $to, $label] = $this->range($period, $request);
        $data = $this->buildReport($from, $to);
        $pdf = Pdf::loadView('pdf.finance_report', compact('data', 'from', 'to', 'label'));

        return $pdf->download('bao-cao-tai-chinh.pdf');
    }

    protected function range(string $period, Request $request): array
    {
        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to = Carbon::parse($request->to)->endOfDay();

            return [$from, $to, $from->format('d/m/Y').' - '.$to->format('d/m/Y')];
        }

        return match ($period) {
            'day' => [now()->startOfDay(), now()->endOfDay(), 'Hôm nay'],
            'quarter' => [now()->firstOfQuarter()->startOfDay(), now()->endOfQuarter()->endOfDay(), 'Quý này'],
            'year' => [now()->startOfYear(), now()->endOfYear(), 'Năm '.now()->year],
            default => [now()->startOfMonth(), now()->endOfMonth(), 'Tháng '.now()->format('m/Y')],
        };
    }

    protected function buildReport(Carbon $from, Carbon $to): array
    {
        $branchId = CurrentBranch::id();

        $revenue = Payment::query()
            ->whereBetween('paid_at', [$from, $to])
            ->whereHas('invoice', fn ($q) => $branchId ? $q->where('branch_id', $branchId) : $q)
            ->sum('amount');

        $refunds = Refund::query()
            ->where('status', 'approved')
            ->whereBetween('processed_at', [$from, $to])
            ->whereHas('payment.invoice', fn ($q) => $branchId ? $q->where('branch_id', $branchId) : $q)
            ->sum('amount');

        $expenses = Expense::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->whereIn('status', ['approved', 'paid'])
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $salaryPaid = Expense::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->where('category', 'salary')
            ->whereIn('status', ['approved', 'paid'])
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $payrollAccrued = app(TeacherPayrollService::class)->accruedInRange($branchId, $from, $to);

        $staffSalaryPaid = Expense::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->where('category', 'staff_salary')
            ->whereIn('status', ['approved', 'paid'])
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $staffPayrollAccrued = app(StaffPayrollService::class)->accruedInRange($branchId, $from, $to);
        $byClass = Invoice::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('class_id, sum(amount) as total')
            ->groupBy('class_id')
            ->with('courseClass')
            ->get();

        $byBranch = Invoice::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('branch_id, sum(amount) as total')
            ->groupBy('branch_id')
            ->with('branch')
            ->get();

        $bySales = Invoice::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->where('status', 'paid')
            ->whereNotNull('sales_id')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('sales_id, sum(amount) as total')
            ->groupBy('sales_id')
            ->with('sales')
            ->get();

        $daily = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor <= $to) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();
            $in = Payment::query()
                ->whereBetween('paid_at', [$dayStart, $dayEnd])
                ->whereHas('invoice', fn ($q) => $branchId ? $q->where('branch_id', $branchId) : $q)
                ->sum('amount');
            $out = Expense::query()
                ->tap(fn ($q) => CurrentBranch::apply($q))
                ->whereIn('status', ['approved', 'paid'])
                ->whereDate('expense_date', $cursor->toDateString())
                ->sum('amount');
            $daily[] = [
                'date' => $cursor->format('d/m'),
                'in' => (float) $in,
                'out' => (float) $out,
            ];
            $cursor->addDay();
            if (count($daily) > 93) {
                break;
            }
        }

        $net = (float) $revenue - (float) $refunds;

        return [
            'revenue' => (float) $revenue,
            'refunds' => (float) $refunds,
            'net_revenue' => $net,
            'expenses' => (float) $expenses,
            'salary_paid' => (float) $salaryPaid,
            'payroll_accrued' => (float) $payrollAccrued,
            'staff_salary_paid' => (float) $staffSalaryPaid,
            'staff_payroll_accrued' => (float) $staffPayrollAccrued,
            'profit' => $net - (float) $expenses,
            'by_class' => $byClass,
            'by_branch' => $byBranch,
            'by_sales' => $bySales,
            'daily' => $daily,
        ];
    }
}
