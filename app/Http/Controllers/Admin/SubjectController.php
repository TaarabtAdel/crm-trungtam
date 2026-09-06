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
        $status = $request->get('status');

        $subjects = Subject::with('branch')
            ->withCount('classes')
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.training.subjects', compact('subjects', 'branches', 'q', 'status'));
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
