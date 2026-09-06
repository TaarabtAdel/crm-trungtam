<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $classes = CurrentBranch::apply(CourseClass::query())->where('status', 'active')->orderBy('name')->get();
        $classId = $request->get('class_id', $classes->first()?->id);
        $date = $request->get('session_date', now()->toDateString());
        $courseClass = $classId ? CourseClass::with('students')->find($classId) : null;
        $attendances = [];

        if ($courseClass) {
            $attendances = Attendance::where('class_id', $courseClass->id)
                ->whereDate('session_date', $date)
                ->pluck('status', 'student_id')
                ->all();
        }

        return view('admin.students.attendances', compact('classes', 'courseClass', 'classId', 'date', 'attendances'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'session_date' => 'required|date',
            'statuses' => 'nullable|array',
            'statuses.*' => 'in:present,absent,late',
            'mark_session_completed' => 'nullable|boolean',
        ]);

        $class = CourseClass::with('students')->findOrFail($data['class_id']);
        $statuses = $data['statuses'] ?? [];

        foreach ($class->students as $student) {
            $status = $statuses[$student->id] ?? 'absent';
            Attendance::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'student_id' => $student->id,
                    'session_date' => $data['session_date'],
                ],
                ['status' => $status]
            );
        }

        if ($request->boolean('mark_session_completed')) {
            ClassSession::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'session_date' => $data['session_date'],
                ],
                [
                    'teacher_id' => $class->teacher_id,
                    'start_time' => $class->start_time,
                    'end_time' => $class->end_time,
                    'status' => 'completed',
                ]
            );
        }

        return redirect()
            ->route('admin.attendances.index', ['class_id' => $class->id, 'session_date' => $data['session_date']])
            ->with('success', 'Đã lưu điểm danh.');
    }
}
