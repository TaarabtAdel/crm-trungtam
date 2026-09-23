<?php

namespace App\Services\Finance;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
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

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'amount' => $amount,
            'reason' => $data['reason'] ?? null,
            'requested_by' => $user->id,
            'status' => 'pending',
        ]);

        $this->createApproveTasks($refund, $user);

        return $refund;
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
            $this->completeApproveTasks($refund, $approver->id);

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

        $this->completeApproveTasks($refund, $approver->id);

        return $refund->fresh();
    }

    protected function createApproveTasks(Refund $refund, User $requester): void
    {
        try {
            $refund->loadMissing(['payment.invoice.student']);
            $payment = $refund->payment;
            $invoice = $payment?->invoice;
            $student = $invoice?->student;
            $amount = number_format((float) $refund->amount, 0, ',', '.');

            $approvers = Notifier::recipientsForPermission(
                'finance.refunds.manage',
                [],
                $invoice?->branch_id ? (int) $invoice->branch_id : null
            )->values();

            if ($approvers->isEmpty()) {
                return;
            }

            app(AutoTaskService::class)->ensureForUsers(
                $approvers,
                AutoTaskService::SOURCE_REFUND_APPROVE,
                (int) $refund->id,
                [
                    'title' => 'Duyệt hoàn tiền: '.$amount.' đ'
                        .($student?->name ? ' · '.$student->name : ''),
                    'description' => 'Yêu cầu hoàn từ '.($requester->name ?? '—')
                        .($refund->reason ? "\nLý do: ".$refund->reason : '')
                        ."\nMở: ".route('admin.refunds.index', absolute: false),
                    'priority' => 'high',
                    'due_date' => now()->endOfDay(),
                    'branch_id' => $invoice?->branch_id ?? $requester->branch_id,
                    'creator_id' => $requester->id,
                    'status' => 'todo',
                    'watcher_ids' => [(int) $requester->id],
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function completeApproveTasks(Refund $refund, ?int $actorId = null): void
    {
        try {
            app(AutoTaskService::class)->completeBySource(
                AutoTaskService::SOURCE_REFUND_APPROVE,
                (int) $refund->id,
                $actorId
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
