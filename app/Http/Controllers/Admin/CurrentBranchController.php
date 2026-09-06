<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;

class CurrentBranchController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'nullable|string',
        ]);

        $branchId = $data['branch_id'] ?? null;
        if ($branchId && $branchId !== 'all') {
            Branch::where('is_active', true)->whereKey($branchId)->firstOrFail();
            CurrentBranch::set((int) $branchId);
        } else {
            CurrentBranch::set(null);
        }

        return back();
    }
}
