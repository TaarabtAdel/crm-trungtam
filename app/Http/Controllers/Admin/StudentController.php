<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\CourseClass;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\Finance\InvoiceService;
use App\Services\StudentExcelImporter;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $classId = $request->get('class_id');

        $students = Student::with(['branch', 'classes'])
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('parent_phone', 'like', "%{$q}%")
                        ->orWhere('parent_name', 'like', "%{$q}%")
                        ->orWhere('parent_email', 'like', "%{$q}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($classId, fn ($query) => $query->whereHas('classes', fn ($c) => $c->where('classes.id', $classId)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $classes = CurrentBranch::apply(CourseClass::query())->where('status', 'active')->orderBy('name')->get();

        return view('admin.students.students', compact(
            'students', 'branches', 'classes', 'q', 'status', 'classId'
        ));
    }

    public function show(Request $request, Student $student)
    {
        $tab = $request->get('tab', 'info');
        if (! in_array($tab, ['info', 'classes', 'tuition', 'attendance'], true)) {
            $tab = 'info';
        }

        $student->load(['branch']);
        $student->loadCount(['classes', 'invoices', 'attendances']);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $classes = CurrentBranch::apply(CourseClass::query())->where('status', 'active')->orderBy('name')->get();

        $studentClasses = collect();
        $availableClasses = collect();
        $invoices = collect();
        $attendances = collect();
        $tuitionSummary = [
            'total' => 0,
            'paid' => 0,
            'remaining' => 0,
            'overdue' => 0,
        ];

        if ($tab === 'info') {
            $student->load('classes');
            $tuitionSummary = [
                'total' => (float) $student->invoices()->where('status', '!=', 'cancelled')->sum('amount'),
                'paid' => (float) $student->invoices()->where('status', '!=', 'cancelled')->sum('paid_amount'),
                'remaining' => (float) $student->invoices()->whereIn('status', ['unpaid', 'partial'])->sum('remaining_amount'),
                'overdue' => (int) $student->invoices()
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
            ];
        }

        if ($tab === 'classes') {
            $studentClasses = $student->classes()
                ->with(['subject', 'teacher', 'branch'])
                ->orderBy('name')
                ->get();
            $enrolledIds = $studentClasses->pluck('id')->all();
            $availableClasses = CurrentBranch::apply(CourseClass::query())
                ->where('status', 'active')
                ->when($enrolledIds, fn ($q) => $q->whereNotIn('id', $enrolledIds))
                ->when($student->branch_id, fn ($q) => $q->where('branch_id', $student->branch_id))
                ->orderBy('name')
                ->get();
        }

        if ($tab === 'tuition') {
            $invoices = Invoice::with(['courseClass', 'payments'])
                ->where('student_id', $student->id)
                ->latest()
                ->paginate(15)
                ->withQueryString();
            $tuitionSummary = [
                'total' => (float) Invoice::where('student_id', $student->id)->where('status', '!=', 'cancelled')->sum('amount'),
                'paid' => (float) Invoice::where('student_id', $student->id)->where('status', '!=', 'cancelled')->sum('paid_amount'),
                'remaining' => (float) Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial'])->sum('remaining_amount'),
                'overdue' => (int) Invoice::where('student_id', $student->id)
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
            ];
        }

        if ($tab === 'attendance') {
            $attendances = Attendance::with('courseClass')
                ->where('student_id', $student->id)
                ->latest('session_date')
                ->paginate(20)
                ->withQueryString();
        }

        return view('admin.students.student_show', compact(
            'student', 'tab', 'branches', 'classes',
            'studentClasses', 'availableClasses',
            'invoices', 'attendances', 'tuitionSummary'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $classIds = $data['class_ids'] ?? [];
        unset($data['class_ids']);
        $student = Student::create($data);
        $student->classes()->sync($classIds);

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', 'Đã thêm học viên.');
    }

    public function update(Request $request, Student $student)
    {
        $data = $this->validated($request, $request->boolean('from_detail'));
        $classIds = $data['class_ids'] ?? null;
        unset($data['class_ids']);
        $student->update($data);

        if (is_array($classIds)) {
            $student->classes()->sync($classIds);
        }

        if ($request->boolean('from_detail')) {
            return redirect()
                ->route('admin.students.show', ['student' => $student, 'tab' => 'info'])
                ->with('success', 'Đã cập nhật học viên.');
        }

        return back()->with('success', 'Đã cập nhật học viên.');
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('admin.students.index')->with('success', 'Đã xóa học viên.');
    }

    public function importTemplate(StudentExcelImporter $importer): StreamedResponse
    {
        return $importer->downloadTemplate();
    }

    public function import(Request $request, StudentExcelImporter $importer)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'Chỉ chấp nhận file .xlsx, .xls hoặc .csv.',
        ]);

        $result = $importer->import($request->file('file'));

        if ($result['imported'] === 0 && count($result['errors']) > 0) {
            return back()
                ->with('error', 'Không nhập được học viên nào.')
                ->with('import_errors', $result['errors']);
        }

        $message = "Đã nhập {$result['imported']} học viên";
        if ($result['skipped'] > 0) {
            $message .= ", bỏ qua {$result['skipped']} dòng";
        }
        $message .= '.';

        return back()
            ->with('success', $message)
            ->with('import_errors', $result['errors']);
    }

    public function attachClass(Request $request, Student $student)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'create_invoice' => 'nullable|boolean',
            'installment_count' => 'nullable|integer|min:1|max:24',
        ]);

        $class = CourseClass::findOrFail($data['class_id']);
        $student->classes()->syncWithoutDetaching([$class->id]);

        $message = 'Đã thêm học viên vào lớp.';
        if ($request->boolean('create_invoice', true)) {
            $invoice = app(InvoiceService::class)->createEnrollmentInvoice($class, $student, [
                'installment_count' => (int) ($data['installment_count'] ?? 1),
            ]);
            if ($invoice) {
                $message .= ' Đã tạo hóa đơn '.$invoice->code.'.';
            }
        }

        return redirect()
            ->route('admin.students.show', ['student' => $student, 'tab' => 'classes'])
            ->with('success', $message);
    }

    public function detachClass(Student $student, CourseClass $class)
    {
        $student->classes()->detach($class->id);

        return redirect()
            ->route('admin.students.show', ['student' => $student, 'tab' => 'classes'])
            ->with('success', 'Đã gỡ học viên khỏi lớp.');
    }

    protected function validated(Request $request, bool $fromDetail = false): array
    {
        $rules = [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:Nam,Nữ,Khác',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'parent_phone' => 'nullable|string|max:30',
            'parent_name' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email',
            'status' => 'nullable|in:studying,paused,graduated,dropped',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ];

        if (! $fromDetail) {
            $rules['class_ids'] = 'nullable|array';
            $rules['class_ids.*'] = 'exists:classes,id';
        }

        $data = $request->validate($rules);
        $data['status'] = $data['status'] ?? 'studying';

        return $data;
    }
}
