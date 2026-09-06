<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $branches = Branch::query()
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.system.branches', compact('branches', 'q'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        Branch::create($data);

        return back()->with('success', 'Đã thêm chi nhánh.');
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $branch->update($data);

        return back()->with('success', 'Đã cập nhật chi nhánh.');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return back()->with('success', 'Đã xóa chi nhánh.');
    }
}
