<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Finance\ExpenseService;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseService $expenses) {}

    public function index(Request $request)
    {
        $status = $request->get('status');
        $category = $request->get('category');

        $items = Expense::with(['branch', 'creator', 'approver', 'teacher', 'staffUser'])
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $teachers = CurrentBranch::apply(Teacher::query())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'branch_id']);
        $staffUsers = CurrentBranch::apply(User::query())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'branch_id']);

        return view('admin.finance.expenses', compact('items', 'branches', 'teachers', 'staffUsers', 'status', 'category'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category' => 'required|in:operations,salary,staff_salary,marketing,other',
            'teacher_id' => [
                Rule::requiredIf(fn () => $request->input('category') === 'salary'),
                'nullable',
                'exists:teachers,id',
            ],
            'user_id' => [
                Rule::requiredIf(fn () => $request->input('category') === 'staff_salary'),
                'nullable',
                'exists:users,id',
            ],
            'billing_month' => [
                Rule::requiredIf(fn () => in_array($request->input('category'), ['salary', 'staff_salary'], true)),
                'nullable',
                'date_format:Y-m',
            ],
            'amount' => 'required|numeric|min:1',
            'expense_date' => 'required|date',
            'note' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'teacher_id.required' => 'Vui lòng chọn giáo viên khi loại chi là Lương GV.',
            'user_id.required' => 'Vui lòng chọn nhân viên khi loại chi là Lương nhân viên.',
            'billing_month.required' => 'Vui lòng chọn tháng lương.',
        ]);

        if (($data['category'] ?? '') === 'salary') {
            $data['user_id'] = null;
        } elseif (($data['category'] ?? '') === 'staff_salary') {
            $data['teacher_id'] = null;
        } else {
            $data['teacher_id'] = null;
            $data['user_id'] = null;
            $data['billing_month'] = null;
        }

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
