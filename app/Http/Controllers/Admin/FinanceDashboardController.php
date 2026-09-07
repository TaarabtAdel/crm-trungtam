<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\CurrentBranch;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceDashboardController extends Controller
{
    public function index()
    {
        $branchId = CurrentBranch::id();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $invoiceQuery = Invoice::query()->tap(fn ($q) => CurrentBranch::apply($q));
        $paymentQuery = Payment::query()->whereHas('invoice', function ($q) use ($branchId) {
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        });
        $expenseQuery = Expense::query()->tap(fn ($q) => CurrentBranch::apply($q));

        $kpi = [
            'revenue_month' => (clone $paymentQuery)->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount')
                - \App\Models\Refund::query()
                    ->where('status', 'approved')
                    ->whereBetween('processed_at', [$monthStart, $monthEnd])
                    ->whereHas('payment.invoice', fn ($q) => $branchId ? $q->where('branch_id', $branchId) : $q)
                    ->sum('amount'),
            'expense_month' => (clone $expenseQuery)->whereIn('status', ['approved', 'paid'])
                ->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount'),
            'debt_total' => (clone $invoiceQuery)->whereIn('status', ['unpaid', 'partial'])->sum('remaining_amount'),
        ];
        $kpi['profit_month'] = (float) $kpi['revenue_month'] - (float) $kpi['expense_month'];

        $debts = Invoice::with(['student', 'courseClass'])
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('remaining_amount', '>', 0)
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return view('admin.finance.dashboard', compact('kpi', 'debts'));
    }
}
