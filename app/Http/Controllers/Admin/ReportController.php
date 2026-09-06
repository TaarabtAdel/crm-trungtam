<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\CurrentBranch;

class ReportController extends Controller
{
    public function index()
    {
        $totalLeads = CurrentBranch::apply(Lead::query())->count();
        $wonLeads = CurrentBranch::apply(Lead::query())->where('status', 'won')->count();

        $report = [
            'leads' => $totalLeads,
            'won' => $wonLeads,
            'conversion' => $totalLeads > 0 ? round($wonLeads / $totalLeads * 100, 1) : 0,
            'expected_revenue' => CurrentBranch::apply(Lead::query())->sum('expected_revenue'),
            'paid_revenue' => CurrentBranch::applyThrough(Invoice::query()->where('status', 'paid'), 'student')->sum('amount'),
            'unpaid_revenue' => CurrentBranch::applyThrough(Invoice::query()->where('status', 'unpaid'), 'student')->sum('amount'),
            'students' => CurrentBranch::apply(Student::query())->count(),
            'studying' => CurrentBranch::apply(Student::query())->where('status', 'studying')->count(),
            'classes' => CurrentBranch::apply(CourseClass::query())->count(),
            'teachers' => CurrentBranch::apply(Teacher::query())->count(),
        ];

        return view('admin.system.reports', compact('report'));
    }
}
