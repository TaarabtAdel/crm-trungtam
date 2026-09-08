<?php

namespace App\Services\Finance;

use App\Jobs\SendPaymentNotificationJob;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        protected InvoiceService $invoices
    ) {}

    public function record(Invoice $invoice, array $data, ?UploadedFile $receipt = null): Payment
    {
        if (in_array($invoice->status, ['cancelled'], true)) {
            throw ValidationException::withMessages(['invoice' => 'Hóa đơn đã hủy.']);
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Số tiền phải lớn hơn 0.']);
        }

        $invoice = $this->invoices->recalculate($invoice);
        if ($amount > (float) $invoice->remaining_amount + 0.0001) {
            throw ValidationException::withMessages(['amount' => 'Số tiền vượt quá công nợ còn lại.']);
        }

        $payment = DB::transaction(function () use ($invoice, $data, $amount, $receipt) {
            $path = null;
            if ($receipt) {
                $path = $receipt->store('finance/receipts', 'public');
            }

            $installmentId = $data['installment_id'] ?? null;
            if ($installmentId) {
                $ok = PaymentInstallment::where('invoice_id', $invoice->id)->where('id', $installmentId)->exists();
                if (! $ok) {
                    $installmentId = null;
                }
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'installment_id' => $installmentId,
                'amount' => $amount,
                'method' => $data['method'] ?? 'cash',
                'paid_at' => $data['paid_at'] ?? now(),
                'received_by' => $data['received_by'] ?? auth()->id(),
                'note' => $data['note'] ?? null,
                'receipt_path' => $path,
            ]);

            $this->invoices->recalculate($invoice);

            return $payment;
        });

        SendPaymentNotificationJob::dispatch(
            $payment->id,
            TenantContext::databaseName(),
            TenantContext::subdomain()
        );

        return $payment;
    }

    public function delete(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            if ($payment->receipt_path) {
                Storage::disk('public')->delete($payment->receipt_path);
            }
            $payment->refunds()->delete();
            $payment->delete();
            $this->invoices->recalculate($invoice);
        });
    }
}
