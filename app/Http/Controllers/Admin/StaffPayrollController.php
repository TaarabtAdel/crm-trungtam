<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollAdjustment;
use App\Models\User;
use App\Services\Finance\ExpenseService;
use App\Services\Finance\PayrollAdjustmentService;
use App\Services\Finance\StaffPayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class StaffPayrollController extends Controller
{
    public function __construct(
        protected StaffPayrollService $payroll,
        protected ExpenseService $expenses,
        protected PayrollAdjustmentService $adjustments,
    ) {}

    public function index(Request $request)
    {
        [$month, $year] = $this->monthYear($request);
        $report = $this->payroll->report($month, $year);

        return view('admin.finance.staff_payroll', compact('report', 'month', 'year'));
    }

    public function pdf(Request $request)
    {
        [$month, $year] = $this->monthYear($request);
        $report = $this->payroll->report($month, $year);
        $pdf = Pdf::loadView('pdf.staff_payroll', compact('report'))
            ->setPaper('a4', 'portrait');

        return $pdf->download(sprintf('bang-luong-nv-%04d-%02d.pdf', $year, $month));
    }

    public function pdfPerson(Request $request, User $user)
    {
        [$month, $year] = $this->monthYear($request);
        $detail = $this->payroll->detail($user, $month, $year);
        $slug = \Illuminate\Support\Str::slug($user->name) ?: ('nv-'.$user->id);
        $pdf = Pdf::loadView('pdf.staff_payroll_person', compact('detail'))
            ->setPaper('a4', 'portrait');

        return $pdf->download(sprintf('bang-luong-nv-%s-%04d-%02d.pdf', $slug, $year, $month));
    }

    public function storeAdjustment(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'billing_month' => 'required|date_format:Y-m',
            'type' => 'required|in:bonus,penalty,advance',
            'amount' => 'required|numeric|min:1',
            'note' => 'required|string|max:1000',
            'mark_paid' => 'nullable|boolean',
        ]);

        $user = User::findOrFail($data['user_id']);
        [$y, $m] = array_map('intval', explode('-', $data['billing_month']));

        $this->adjustments->createStaffAdjustment(
            $user,
            $data['billing_month'],
            $data['type'],
            (float) $data['amount'],
            $data['note'],
            $request->user(),
            $request->boolean('mark_paid', true),
        );

        $label = PayrollAdjustment::typeOptions()[$data['type']] ?? $data['type'];

        return redirect()
            ->route('admin.finance.staff-payroll', ['month' => $m, 'year' => $y])
            ->with('success', 'Đã thêm '.$label.' cho '.$user->name.'.');
    }

    public function bulkAdjustment(Request $request)
    {
        $data = $request->validate([
            'billing_month' => 'required|date_format:Y-m',
            'type' => 'required|in:bonus,penalty',
            'amount' => 'required|numeric|min:1',
            'note' => 'required|string|max:1000',
        ]);

        [$y, $m] = array_map('intval', explode('-', $data['billing_month']));
        $result = $this->adjustments->bulkStaff(
            $data['billing_month'],
            $data['type'],
            (float) $data['amount'],
            $data['note'],
            $request->user(),
        );

        $label = PayrollAdjustment::typeOptions()[$data['type']] ?? $data['type'];

        return redirect()
            ->route('admin.finance.staff-payroll', ['month' => $m, 'year' => $y])
            ->with('success', "Đã áp {$label} cho {$result['count']} nhân viên.");
    }

    public function destroyAdjustment(Request $request, PayrollAdjustment $adjustment)
    {
        abort_unless($adjustment->scope === 'staff', 404);
        [$y, $m] = array_map('intval', explode('-', $adjustment->billing_month));
        $this->adjustments->delete($adjustment);

        return redirect()
            ->route('admin.finance.staff-payroll', ['month' => $m, 'year' => $y])
            ->with('success', 'Đã xóa khoản điều chỉnh.');
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

    /** @return array{0:int,1:int} */
    protected function monthYear(Request $request): array
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        return [$month, $year];
    }
}
