<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\InvoiceService;
use App\Services\Finance\PaymentService;
use App\Support\CurrentBranch;
use App\Support\VietQr;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $classId = $request->get('class_id');
        $user = $request->user();

        $invoices = Invoice::with(['student', 'courseClass', 'sales', 'branch'])
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->when($user->isSales(), fn ($query) => $query->where('sales_id', $user->id))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('code', 'like', "%{$q}%")
                        ->orWhereHas('student', fn ($s) => $s->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $classes = collect();
        $salesUsers = User::whereIn('role', ['sales', 'admin', 'super_admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedStudent = null;
        $oldStudentId = old('student_id');
        if ($oldStudentId) {
            $selectedStudent = CurrentBranch::apply(Student::query())->find($oldStudentId);
        }

        $selectedClass = null;
        $oldClassId = old('class_id', $classId);
        if ($oldClassId) {
            $selectedClass = CurrentBranch::apply(CourseClass::query())->find($oldClassId);
        }

        return view('admin.finance.invoices', compact(
            'invoices', 'classes', 'salesUsers', 'q', 'status', 'classId', 'selectedStudent', 'selectedClass'
        ));
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);
        $invoice->load([
            'student', 'courseClass', 'branch', 'sales',
            'installments', 'payments.receiver', 'payments.refunds', 'commissions.sales',
        ]);

        return view('admin.finance.invoice_show', compact('invoice'));
    }

    public function suggest(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'billing_month' => 'nullable|string|max:7',
            'fee_type' => 'nullable|in:monthly,per_session,course',
        ]);

        $class = CourseClass::findOrFail($data['class_id']);
        $billingMonth = $data['billing_month'] ?? now()->format('Y-m');
        $feeType = $data['fee_type'] ?? ($class->isPerSessionFee() ? 'per_session' : 'monthly');
        $suggestion = $class->suggestInvoiceAmount($billingMonth, $feeType);

        $sessions = $class->sessionsInMonth($billingMonth)->map(function ($s) {
            $time = trim(
                ($s->start_time ? substr((string) $s->start_time, 0, 5) : '')
                .(($s->start_time || $s->end_time) ? '–' : '')
                .($s->end_time ? substr((string) $s->end_time, 0, 5) : ''),
                '–'
            );

            return [
                'id' => $s->id,
                'date' => optional($s->session_date)->format('d/m/Y'),
                'time' => $time,
                'status' => $s->statusLabel(),
            ];
        })->values();

        return response()->json([
            'class_name' => $class->name,
            'tuition_type' => $class->tuition_type,
            'tuition_type_label' => $class->tuitionTypeLabel(),
            'tuition_display' => $class->tuitionDisplay(),
            'unit_fee' => (float) $class->tuition_fee,
            'default_fee_type' => $class->isPerSessionFee() ? 'per_session' : 'monthly',
            'previews' => [
                'monthly' => $class->suggestInvoiceAmount($billingMonth, 'monthly'),
                'per_session' => $class->suggestInvoiceAmount($billingMonth, 'per_session'),
                'course' => $class->suggestInvoiceAmount($billingMonth, 'course'),
            ],
            'sessions' => $sessions,
            ...$suggestion,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'nullable|exists:classes,id',
            'sales_id' => 'nullable|exists:users,id',
            'fee_type' => 'nullable|in:monthly,per_session,course',
            'billing_month' => 'nullable|string|max:7',
            'session_ids' => 'nullable|array',
            'session_ids.*' => 'integer|exists:class_sessions,id',
            'sessions_count' => 'nullable|integer|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'installment_count' => 'nullable|integer|min:1|max:24',
            'status' => 'nullable|in:unpaid,paid,cancelled',
            'due_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $installments = (int) ($data['installment_count'] ?? 1);
        $student = Student::findOrFail($data['student_id']);

        if (! empty($data['class_id'])) {
            $class = CourseClass::findOrFail($data['class_id']);
            if (($data['fee_type'] ?? '') === 'per_session' && empty($data['session_ids'])) {
                return back()->with('error', 'Thu theo buổi: vui lòng chọn ít nhất một buổi trên lịch.')->withInput();
            }

            $payload = $this->invoiceService->buildClassBillingPayload($class, $data);
            $this->invoiceService->create(array_merge($payload, [
                'student_id' => $student->id,
                'class_id' => $class->id,
                'branch_id' => $class->branch_id ?: $student->branch_id,
                'sales_id' => $data['sales_id'] ?? null,
                'due_date' => $data['due_date'] ?? now()->endOfMonth()->toDateString(),
                'status' => $data['status'] ?? 'unpaid',
                'received_by' => auth()->id(),
            ]), $installments);
        } else {
            if (! isset($data['amount']) || $data['amount'] === '' || $data['amount'] === null) {
                return back()->with('error', 'Vui lòng nhập số tiền hoặc chọn lớp.')->withInput();
            }
            $gross = (float) $data['amount'];
            $discount = max(0, (float) ($data['discount_amount'] ?? 0));
            if ($discount > $gross) {
                $discount = $gross;
            }
            $this->invoiceService->create([
                'student_id' => $student->id,
                'class_id' => null,
                'branch_id' => $student->branch_id,
                'sales_id' => $data['sales_id'] ?? null,
                'billing_month' => $data['billing_month'] ?? null,
                'sessions_count' => $data['sessions_count'] ?? null,
                'fee_type' => $data['fee_type'] ?? null,
                'gross_amount' => $gross,
                'discount_amount' => $discount,
                'discount_reason' => $discount > 0 ? ($data['discount_reason'] ?: 'Giảm học phí') : null,
                'amount' => max(0, $gross - $discount),
                'due_date' => $data['due_date'] ?? null,
                'status' => $data['status'] ?? 'unpaid',
                'note' => $data['note'] ?? null,
                'received_by' => auth()->id(),
            ], $installments);
        }

        return back()->with('success', 'Đã tạo hóa đơn.');
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);
        $data = $this->validated($request, true);
        unset($data['installment_count'], $data['status']);

        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'Hóa đơn đã hủy.');
        }

        $invoice->update([
            'student_id' => $data['student_id'],
            'class_id' => $data['class_id'] ?? null,
            'sales_id' => $data['sales_id'] ?? null,
            'billing_month' => $data['billing_month'] ?? null,
            'sessions_count' => $data['sessions_count'] ?? null,
            'fee_type' => $data['fee_type'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'note' => $data['note'] ?? null,
            'amount' => $data['amount'],
        ]);

        if ($invoice->wasChanged('amount') || $request->filled('rebuild_installments')) {
            $count = max(1, (int) $request->get('installment_count', $invoice->installment_count ?: 1));
            $this->invoiceService->createInstallments($invoice, $count, $data['due_date'] ?? null);
        }

        $this->invoiceService->recalculate($invoice->fresh());

        return back()->with('success', 'Đã cập nhật hóa đơn.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);

        if (! $invoice->canBeDeleted()) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Không thể xóa hóa đơn đã thu (hoặc đã thu một phần).',
                ], 422);
            }

            return redirect()
                ->route('admin.invoices.index')
                ->with('error', 'Không thể xóa hóa đơn đã thu (hoặc đã thu một phần).');
        }

        $invoice->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Đã xóa hóa đơn.',
            ]);
        }

        return redirect()->route('admin.invoices.index')->with('success', 'Đã xóa hóa đơn.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:invoices,id',
        ]);

        $user = $request->user();
        $query = Invoice::query()
            ->whereIn('id', $data['ids'])
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->when($user->isSales(), fn ($q) => $q->where('sales_id', $user->id));

        $candidates = $query->get();
        $blocked = $candidates->filter(fn (Invoice $invoice) => ! $invoice->canBeDeleted())->count();
        $deletableIds = $candidates->filter(fn (Invoice $invoice) => $invoice->canBeDeleted())->pluck('id');

        $deleted = 0;
        if ($deletableIds->isNotEmpty()) {
            $deleted = Invoice::query()->whereIn('id', $deletableIds)->delete();
        }

        if ($deleted === 0 && $blocked > 0) {
            return redirect()
                ->route('admin.invoices.index')
                ->with('error', "Không thể xóa: {$blocked} hóa đơn đã thu (hoặc đã thu một phần).");
        }

        $message = "Đã xóa {$deleted} hóa đơn.";
        if ($blocked > 0) {
            $message .= " Bỏ qua {$blocked} hóa đơn đã thu.";
        }

        return redirect()
            ->route('admin.invoices.index')
            ->with('success', $message);
    }

    public function storePayment(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:cash,bank_transfer,card,e_wallet',
            'paid_at' => 'nullable|date',
            'installment_id' => 'nullable|exists:payment_installments,id',
            'note' => 'nullable|string',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $this->paymentService->record($invoice, $data, $request->file('receipt'));

        return back()->with('success', 'Đã ghi nhận thanh toán.');
    }

    public function storeInstallments(Request $request, Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);
        $data = $request->validate([
            'installment_count' => 'required|integer|min:1|max:24',
            'first_due' => 'nullable|date',
        ]);

        $this->invoiceService->createInstallments(
            $invoice,
            (int) $data['installment_count'],
            $data['first_due'] ?? null
        );
        $this->invoiceService->recalculate($invoice->fresh());

        return back()->with('success', 'Đã cập nhật kỳ trả góp.');
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);
        $invoice->load(['student', 'courseClass', 'branch', 'sales', 'payments', 'installments']);

        $billedSessions = collect();
        $sessionIds = $invoice->billed_session_ids ?? [];
        if (is_array($sessionIds) && $sessionIds !== []) {
            $billedSessions = \App\Models\ClassSession::query()
                ->whereIn('id', $sessionIds)
                ->orderBy('session_date')
                ->get();
        }

        $qrDataUri = null;
        $qrPayAmount = max(0, (float) $invoice->remaining_amount);
        $branch = $invoice->branch;
        if ($branch && $branch->hasPaymentAccount() && $qrPayAmount > 0) {
            $qrUrl = $branch->paymentQrUrl($qrPayAmount, $invoice->code);
            $qrDataUri = $qrUrl ? VietQr::dataUriFromUrl($qrUrl) : null;
        }

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'billedSessions', 'qrDataUri', 'qrPayAmount'))
            ->setPaper('a4', 'portrait');

        return $pdf->download(($invoice->code ?: 'invoice-'.$invoice->id).'.pdf');
    }

    protected function authorizeInvoice(Invoice $invoice): void
    {
        $user = request()->user();
        if ($user->isSales() && (int) $invoice->sales_id !== (int) $user->id) {
            abort(403);
        }
    }

    protected function validated(Request $request, bool $updating = false): array
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'nullable|exists:classes,id',
            'sales_id' => 'nullable|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'billing_month' => 'nullable|string|max:7',
            'sessions_count' => 'nullable|integer|min:0',
            'fee_type' => 'nullable|in:monthly,per_session,course',
            'installment_count' => 'nullable|integer|min:1|max:24',
            'status' => 'nullable|in:unpaid,partial,paid,cancelled',
            'due_date' => 'nullable|date',
            'note' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'gross_amount' => 'nullable|numeric|min:0',
        ]);

        if (! empty($data['class_id']) && empty($data['fee_type'])) {
            $class = CourseClass::find($data['class_id']);
            $data['fee_type'] = $class?->isPerSessionFee() ? 'per_session' : 'monthly';
        }

        $student = Student::find($data['student_id']);
        $data['branch_id'] = $student?->branch_id;

        return $data;
    }
}
