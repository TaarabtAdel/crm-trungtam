<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Teacher;
use App\Services\Finance\ExpenseService;
use App\Services\Finance\TeacherPayrollService;
use Illuminate\Http\Request;

class TeacherPayrollController extends Controller
{
    public function __construct(
        protected TeacherPayrollService $payroll,
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

        return view('admin.finance.teacher_payroll', compact('report', 'month', 'year'));
    }

    public function pay(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'billing_month' => 'required|date_format:Y-m',
            'amount' => 'nullable|numeric|min:1',
            'expense_date' => 'nullable|date',
            'mark_paid' => 'nullable|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        $teacher = Teacher::findOrFail($data['teacher_id']);
        [$y, $m] = array_map('intval', explode('-', $data['billing_month']));
        $report = $this->payroll->report($m, $y);
        $row = collect($report['rows'])->firstWhere('teacher_id', $teacher->id);

        if (! $row || $row['remaining'] <= 0) {
            return back()->with('error', 'Không còn số tiền lương cần chi cho giáo viên này trong tháng đã chọn.');
        }

        $amount = isset($data['amount']) ? (float) $data['amount'] : (float) $row['remaining'];
        if ($amount > $row['remaining']) {
            return back()->with('error', 'Số tiền chi vượt quá phần còn lại ('.number_format($row['remaining'], 0, ',', '.').' đ).');
        }

        $markPaid = $request->user()->hasPermission('finance.expenses.pay_immediate')
            && $request->boolean('mark_paid');
        $note = $data['note'] ?? ('Lương GV '.$teacher->name.' tháng '.$m.'/'.$y);

        $expense = $this->expenses->create([
            'branch_id' => $teacher->branch_id,
            'teacher_id' => $teacher->id,
            'billing_month' => $data['billing_month'],
            'category' => 'salary',
            'amount' => $amount,
            'expense_date' => $data['expense_date'] ?? now()->toDateString(),
            'note' => $note,
            'status' => $markPaid ? 'paid' : 'pending',
            'approved_by' => $markPaid ? $request->user()->id : null,
        ], $request->user());

        $msg = $markPaid
            ? 'Đã ghi nhận chi lương '.$expense->amount.' đ — dòng tiền Chi phí đã cập nhật.'
            : 'Đã tạo phiếu chi lương (chờ duyệt).';

        return redirect()
            ->route('admin.finance.teacher-payroll', ['month' => $m, 'year' => $y])
            ->with('success', $msg);
    }
}
