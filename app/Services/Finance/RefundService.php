<?php

namespace App\Services\Finance;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(
        protected InvoiceService $invoices
    ) {}

    public function request(Payment $payment, array $data, User $user): Refund
    {
        $amount = (float) ($data['amount'] ?? 0);
        $max = $payment->netAmount();
        if ($amount <= 0 || $amount > $max + 0.0001) {
            throw ValidationException::withMessages(['amount' => 'Số tiền hoàn không hợp lệ.']);
        }

        return Refund::create([
            'payment_id' => $payment->id,
            'amount' => $amount,
            'reason' => $data['reason'] ?? null,
            'requested_by' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function approve(Refund $refund, User $approver): Refund
    {
        if ($refund->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Hoàn tiền đã được xử lý.']);
        }

        return DB::transaction(function () use ($refund, $approver) {
            $payment = $refund->payment()->lockForUpdate()->first();
            $max = $payment->netAmount();
            if ((float) $refund->amount > $max + 0.0001) {
                throw ValidationException::withMessages(['amount' => 'Số tiền hoàn vượt mức còn lại.']);
            }

            $refund->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'processed_at' => now(),
            ]);

            $this->invoices->recalculate($payment->invoice);

            return $refund->fresh();
        });
    }

    public function reject(Refund $refund, User $approver): Refund
    {
        if ($refund->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Hoàn tiền đã được xử lý.']);
        }

        $refund->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'processed_at' => now(),
        ]);

        return $refund->fresh();
    }
}
