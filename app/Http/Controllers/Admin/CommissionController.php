<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\CourseClass;
use App\Services\Finance\CommissionService;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function __construct(protected CommissionService $commissions) {}

    public function index(Request $request)
    {
        $status = $request->get('status');
        $user = $request->user();

        $items = Commission::with(['sales', 'invoice.student', 'invoice.courseClass'])
            ->when($user->isSales(), fn ($q) => $q->where('sales_id', $user->id))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->whereHas('invoice', fn ($q) => CurrentBranch::apply($q))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.finance.commissions', compact('items', 'status'));
    }

    public function markPaid(Commission $commission)
    {
        if (request()->user()->isSales()) {
            abort(403);
        }
        $this->commissions->markPaid($commission);

        return back()->with('success', 'Đã đánh dấu thanh toán hoa hồng.');
    }

    public function rules()
    {
        $rules = CommissionRule::with('courseClass')->orderByDesc('is_active')->orderBy('scope')->get();
        $classes = CurrentBranch::apply(CourseClass::query())->orderBy('name')->get();

        return view('admin.finance.commission_rules', compact('rules', 'classes'));
    }

    public function storeRule(Request $request)
    {
        $data = $request->validate([
            'scope' => 'required|in:global,class',
            'class_id' => 'nullable|required_if:scope,class|exists:classes,id',
            'percent' => 'required|numeric|min:0|max:100',
            'tier_min_revenue' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($data['scope'] === 'global') {
            $data['class_id'] = null;
        }
        $data['is_active'] = $request->boolean('is_active', true);

        CommissionRule::create($data);

        return back()->with('success', 'Đã thêm quy tắc hoa hồng.');
    }

    public function updateRule(Request $request, CommissionRule $rule)
    {
        $data = $request->validate([
            'percent' => 'required|numeric|min:0|max:100',
            'tier_min_revenue' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $rule->update($data);

        return back()->with('success', 'Đã cập nhật quy tắc.');
    }

    public function destroyRule(CommissionRule $rule)
    {
        $rule->delete();

        return back()->with('success', 'Đã xóa quy tắc.');
    }
}
