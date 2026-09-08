@php $isSales = auth()->user()->isSales(); @endphp
<div class="form-group"><label>Chọn khách hàng *</label>
    <select name="lead_id" class="form-control js-interaction-lead" required style="width:100%" data-placeholder="Tìm lead theo tên, SĐT...">
        <option value=""></option>
        @php
            $prefillLead = null;
            if (! empty($item?->lead)) {
                $prefillLead = $item->lead;
            } elseif (! empty($item?->lead_id) && isset($leads)) {
                $prefillLead = $leads[$item->lead_id] ?? $leads->firstWhere('id', $item->lead_id);
            } elseif (old('lead_id') && isset($leads)) {
                $prefillLead = $leads[(int) old('lead_id')] ?? $leads->firstWhere('id', (int) old('lead_id'));
            }
        @endphp
        @if($prefillLead)
            <option value="{{ $prefillLead->id }}" selected>
                {{ $prefillLead->name }}@if($prefillLead->phone) — {{ $prefillLead->phone }}@endif
            </option>
        @endif
    </select>
</div>
@if(!$isSales)
<div class="form-group"><label>Nhân viên phụ trách (Sales)</label>
    <select name="sales_id" class="form-control js-interaction-sales" style="width:100%" data-placeholder="Chọn Sales">
        <option value=""></option>
        @foreach($salesUsers as $u)
            <option value="{{ $u->id }}" @selected(old('sales_id', $item->sales_id ?? '')==$u->id)>{{ $u->name }}@if($u->email) — {{ $u->email }}@endif</option>
        @endforeach
    </select>
</div>
@endif
<div class="form-group"><label>Loại tương tác *</label>
    <select name="type" class="form-control" required>
        @foreach(\App\Models\Interaction::typeOptions() as $k=>$v)
            <option value="{{ $k }}" @selected(old('type', $item->type ?? 'Lịch hẹn Test')===$k)>{{ $v }}</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Thời gian hẹn</label>
    <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at', isset($item->scheduled_at) ? $item->scheduled_at->format('Y-m-d\TH:i') : '') }}">
</div>
<div class="form-group"><label>Trạng thái</label>
    <select name="status" class="form-control">
        @foreach(\App\Models\Interaction::statusOptions() as $k=>$v)
            <option value="{{ $k }}" @selected(old('status', $item->status ?? 'upcoming')===$k)>{{ $v }}</option>
        @endforeach
    </select>
</div>
<div class="form-group mb-0"><label>Ghi chú</label><textarea name="notes" class="form-control" rows="3" placeholder="Nội dung cần trao đổi...">{{ old('notes', $item->notes ?? '') }}</textarea></div>
