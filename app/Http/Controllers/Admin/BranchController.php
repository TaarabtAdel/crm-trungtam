<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\VietQr;
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

        $banks = VietQr::banks();

        return view('admin.system.branches', compact('branches', 'q', 'banks'));
    }

    public function store(Request $request)
    {
        Branch::create($this->validated($request, true));

        return back()->with('success', 'Đã thêm chi nhánh.');
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($this->validated($request, false));

        return back()->with('success', 'Đã cập nhật chi nhánh.');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return back()->with('success', 'Đã xóa chi nhánh.');
    }

    protected function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'bank_bin' => 'nullable|string|max:20',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:120',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $creating
            ? $request->boolean('is_active', true)
            : $request->boolean('is_active');
        $data['bank_bin'] = ! empty($data['bank_bin']) ? $data['bank_bin'] : null;
        $data['bank_account_number'] = ! empty($data['bank_account_number'])
            ? preg_replace('/\s+/', '', $data['bank_account_number'])
            : null;
        $data['bank_account_name'] = ! empty($data['bank_account_name'])
            ? mb_strtoupper(trim($data['bank_account_name']))
            : null;
        $data['bank_name'] = ($data['bank_bin'] && $data['bank_account_number'])
            ? VietQr::bankName($data['bank_bin'])
            : null;

        return $data;
    }
}
