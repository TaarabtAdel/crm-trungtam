<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Support\CurrentBranch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $month = (string) $request->get('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        [$year, $monthNum] = array_map('intval', explode('-', $month));
        $from = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $userId = $request->integer('user_id') ?: null;
        $users = CurrentBranch::apply(User::query())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'daily_rate', 'branch_id']);

        $selectedUser = $userId ? $users->firstWhere('id', $userId) : null;

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
                'weekday' => $d->dayOfWeek, // 0=CN
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

        return view('admin.system.staff_attendances', compact(
            'users', 'selectedUser', 'userId', 'month', 'from', 'to', 'calendarDays', 'summary'
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

        return redirect()
            ->route('admin.staff-attendances.index', [
                'user_id' => $user->id,
                'month' => $data['month'] ?? Carbon::parse($dates->first())->format('Y-m'),
            ])
            ->with('success', 'Đã chấm công '.$dates->count().' ngày cho '.$user->name.'.');
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

        $deleted = StaffAttendance::query()
            ->where('user_id', $data['user_id'])
            ->whereIn('work_date', $dates)
            ->delete();

        return redirect()
            ->route('admin.staff-attendances.index', [
                'user_id' => $data['user_id'],
                'month' => $data['month'] ?? now()->format('Y-m'),
            ])
            ->with('success', "Đã xóa {$deleted} dòng chấm công.");
    }
}
