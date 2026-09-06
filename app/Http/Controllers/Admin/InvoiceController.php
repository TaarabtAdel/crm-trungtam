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
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $students = CurrentBranch::apply(Student::query())->orderBy('name')->get();
        $classes = CurrentBranch::apply(CourseClass::query())->orderBy('name')->get();
        $salesUsers = User::whereIn('role', ['sales', 'admin', 'super_admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.finance.invoices', compact(
            'invoices', 'students', 'classes', 'salesUsers', 'q', 'status'
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
        ]);

        $class = CourseClass::findOrFail($data['class_id']);
        $suggestion = $class->suggestInvoiceAmount($data['billing_month'] ?? null);

        return response()->json([
            'class_name' => $class->name,
            'tuition_type' => $class->tuition_type,
            'tuition_type_label' => $class->tuitionTypeLabel(),
            'tuition_display' => $class->tuitionDisplay(),
            ...$suggestion,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $installments = (int) ($data['installment_count'] ?? 1);
        unset($data['installment_count']);
        $this->invoiceService->create($data, $installments);

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
        $invoice->delete();

        return redirect()->route('admin.invoices.index')->with('success', 'Đã xóa hóa đơn.');
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
        $invoice->load(['student', 'courseClass', 'branch', 'payments', 'installments']);
        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'));

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
            'fee_type' => 'nullable|in:monthly,per_session',
            'installment_count' => 'nullable|integer|min:1|max:24',
            'status' => 'nullable|in:unpaid,partial,paid,cancelled',
            'due_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        if (! empty($data['class_id']) && empty($data['fee_type'])) {
            $class = CourseClass::find($data['class_id']);
            $data['fee_type'] = $class?->tuition_type;
        }

        $student = Student::find($data['student_id']);
        $data['branch_id'] = $student?->branch_id;

        return $data;
    }
}
