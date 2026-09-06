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
        $interactions = Interaction::with(['lead', 'sales', 'branch'])
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->when($q, function ($query) use ($q) {
                $query->whereHas('lead', fn ($l) => $l->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"));
            })
            ->when($type, fn ($query) => $query->where('type', $type))
            ->latest('scheduled_at')
            ->paginate(15)
            ->withQueryString();

        $leads = CurrentBranch::apply(Lead::query())->whereNotIn('status', ['won', 'lost'])->orderBy('name')->get();
        $salesUsers = User::whereIn('role', ['sales', 'admin', 'super_admin'])
            ->where('is_active', true)
            ->when(CurrentBranch::id(), fn ($q) => $q->where('branch_id', CurrentBranch::id()))
            ->orderBy('name')
            ->get();

        return view('admin.crm.interactions', compact('interactions', 'leads', 'salesUsers', 'q', 'type'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $lead = Lead::findOrFail($data['lead_id']);
        $data['branch_id'] = $lead->branch_id;
        Interaction::create($data);

        return back()->with('success', 'Đã thêm lịch hẹn / tương tác.');
    }

    public function update(Request $request, Interaction $interaction)
    {
        $data = $this->validated($request);
        $lead = Lead::findOrFail($data['lead_id']);
        $data['branch_id'] = $lead->branch_id;
        $interaction->update($data);

        return back()->with('success', 'Đã cập nhật tương tác.');
    }

    public function destroy(Interaction $interaction)
    {
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
}
