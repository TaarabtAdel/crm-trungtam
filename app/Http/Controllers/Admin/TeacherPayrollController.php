<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollAdjustment;
use App\Models\Teacher;
use App\Services\Finance\ExpenseService;
use App\Services\Finance\PayrollAdjustmentService;
use App\Services\Finance\TeacherPayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TeacherPayrollController extends Controller
{
    public function __construct(
        protected TeacherPayrollService $payroll,
        protected ExpenseService $expenses,
        protected PayrollAdjustmentService $adjustments,
    ) {}

    public function index(Request $request)
    {
        [$month, $year] = $this->monthYear($request);
        $report = $this->payroll->report($month, $year);

        return view('admin.finance.teacher_payroll', compact('report', 'month', 'year'));
    }

    public function pdf(Request $request)
    {
        [$month, $year] = $this->monthYear($request);
        $report = $this->payroll->report($month, $year);
        $pdf = Pdf::loadView('pdf.teacher_payroll', compact('report'))
            ->setPaper('a4', 'portrait');

        return $pdf->download(sprintf('bang-luong-gv-%04d-%02d.pdf', $year, $month));
    }

    public function pdfPerson(Request $request, Teacher $teacher)
    {
        [$month, $year] = $this->monthYear($request);
        $detail = $this->payroll->detail($teacher, $month, $year);
        $slug = \Illuminate\Support\Str::slug($teacher->name) ?: ('gv-'.$teacher->id);
        $pdf = Pdf::loadView('pdf.teacher_payroll_person', compact('detail'))
            ->setPaper('a4', 'portrait');

        return $pdf->download(sprintf('bang-luong-gv-%s-%04d-%02d.pdf', $slug, $year, $month));
    }

    public function storeAdjustment(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'billing_month' => 'required|date_format:Y-m',
            'type' => 'required|in:bonus,penalty,advance',
            'amount' => 'required|numeric|min:1',
            'note' => 'required|string|max:1000',
            'mark_paid' => 'nullable|boolean',
        ]);

        $teacher = Teacher::findOrFail($data['teacher_id']);
        [$y, $m] = array_map('intval', explode('-', $data['billing_month']));

        $this->adjustments->createTeacherAdjustment(
            $teacher,
            $data['billing_month'],
            $data['type'],
            (float) $data['amount'],
            $data['note'],
            $request->user(),
            $request->boolean('mark_paid', true),
        );

        $label = PayrollAdjustment::typeOptions()[$data['type']] ?? $data['type'];

        return redirect()
            ->route('admin.finance.teacher-payroll', ['month' => $m, 'year' => $y])
            ->with('success', 'Đã thêm '.$label.' cho '.$teacher->name.'.');
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
        $result = $this->adjustments->bulkTeachers(
            $data['billing_month'],
            $data['type'],
            (float) $data['amount'],
            $data['note'],
            $request->user(),
        );

        $label = PayrollAdjustment::typeOptions()[$data['type']] ?? $data['type'];

        return redirect()
            ->route('admin.finance.teacher-payroll', ['month' => $m, 'year' => $y])
            ->with('success', "Đã áp {$label} cho {$result['count']} giáo viên.");
    }

    public function destroyAdjustment(Request $request, PayrollAdjustment $adjustment)
    {
        abort_unless($adjustment->scope === 'teacher', 404);
        [$y, $m] = array_map('intval', explode('-', $adjustment->billing_month));
        $this->adjustments->delete($adjustment);

        return redirect()
            ->route('admin.finance.teacher-payroll', ['month' => $m, 'year' => $y])
            ->with('success', 'Đã xóa khoản điều chỉnh.');
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
