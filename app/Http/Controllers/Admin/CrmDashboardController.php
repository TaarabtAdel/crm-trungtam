<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\User;
use App\Support\CurrentBranch;

class CrmDashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $branchId = CurrentBranch::id();

        $leadsToday = CurrentBranch::apply(Lead::query())->whereDate('created_at', $today);
        $interactionsToday = CurrentBranch::apply(Interaction::query())->whereDate('scheduled_at', $today);

        $kpi = [
            'new_leads' => (clone $leadsToday)->count(),
            'calls' => (clone $interactionsToday)->where(function ($q) {
                $q->where('type', 'like', '%gọi%')->orWhere('type', 'Cuộc gọi');
            })->count(),
            'won' => CurrentBranch::apply(Lead::query())->where('status', 'won')->whereDate('updated_at', $today)->count(),
            'expected_revenue' => (clone $leadsToday)->sum('expected_revenue'),
        ];

        $totalLeads = CurrentBranch::apply(Lead::query())->count();
        $wonLeads = CurrentBranch::apply(Lead::query())->where('status', 'won')->count();
        $kpi['conversion'] = $totalLeads > 0 ? round($wonLeads / $totalLeads * 100, 1) : 0;

        $performance = User::whereIn('role', ['sales', 'admin', 'super_admin'])
            ->with('branch')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get()
            ->map(function (User $user) use ($branchId) {
                $assigned = Lead::where('assigned_sales_id', $user->id)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->count();
                $calls = Interaction::where('sales_id', $user->id)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->count();
                $won = Lead::where('assigned_sales_id', $user->id)
                    ->where('status', 'won')
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->count();
                $revenue = Lead::where('assigned_sales_id', $user->id)
                    ->where('status', 'won')
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->sum('expected_revenue');

                return [
                    'name' => $user->name,
                    'branch' => $user->branch?->name,
                    'assigned' => $assigned,
                    'calls' => $calls,
                    'won' => $won,
                    'revenue' => $revenue,
                    'rate' => $assigned > 0 ? round($won / $assigned * 100, 1) : 0,
                ];
            });

        return view('admin.crm.sales', compact('kpi', 'performance'));
    }
}
