<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CourseClass;
use App\Models\Student;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $classIds = $data['class_ids'] ?? [];
        unset($data['class_ids']);
        $student = Student::create($data);
        $student->classes()->sync($classIds);

        return back()->with('success', 'Đã thêm học viên.');
    }

    public function update(Request $request, Student $student)
    {
        $data = $this->validated($request);
        $classIds = $data['class_ids'] ?? [];
        unset($data['class_ids']);
        $student->update($data);
        $student->classes()->sync($classIds);

        return back()->with('success', 'Đã cập nhật học viên.');
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return back()->with('success', 'Đã xóa học viên.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:Nam,Nữ,Khác',
            'parent_phone' => 'nullable|string|max:30',
            'parent_name' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email',
            'status' => 'nullable|in:studying,paused,graduated,dropped',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:classes,id',
        ]);
        $data['status'] = $data['status'] ?? 'studying';

        return $data;
    }
}
