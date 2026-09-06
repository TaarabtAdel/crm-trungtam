<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use App\Services\Finance\ExpenseService;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $expenses) {}

    public function index(Request $request)
    {
        $status = $request->get('status');
        $category = $request->get('category');

        $items = Expense::with(['branch', 'creator', 'approver'])
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.finance.expenses', compact('items', 'branches', 'status', 'category'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category' => 'required|in:operations,salary,marketing,other',
            'amount' => 'required|numeric|min:1',
            'expense_date' => 'required|date',
            'note' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $this->expenses->create($data, $request->user(), $request->file('attachment'));

        return back()->with('success', 'Đã tạo đề xuất chi.');
    }

    public function approve(Request $request, Expense $expense)
    {
        $this->expenses->approve($expense, $request->user());

        return back()->with('success', 'Đã duyệt khoản chi.');
    }

    public function reject(Request $request, Expense $expense)
    {
        $this->expenses->reject($expense, $request->user());

        return back()->with('success', 'Đã từ chối khoản chi.');
    }

    public function markPaid(Expense $expense)
    {
        $this->expenses->markPaid($expense);

        return back()->with('success', 'Đã đánh dấu đã chi.');
    }

    public function destroy(Expense $expense)
    {
        $this->expenses->delete($expense);

        return back()->with('success', 'Đã xóa khoản chi.');
    }
}
