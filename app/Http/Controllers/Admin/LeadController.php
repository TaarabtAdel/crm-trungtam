<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadExcelImporter;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $user = $request->user();

        $leads = Lead::with(['branch', 'assignedSales'])
            ->withCount('interactions')
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->tap(fn ($query) => $query->visibleTo($user))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('related_name', 'like', "%{$q}%")
                        ->orWhere('related_phone', 'like', "%{$q}%")
                        ->orWhere('related_email', 'like', "%{$q}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->from, fn ($query) => $query->whereDate('created_at', '>=', $request->from))
            ->when($request->to, fn ($query) => $query->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $salesUsers = User::whereIn('role', ['sales', 'admin', 'super_admin'])
            ->where('is_active', true)
            ->when(CurrentBranch::id(), fn ($q) => $q->where('branch_id', CurrentBranch::id()))
            ->when($user->isSales(), fn ($q) => $q->where('id', $user->id))
            ->orderBy('name')
            ->get();

        return view('admin.crm.leads', compact('leads', 'branches', 'salesUsers', 'q', 'status'));
    }

    public function show(Request $request, Lead $lead)
    {
        $this->ensureSalesOwnsLead($request, $lead);

        $tab = $request->get('tab', 'info');
        if (! in_array($tab, ['info', 'history'], true)) {
            $tab = 'info';
        }

        $lead->load(['branch', 'assignedSales']);
        $lead->loadCount('interactions');

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $salesUsers = User::whereIn('role', ['sales', 'admin', 'super_admin'])
            ->where('is_active', true)
            ->when(CurrentBranch::id(), fn ($q) => $q->where('branch_id', CurrentBranch::id()))
            ->when($request->user()->isSales(), fn ($q) => $q->where('id', $request->user()->id))
            ->orderBy('name')
            ->get();

        $interactions = collect();
        if ($tab === 'history') {
            $interactions = $lead->interactions()
                ->with('sales')
                ->latest('scheduled_at')
                ->latest('id')
                ->paginate(20)
                ->withQueryString();
        }

        return view('admin.crm.lead_show', compact(
            'lead', 'tab', 'branches', 'salesUsers', 'interactions'
        ));
    }

    public function store(Request $request)
    {
        Lead::create($this->validated($request));

        return back()->with('success', 'Đã thêm lead.');
    }

    public function update(Request $request, Lead $lead)
    {
        $this->ensureSalesOwnsLead($request, $lead);
        $lead->update($this->validated($request));

        if ($request->boolean('from_detail')) {
            return redirect()
                ->route('admin.leads.show', ['lead' => $lead, 'tab' => 'info'])
                ->with('success', 'Đã cập nhật thông tin lead.');
        }

        return back()->with('success', 'Đã cập nhật lead.');
    }

    public function destroy(Request $request, Lead $lead)
    {
        $this->ensureSalesOwnsLead($request, $lead);
        $lead->delete();

        return redirect()->route('admin.leads.index')->with('success', 'Đã xóa lead.');
    }

    public function storeInteraction(Request $request, Lead $lead)
    {
        $this->ensureSalesOwnsLead($request, $lead);

        $data = $request->validate([
            'sales_id' => 'nullable|exists:users,id',
            'type' => 'required|string|max:100',
            'scheduled_at' => 'nullable|date',
            'status' => 'nullable|in:upcoming,done,cancelled,no_show',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        if ($user->isSales()) {
            $data['sales_id'] = $user->id;
        } else {
            $data['sales_id'] = $data['sales_id'] ?: $lead->assigned_sales_id ?: $user->id;
        }

        Interaction::create([
            'lead_id' => $lead->id,
            'branch_id' => $lead->branch_id,
            'sales_id' => $data['sales_id'] ?? null,
            'type' => $data['type'],
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => $data['status'] ?? 'upcoming',
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.leads.show', ['lead' => $lead, 'tab' => 'history'])
            ->with('success', 'Đã thêm lịch sử tư vấn.');
    }

    public function importTemplate(LeadExcelImporter $importer): StreamedResponse
    {
        return $importer->downloadTemplate();
    }

    public function import(Request $request, LeadExcelImporter $importer)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'Chỉ chấp nhận file .xlsx, .xls hoặc .csv.',
        ]);

        $result = $importer->import($request->file('file'));

        if ($result['imported'] === 0 && count($result['errors']) > 0) {
            return back()
                ->with('error', 'Không nhập được lead nào.')
                ->with('import_errors', $result['errors']);
        }

        $message = "Đã nhập {$result['imported']} lead";
        if ($result['skipped'] > 0) {
            $message .= ", bỏ qua {$result['skipped']} dòng";
        }
        $message .= '.';

        return back()
            ->with('success', $message)
            ->with('import_errors', $result['errors']);
    }

    protected function ensureSalesOwnsLead(Request $request, Lead $lead): void
    {
        $user = $request->user();
        if ($user->isSales() && (int) $lead->assigned_sales_id !== (int) $user->id) {
            abort(403, 'Bạn chỉ được thao tác lead được gán cho mình.');
        }
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email',
            'related_name' => 'nullable|string|max:255',
            'related_phone' => 'nullable|string|max:30',
            'related_email' => 'nullable|email',
            'source' => 'nullable|string|max:100',
            'branch_id' => 'required|exists:branches,id',
            'expected_revenue' => 'nullable|numeric|min:0',
            'assigned_sales_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:new,contacted,interested,won,lost',
        ]);
        $data['expected_revenue'] = $data['expected_revenue'] ?? 0;
        $data['status'] = $data['status'] ?? 'new';
        $data['related_name'] = $data['related_name'] ?: null;
        $data['related_phone'] = $data['related_phone'] ?: null;
        $data['related_email'] = $data['related_email'] ?: null;

        if ($request->user()->isSales()) {
            $data['assigned_sales_id'] = $request->user()->id;
        } else {
            $data['assigned_sales_id'] = $data['assigned_sales_id'] ?: null;
        }

        return $data;
    }
}
