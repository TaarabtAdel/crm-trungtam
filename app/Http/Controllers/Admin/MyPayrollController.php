<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Finance\StaffPayrollService;
use App\Services\Finance\TeacherPayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MyPayrollController extends Controller
{
    public function __construct(
        protected TeacherPayrollService $teacherPayroll,
        protected StaffPayrollService $staffPayroll,
    ) {}

    public function show(Request $request)
    {
        [$month, $year] = $this->monthYear($request);
        $user = $request->user();
        $mode = $this->resolveMode($request, $user);
        $dual = $user->hasDualMyPayroll();

        if ($mode === 'teacher') {
            $teacher = $user->linkedTeacher();
            $detail = $teacher
                ? $this->teacherPayroll->detail($teacher, $month, $year)
                : null;

            return view('admin.finance.my_payroll', [
                'mode' => 'teacher',
                'dual' => $dual,
                'detail' => $detail,
                'teacher' => $teacher,
                'month' => $month,
                'year' => $year,
            ]);
        }

        $detail = $this->staffPayroll->detail($user, $month, $year);

        return view('admin.finance.my_payroll', [
            'mode' => 'staff',
            'dual' => $dual,
            'detail' => $detail,
            'teacher' => null,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function pdf(Request $request)
    {
        [$month, $year] = $this->monthYear($request);
        $user = $request->user();
        $mode = $this->resolveMode($request, $user);

        if ($mode === 'teacher') {
            $teacher = $user->linkedTeacher();
            abort_unless($teacher, 404, 'Chưa liên kết hồ sơ giáo viên (cùng email).');
            $detail = $this->teacherPayroll->detail($teacher, $month, $year);
            $slug = \Illuminate\Support\Str::slug($teacher->name) ?: ('gv-'.$teacher->id);
            $pdf = Pdf::loadView('pdf.teacher_payroll_person', compact('detail'))
                ->setPaper('a4', 'portrait');

            return $pdf->download(sprintf('bang-luong-gv-cua-toi-%s-%04d-%02d.pdf', $slug, $year, $month));
        }

        $detail = $this->staffPayroll->detail($user, $month, $year);
        $slug = \Illuminate\Support\Str::slug($user->name) ?: ('nv-'.$user->id);
        $pdf = Pdf::loadView('pdf.staff_payroll_person', compact('detail'))
            ->setPaper('a4', 'portrait');

        return $pdf->download(sprintf('bang-luong-nv-cua-toi-%s-%04d-%02d.pdf', $slug, $year, $month));
    }

    protected function resolveMode(Request $request, User $user): string
    {
        $type = strtolower(trim((string) $request->get('type', '')));

        if ($type === 'teacher' && $user->canViewTeacherMyPayroll()) {
            return 'teacher';
        }
        if ($type === 'staff' && $user->canViewStaffMyPayroll()) {
            return 'staff';
        }

        if ($user->hasDualMyPayroll()) {
            // Dual: mặc định lương GV nếu đã gắn hồ sơ, không thì lương NV
            return $user->linkedTeacher() ? 'teacher' : 'staff';
        }

        return $user->defaultMyPayrollMode();
    }

    /** @return array{0:int,1:int} */
    protected function monthYear(Request $request): array
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        return [$month, $year];
    }
}
