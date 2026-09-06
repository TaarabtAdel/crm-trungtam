<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $user = $request->user();

        $query = Invoice::with(['student', 'courseClass', 'sales'])
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->when($user->isSales(), fn ($q) => $q->where('sales_id', $user->id))
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('remaining_amount', '>', 0);

        if ($filter === 'overdue') {
            $query->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString());
        } elseif ($filter === 'upcoming') {
            $query->whereNotNull('due_date')
                ->whereDate('due_date', '>=', now()->toDateString())
                ->whereDate('due_date', '<=', now()->addDays(7)->toDateString());
        }

        $debts = $query->orderBy('due_date')->paginate(20)->withQueryString();

        return view('admin.finance.debts', compact('debts', 'filter'));
    }
}
