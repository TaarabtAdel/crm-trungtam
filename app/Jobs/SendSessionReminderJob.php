<?php

namespace App\Jobs;

use App\Models\ClassSession;
use App\Services\SessionReminderNotificationService;
use App\Support\TenantContext;
use App\Support\TenantDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSessionReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $sessionId,
        public ?string $tenantDatabase = null,
        public ?string $tenantSlug = null,
    ) {}

    public function handle(SessionReminderNotificationService $service): void
    {
        if ($this->tenantDatabase) {
            TenantDatabase::connect($this->tenantDatabase);
            TenantContext::set($this->tenantSlug ?: 'local', $this->tenantDatabase);
        }

        $session = ClassSession::query()->find($this->sessionId);
        if (! $session) {
            Log::warning('SendSessionReminderJob: session not found', ['id' => $this->sessionId]);

            return;
        }

        if ($session->status !== 'scheduled') {
            return;
        }

        $service->sendForSession($session);
    }
}
