<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Subject;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $subjects = Subject::with('branch')
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.training.subjects', compact('subjects', 'branches', 'q'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);
        $data['status'] = $data['status'] ?? 'active';
        Subject::create($data);

        return back()->with('success', 'Đã thêm môn học.');
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
        $subject->update($data);

        return back()->with('success', 'Đã cập nhật môn học.');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();

        return back()->with('success', 'Đã xóa môn học.');
    }
}
