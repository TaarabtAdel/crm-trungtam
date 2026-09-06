<?php

namespace App\Services\Finance;

use App\Models\CourseClass;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected CommissionService $commissions
    ) {}

    public function nextCode(?Carbon $at = null): string
    {
        $at = $at ?: now();
        $prefix = 'HD-'.$at->format('Ym').'-';
        $last = Invoice::query()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, int $installmentCount = 1): Invoice
    {
        return DB::transaction(function () use ($data, $installmentCount) {
            $student = Student::findOrFail($data['student_id']);
            $amount = (float) ($data['amount'] ?? 0);
            $status = $data['status'] ?? 'unpaid';
            $installmentCount = max(1, (int) $installmentCount);

            if (empty($data['branch_id'])) {
                $data['branch_id'] = $student->branch_id;
            }

            $invoice = Invoice::create([
                'code' => $data['code'] ?? $this->nextCode(),
                'student_id' => $student->id,
                'class_id' => $data['class_id'] ?? null,
                'branch_id' => $data['branch_id'],
                'sales_id' => $data['sales_id'] ?? null,
                'amount' => $amount,
                'paid_amount' => 0,
                'remaining_amount' => $amount,
                'billing_month' => $data['billing_month'] ?? null,
                'sessions_count' => $data['sessions_count'] ?? null,
                'fee_type' => $data['fee_type'] ?? null,
                'installment_count' => $installmentCount,
                'status' => $status === 'paid' ? 'unpaid' : $status,
                'due_date' => $data['due_date'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            $this->createInstallments($invoice, $installmentCount, $data['due_date'] ?? null);

            if (($data['status'] ?? null) === 'paid' && $amount > 0) {
                app(PaymentService::class)->record($invoice, [
                    'amount' => $amount,
                    'method' => $data['payment_method'] ?? 'cash',
                    'paid_at' => now(),
                    'received_by' => $data['received_by'] ?? auth()->id(),
                    'note' => 'Thu đủ khi tạo hóa đơn',
                ]);
            } else {
                $this->recalculate($invoice);
            }

            return $invoice->fresh(['installments', 'payments']);
        });
    }

    public function createEnrollmentInvoice(CourseClass $class, Student $student, array $extra = []): ?Invoice
    {
        $exists = Invoice::query()
            ->where('class_id', $class->id)
            ->where('student_id', $student->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereNull('billing_month')
                    ->orWhere('billing_month', now()->format('Y-m'));
            })
            ->exists();

        if ($exists) {
            return null;
        }

        $suggestion = $class->suggestInvoiceAmount(now()->format('Y-m'));

        return $this->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'branch_id' => $class->branch_id ?: $student->branch_id,
            'amount' => $suggestion['amount'] ?: (float) $class->tuition_fee,
            'billing_month' => now()->format('Y-m'),
            'fee_type' => $suggestion['fee_type'] ?? $class->tuition_type,
            'sessions_count' => $suggestion['sessions_count'],
            'due_date' => now()->endOfMonth()->toDateString(),
            'note' => $extra['note'] ?? 'Tự tạo khi đăng ký lớp',
            'sales_id' => $extra['sales_id'] ?? null,
            'status' => 'unpaid',
        ], (int) ($extra['installment_count'] ?? 1));
    }

    public function createInstallments(Invoice $invoice, int $count, ?string $firstDue = null): void
    {
        $invoice->installments()->delete();
        $count = max(1, $count);
        $total = (float) $invoice->amount;
        $base = (int) floor($total / $count);
        $remainder = (int) ($total - ($base * $count));
        $start = $firstDue ? Carbon::parse($firstDue) : ($invoice->due_date ? Carbon::parse($invoice->due_date) : now()->endOfMonth());

        for ($i = 1; $i <= $count; $i++) {
            $amount = $base + ($i === $count ? $remainder : 0);
            PaymentInstallment::create([
                'invoice_id' => $invoice->id,
                'sequence' => $i,
                'amount' => $amount,
                'paid_amount' => 0,
                'due_date' => (clone $start)->addMonths($i - 1)->toDateString(),
                'status' => 'unpaid',
            ]);
        }

        $invoice->update(['installment_count' => $count]);
    }

    public function recalculate(Invoice $invoice): Invoice
    {
        $invoice->load(['payments.refunds', 'installments']);

        $paid = 0.0;
        foreach ($invoice->payments as $payment) {
            $refunded = (float) $payment->refunds->where('status', 'approved')->sum('amount');
            $paid += max(0, (float) $payment->amount - $refunded);
        }

        $amount = (float) $invoice->amount;
        $remaining = max(0, $amount - $paid);

        $status = $invoice->status;
        if ($status !== 'cancelled') {
            if ($paid <= 0) {
                $status = 'unpaid';
            } elseif ($remaining <= 0) {
                $status = 'paid';
            } else {
                $status = 'partial';
            }
        }

        $wasPaid = $invoice->status === 'paid';
        $invoice->update([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $status,
            'paid_at' => $status === 'paid' ? ($invoice->paid_at ?: now()) : null,
        ]);

        $this->syncInstallments($invoice->fresh(['installments', 'payments.refunds']));

        if (! $wasPaid && $status === 'paid') {
            $this->commissions->createForPaidInvoice($invoice->fresh());
        }

        return $invoice->fresh();
    }

    protected function syncInstallments(Invoice $invoice): void
    {
        $remainingPaid = (float) $invoice->paid_amount;

        foreach ($invoice->installments->sortBy('sequence') as $inst) {
            $apply = min((float) $inst->amount, max(0, $remainingPaid));
            $remainingPaid -= $apply;
            $status = 'unpaid';
            if ($apply <= 0) {
                $status = ($inst->due_date && $inst->due_date->isPast()) ? 'overdue' : 'unpaid';
            } elseif ($apply >= (float) $inst->amount) {
                $status = 'paid';
            } else {
                $status = 'partial';
            }

            $inst->update([
                'paid_amount' => $apply,
                'status' => $status,
            ]);
        }
    }
}
