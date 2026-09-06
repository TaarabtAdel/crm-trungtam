<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\User;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;

class CrmDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $branchId = CurrentBranch::id();
        $user = $request->user();
        $period = $request->get('period', 'all');
        if (! in_array($period, ['month', 'all'], true)) {
            $period = 'all';
        }

        $leadsBase = CurrentBranch::apply(Lead::query())
            ->when($user->isSales(), fn ($q) => $q->where('assigned_sales_id', $user->id));

        $interactionsBase = CurrentBranch::apply(Interaction::query())
            ->when($user->isSales(), fn ($q) => $q->where('sales_id', $user->id));

        $leadsToday = (clone $leadsBase)->whereDate('created_at', $today);
        $interactionsToday = (clone $interactionsBase)->whereDate('scheduled_at', $today);

        $openPipeline = (clone $leadsBase)->whereNotIn('status', ['won', 'lost']);

        $kpi = [
            'new_leads' => (clone $leadsToday)->count(),
            'calls' => (clone $interactionsToday)->where(function ($q) {
                $q->where('type', 'like', '%gọi%')->orWhere('type', 'Cuộc gọi');
            })->count(),
            'appointments' => (clone $interactionsToday)->count(),
            'won_today' => (clone $leadsBase)->where('status', 'won')->whereDate('updated_at', $today)->count(),
            'expected_revenue' => (clone $openPipeline)->sum('expected_revenue'),
        ];

        $totalLeads = (clone $leadsBase)->count();
        $wonLeads = (clone $leadsBase)->where('status', 'won')->count();
        $kpi['conversion'] = $totalLeads > 0 ? round($wonLeads / $totalLeads * 100, 1) : 0;

        $performance = User::whereIn('role', ['sales', 'admin', 'super_admin'])
            ->with('branch')
            ->where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($user->isSales(), fn ($q) => $q->where('id', $user->id))
            ->orderBy('name')
            ->get()
            ->map(function (User $salesUser) use ($branchId, $period) {
                $leads = Lead::where('assigned_sales_id', $salesUser->id)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->when($period === 'month', fn ($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year));

                $assigned = (clone $leads)->count();
                $won = (clone $leads)->where('status', 'won')->count();
                $revenue = (clone $leads)->where('status', 'won')->sum('expected_revenue');

                $calls = Interaction::where('sales_id', $salesUser->id)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->when($period === 'month', function ($q) {
                        $q->whereMonth('scheduled_at', now()->month)->whereYear('scheduled_at', now()->year);
                    })
                    ->count();

                return [
                    'id' => $salesUser->id,
                    'name' => $salesUser->name,
                    'email' => $salesUser->email,
                    'branch' => $salesUser->branch?->name,
                    'assigned' => $assigned,
                    'calls' => $calls,
                    'won' => $won,
                    'revenue' => (float) $revenue,
                    'rate' => $assigned > 0 ? round($won / $assigned * 100, 1) : 0,
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        $upcoming = (clone $interactionsBase)
            ->with(['lead', 'sales'])
            ->where('status', 'upcoming')
            ->whereDate('scheduled_at', '>=', $today)
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        return view('admin.crm.sales', compact('kpi', 'performance', 'upcoming', 'period'));
    }
}
