<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReminderScheduler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchedulerTickController extends Controller
{
    /**
     * Chạy các lệnh nhắc đến hạn (AJAX admin hoặc cron GET công khai).
     *
     * - POST /admin/scheduler/tick — cần đăng nhập
     * - GET  /scheduler/tick?key=... — không cần đăng nhập (cron mỗi phút)
     */
    public function __invoke(Request $request, ReminderScheduler $scheduler): JsonResponse
    {
        if (! $request->user()) {
            $expected = (string) config('app.scheduler_tick_token', '');
            $given = (string) $request->query('key', $request->query('token', ''));

            if ($expected === '' || ! hash_equals($expected, $given)) {
                abort(403, 'Invalid or missing scheduler key.');
            }
        }

        $result = $scheduler->tick($request->boolean('force'));

        return response()->json([
            'ok' => true,
            'ran' => $result['ran'],
            'skipped' => $result['skipped'],
            'locked' => $result['locked'],
            'server_time' => now()->toDateTimeString(),
        ]);
    }
}
