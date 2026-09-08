<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Finance\ExpenseService;
use App\Services\Finance\StaffPayrollService;
use Illuminate\Http\Request;

class StaffPayrollController extends Controller
{
    public function __construct(
        protected StaffPayrollService $payroll,
        protected ExpenseService $expenses,
    ) {}

    public function index(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        $report = $this->payroll->report($month, $year);

        return view('admin.finance.staff_payroll', compact('report', 'month', 'year'));
    }

    public function pay(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'billing_month' => 'required|date_format:Y-m',
            'amount' => 'nullable|numeric|min:1',
            'expense_date' => 'nullable|date',
            'mark_paid' => 'nullable|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($data['user_id']);
        [$y, $m] = array_map('intval', explode('-', $data['billing_month']));
        $report = $this->payroll->report($m, $y);
        $row = collect($report['rows'])->firstWhere('user_id', $user->id);

        if (! $row || $row['remaining'] <= 0) {
            return back()->with('error', 'Không còn số tiền lương cần chi cho nhân viên này trong tháng đã chọn.');
        }

        $amount = isset($data['amount']) ? (float) $data['amount'] : (float) $row['remaining'];
        if ($amount > $row['remaining']) {
            return back()->with('error', 'Số tiền chi vượt quá phần còn lại ('.number_format($row['remaining'], 0, ',', '.').' đ).');
        }

        $markPaid = $request->user()->hasPermission('finance.expenses.pay_immediate')
            && $request->boolean('mark_paid');
        $note = $data['note'] ?? ('Lương NV '.$user->name.' tháng '.$m.'/'.$y);

        $expense = $this->expenses->create([
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'billing_month' => $data['billing_month'],
            'category' => 'staff_salary',
            'amount' => $amount,
            'expense_date' => $data['expense_date'] ?? now()->toDateString(),
            'note' => $note,
            'status' => $markPaid ? 'paid' : 'pending',
            'approved_by' => $markPaid ? $request->user()->id : null,
        ], $request->user());

        $msg = $markPaid
            ? 'Đã ghi nhận chi lương NV '.$expense->amount.' đ.'
            : 'Đã tạo phiếu chi lương NV (chờ duyệt).';

        return redirect()
            ->route('admin.finance.staff-payroll', ['month' => $m, 'year' => $y])
            ->with('success', $msg);
    }
}
