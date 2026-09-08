<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\PaymentNotificationService;
use App\Support\TenantContext;
use App\Support\TenantDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPaymentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $paymentId,
        public ?string $tenantDatabase = null,
        public ?string $tenantSlug = null,
    ) {}

    public function handle(PaymentNotificationService $service): void
    {
        // Queue worker có thể mất tenant context — khôi phục DB nếu có
        if ($this->tenantDatabase) {
            TenantDatabase::connect($this->tenantDatabase);
            TenantContext::set($this->tenantSlug ?: 'local', $this->tenantDatabase);
        }

        $payment = Payment::query()->find($this->paymentId);
        if (! $payment) {
            Log::warning('SendPaymentNotificationJob: payment not found', ['id' => $this->paymentId]);

            return;
        }

        $service->sendForPayment($payment);
    }
}
