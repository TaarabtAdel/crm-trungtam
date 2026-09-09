<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\User;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $type = $request->get('type');
        $status = $request->get('status');
        $user = $request->user();

        $interactions = Interaction::with(['lead', 'sales', 'branch'])
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->when($user->isSales(), fn ($query) => $query->where('sales_id', $user->id))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('notes', 'like', "%{$q}%")
                        ->orWhereHas('lead', function ($lead) use ($q) {
                            $lead->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%");
                        });
                });
            })
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('scheduled_at')
            ->paginate(15)
            ->withQueryString();

        // Prefill Select2 (edit / old) — không load toàn bộ lead
        $leads = collect();
        $prefillIds = $interactions->pluck('lead_id')->filter()->unique()->values();
        if (old('lead_id')) {
            $prefillIds = $prefillIds->push((int) old('lead_id'))->unique()->values();
        }
        if ($prefillIds->isNotEmpty()) {
            $leads = Lead::query()->whereIn('id', $prefillIds)->get()->keyBy('id');
        }

        $salesUsers = User::whereIn('role', ['sales', 'admin', 'super_admin'])
            ->where('is_active', true)
            ->when(CurrentBranch::id(), fn ($query) => $query->where('branch_id', CurrentBranch::id()))
            ->orderBy('name')
            ->get();

        return view('admin.crm.interactions', compact(
            'interactions', 'leads', 'salesUsers', 'q', 'type', 'status'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $lead = Lead::findOrFail($data['lead_id']);
        $this->authorizeLeadAccess($request, $lead);
        $data['branch_id'] = $lead->branch_id;
        if ($request->user()->isSales()) {
            $data['sales_id'] = $request->user()->id;
        } elseif (empty($data['sales_id'])) {
            $data['sales_id'] = $lead->assigned_sales_id;
        }
        Interaction::create($data);

        if ($request->input('redirect_to') === 'interactions') {
            return redirect()
                ->route('admin.interactions.index')
                ->with('success', 'Đã thêm lịch hẹn cho '.$lead->name.'.');
        }

        return back()->with('success', 'Đã thêm lịch hẹn / tương tác.');
    }

    public function update(Request $request, Interaction $interaction)
    {
        $this->authorizeInteractionAccess($request, $interaction);
        $data = $this->validated($request);
        $lead = Lead::findOrFail($data['lead_id']);
        $this->authorizeLeadAccess($request, $lead);
        $data['branch_id'] = $lead->branch_id;
        if ($request->user()->isSales()) {
            $data['sales_id'] = $request->user()->id;
        }
        $interaction->update($data);

        return back()->with('success', 'Đã cập nhật tương tác.');
    }

    public function destroy(Request $request, Interaction $interaction)
    {
        $this->authorizeInteractionAccess($request, $interaction);
        $interaction->delete();

        return back()->with('success', 'Đã xóa tương tác.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'sales_id' => 'nullable|exists:users,id',
            'type' => 'required|string|max:100',
            'scheduled_at' => 'nullable|date',
            'status' => 'nullable|in:upcoming,done,cancelled,no_show',
            'notes' => 'nullable|string',
        ]);
        $data['status'] = $data['status'] ?? 'upcoming';

        return $data;
    }

    protected function authorizeLeadAccess(Request $request, Lead $lead): void
    {
        if ($request->user()->isSales() && (int) $lead->assigned_sales_id !== (int) $request->user()->id) {
            abort(403);
        }
    }

    protected function authorizeInteractionAccess(Request $request, Interaction $interaction): void
    {
        if ($request->user()->isSales() && (int) $interaction->sales_id !== (int) $request->user()->id) {
            abort(403);
        }
    }
}
