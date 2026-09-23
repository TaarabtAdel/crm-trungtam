<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\Tasks\AutoTaskService;
use App\Support\CurrentBranch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->get('mode') === 'day' ? 'day' : 'person';

        $month = (string) $request->get('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        [$year, $monthNum] = array_map('intval', explode('-', $month));
        $from = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $users = CurrentBranch::apply(User::query())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'daily_rate', 'branch_id']);

        if ($mode === 'day') {
            return $this->indexDay($request, $users, $month);
        }

        return $this->indexPerson($request, $users, $month, $from, $to);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $users
     */
    protected function indexPerson(Request $request, $users, string $month, Carbon $from, Carbon $to)
    {
        $userId = $request->integer('user_id') ?: null;
        $selectedUser = $userId ? $users->firstWhere('id', $userId) : null;
        if ($userId && ! $selectedUser) {
            abort(403, 'Bạn chỉ được thao tác dữ liệu thuộc chi nhánh của mình.');
        }

        $attendances = collect();
        if ($selectedUser) {
            $attendances = StaffAttendance::query()
                ->where('user_id', $selectedUser->id)
                ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
                ->get()
                ->keyBy(fn (StaffAttendance $a) => $a->work_date->format('Y-m-d'));
        }

        $calendarDays = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $att = $attendances->get($key);
            $calendarDays[] = [
                'date' => $key,
                'day' => $d->day,
                'weekday' => $d->dayOfWeek,
                'is_weekend' => $d->isWeekend(),
                'is_future' => $d->isFuture(),
                'attendance' => $att,
            ];
        }

        $summary = [
            'present' => $attendances->where('status', 'present')->count(),
            'half' => $attendances->where('status', 'half')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'units' => round($attendances->sum(fn ($a) => $a->dayUnits()), 2),
            'accrued' => $selectedUser
                ? (float) $attendances->sum(fn ($a) => $a->payAmount((float) $selectedUser->daily_rate))
                : 0,
        ];

        $mode = 'person';

        return view('admin.system.staff_attendances', compact(
            'mode', 'users', 'selectedUser', 'userId', 'month', 'from', 'to', 'calendarDays', 'summary'
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $users
     */
    protected function indexDay(Request $request, $users, string $month)
    {
        $dateStr = (string) $request->get('date', now()->toDateString());
        try {
            $workDate = Carbon::parse($dateStr)->startOfDay();
        } catch (\Throwable) {
            $workDate = now()->startOfDay();
        }

        $month = $workDate->format('Y-m');
        $byUser = StaffAttendance::query()
            ->whereDate('work_date', $workDate->toDateString())
            ->whereIn('user_id', $users->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $dayRows = $users->map(function (User $u) use ($byUser) {
            return [
                'user' => $u,
                'attendance' => $byUser->get($u->id),
            ];
        });

        $summary = [
            'present' => $byUser->where('status', 'present')->count(),
            'half' => $byUser->where('status', 'half')->count(),
            'leave' => $byUser->where('status', 'leave')->count(),
            'absent' => $byUser->where('status', 'absent')->count(),
            'marked' => $byUser->count(),
            'total' => $users->count(),
        ];

        $mode = 'day';
        $workDateStr = $workDate->toDateString();

        return view('admin.system.staff_attendances', compact(
            'mode', 'users', 'month', 'dayRows', 'summary', 'workDateStr'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'dates' => 'required|array|min:1',
            'dates.*' => 'date',
            'status' => ['required', Rule::in(array_keys(StaffAttendance::statusOptions()))],
            'note' => 'nullable|string|max:255',
            'month' => 'nullable|date_format:Y-m',
        ]);

        $user = User::findOrFail($data['user_id']);
        CurrentBranch::authorize($user->branch_id !== null ? (int) $user->branch_id : null);
        $dates = collect($data['dates'])->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->values();

        DB::transaction(function () use ($dates, $data, $user, $request) {
            foreach ($dates as $date) {
                StaffAttendance::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'work_date' => $date,
                    ],
                    [
                        'status' => $data['status'],
                        'note' => $data['note'] ?? null,
                        'created_by' => $request->user()->id,
                    ]
                );
            }
        });

        $this->completeAttendanceTasksIfReady($dates->all(), $request->user()?->id);

        return redirect()
            ->route('admin.staff-attendances.index', [
                'mode' => 'person',
                'user_id' => $user->id,
                'month' => $data['month'] ?? Carbon::parse($dates->first())->format('Y-m'),
            ])
            ->with('success', 'Đã chấm công '.$dates->count().' ngày cho '.$user->name.'.');
    }

    /**
     * Chấm 1 ngày cho nhiều nhân viên.
     */
    public function storeDay(Request $request)
    {
        $data = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'work_date' => 'required|date',
            'status' => ['required', Rule::in(array_keys(StaffAttendance::statusOptions()))],
            'note' => 'nullable|string|max:255',
        ]);

        $workDate = Carbon::parse($data['work_date'])->toDateString();
        $userIds = collect($data['user_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $allowedIds = CurrentBranch::apply(User::query())
            ->where('is_active', true)
            ->whereIn('id', $userIds)
            ->pluck('id');

        DB::transaction(function () use ($allowedIds, $workDate, $data, $request) {
            foreach ($allowedIds as $uid) {
                StaffAttendance::query()->updateOrCreate(
                    [
                        'user_id' => $uid,
                        'work_date' => $workDate,
                    ],
                    [
                        'status' => $data['status'],
                        'note' => $data['note'] ?? null,
                        'created_by' => $request->user()->id,
                    ]
                );
            }
        });

        $this->completeAttendanceTasksIfReady([$workDate], $request->user()?->id);

        return redirect()
            ->route('admin.staff-attendances.index', [
                'mode' => 'day',
                'date' => $workDate,
            ])
            ->with('success', 'Đã chấm công ngày '.Carbon::parse($workDate)->format('d/m/Y').' cho '.$allowedIds->count().' nhân viên.');
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'dates' => 'required|array|min:1',
            'dates.*' => 'date',
            'month' => 'nullable|date_format:Y-m',
        ]);

        $dates = collect($data['dates'])->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->values();

        $user = User::findOrFail($data['user_id']);
        CurrentBranch::authorize($user->branch_id !== null ? (int) $user->branch_id : null);

        $deleted = StaffAttendance::query()
            ->where('user_id', $data['user_id'])
            ->whereIn('work_date', $dates)
            ->delete();

        return redirect()
            ->route('admin.staff-attendances.index', [
                'mode' => 'person',
                'user_id' => $data['user_id'],
                'month' => $data['month'] ?? now()->format('Y-m'),
            ])
            ->with('success', "Đã xóa {$deleted} dòng chấm công.");
    }

    public function destroyDay(Request $request)
    {
        $data = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'work_date' => 'required|date',
        ]);

        $workDate = Carbon::parse($data['work_date'])->toDateString();
        $userIds = collect($data['user_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        $allowedIds = CurrentBranch::apply(User::query())
            ->whereIn('id', $userIds)
            ->pluck('id');

        $deleted = StaffAttendance::query()
            ->whereDate('work_date', $workDate)
            ->whereIn('user_id', $allowedIds)
            ->delete();

        return redirect()
            ->route('admin.staff-attendances.index', [
                'mode' => 'day',
                'date' => $workDate,
            ])
            ->with('success', "Đã xóa {$deleted} dòng chấm công.");
    }

    /**
     * Khi đã chấm đủ mọi NV active trong ngày → đóng việc auto trên board.
     *
     * @param  list<string>  $dates  Y-m-d
     */
    protected function completeAttendanceTasksIfReady(array $dates, ?int $actorId = null): void
    {
        $activeIds = CurrentBranch::apply(User::query())
            ->where('is_active', true)
            ->pluck('id');
        $activeCount = $activeIds->count();
        if ($activeCount === 0) {
            return;
        }

        $auto = app(AutoTaskService::class);

        foreach (array_unique($dates) as $date) {
            $marked = StaffAttendance::query()
                ->whereDate('work_date', $date)
                ->whereIn('user_id', $activeIds)
                ->pluck('user_id')
                ->unique()
                ->count();

            if ($marked < $activeCount) {
                continue;
            }

            try {
                $branchId = CurrentBranch::id();
                if (! $branchId) {
                    // HQ đang xem "Tất cả" — không complete việc theo CN (tránh đụng sai)
                    continue;
                }
                // Khớp CreateStaffAttendanceTasksCommand: Ymd + 4 chữ số branch_id
                $sourceId = (int) (Carbon::parse($date)->format('Ymd').sprintf('%04d', $branchId));
                $auto->completeBySource(AutoTaskService::SOURCE_STAFF_ATTENDANCE, $sourceId, $actorId);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
