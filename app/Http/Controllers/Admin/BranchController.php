<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\CurrentBranch;
use App\Support\SmartCache;
use App\Support\VietQr;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $branches = Branch::query()
            ->when(CurrentBranch::forcedId(), fn ($query) => $query->whereKey(CurrentBranch::forcedId()))
            ->when($q, fn ($query) => $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $banks = VietQr::banks();
        // Thêm / xóa chi nhánh chỉ khi không bị khóa theo hồ sơ
        $canManageAllBranches = CurrentBranch::canSwitch()
            && ($request->user()?->hasPermission('system.branches.manage') ?? false);

        return view('admin.system.branches', compact('branches', 'q', 'banks', 'canManageAllBranches'));
    }

    public function store(Request $request)
    {
        if (! CurrentBranch::canSwitch()) {
            abort(403, 'Bạn chỉ được thao tác dữ liệu thuộc chi nhánh của mình.');
        }

        Branch::create($this->validated($request, true));
        SmartCache::forgetActiveBranches();

        return back()->with('success', 'Đã thêm chi nhánh.');
    }

    public function update(Request $request, Branch $branch)
    {
        CurrentBranch::authorize($branch->id);
        $branch->update($this->validated($request, false));
        SmartCache::forgetActiveBranches();

        return back()->with('success', 'Đã cập nhật chi nhánh.');
    }

    public function destroy(Branch $branch)
    {
        if (! CurrentBranch::canSwitch()) {
            abort(403, 'Bạn chỉ được thao tác dữ liệu thuộc chi nhánh của mình.');
        }

        $branch->delete();
        SmartCache::forgetActiveBranches();

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
