<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\Teacher;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $teachers = Teacher::with('branch')
            ->withCount('classes')
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('specialty', 'like', "%{$q}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        $month = (int) $request->get('payroll_month', now()->month);
        $year = (int) $request->get('payroll_year', now()->year);
        $payroll = $this->buildPayroll($month, $year);

        return view('admin.training.teachers', compact('teachers', 'branches', 'q', 'status', 'payroll', 'month', 'year'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Teacher::create($data);

        return back()->with('success', 'Đã thêm giáo viên.');
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $this->validated($request, $teacher->id);
        $teacher->update($data);

        return back()->with('success', 'Đã cập nhật giáo viên.');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();

        return back()->with('success', 'Đã xóa giáo viên.');
    }

    public function exportPayroll(Request $request): StreamedResponse
    {
        $month = (int) $request->get('payroll_month', now()->month);
        $year = (int) $request->get('payroll_year', now()->year);
        $payroll = $this->buildPayroll($month, $year);

        return response()->streamDownload(function () use ($payroll, $month, $year) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Giáo viên', 'Chi nhánh', 'Đơn giá', 'Số buổi hoàn thành', 'Tổng giờ', 'Thực nhận', "Tháng {$month}/{$year}"]);
            foreach ($payroll as $row) {
                fputcsv($out, [
                    $row['name'], $row['branch'], $row['rate'], $row['sessions'], $row['hours'], $row['total'],
                ]);
            }
            fclose($out);
        }, "bang-luong-gv-{$month}-{$year}.csv");
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers,email'.($ignoreId ? ",{$ignoreId}" : ''),
            'phone' => 'nullable|string|max:30',
            'specialty' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'hourly_rate' => 'nullable|numeric|min:0',
            'joined_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);
        $data['hourly_rate'] = $data['hourly_rate'] ?? 0;
        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }

    protected function buildPayroll(int $month, int $year): array
    {
        $teachers = CurrentBranch::apply(Teacher::with('branch'))->orderBy('name')->get();
        $rows = [];

        foreach ($teachers as $teacher) {
            $sessions = ClassSession::where('teacher_id', $teacher->id)
                ->where('status', 'completed')
                ->whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->get();

            $hours = $sessions->sum(fn ($s) => $s->hours());
            $rows[] = [
                'name' => $teacher->name,
                'branch' => $teacher->branch?->name,
                'rate' => (float) $teacher->hourly_rate,
                'sessions' => $sessions->count(),
                'hours' => round($hours, 2),
                'total' => round($hours * (float) $teacher->hourly_rate),
            ];
        }

        return $rows;
    }
}
