<div class="form-group"><label>Chọn khách hàng *</label>
    <select name="lead_id" class="form-control" required>
        <option value="">-- Chọn --</option>
        @foreach($leads as $lead)
            <option value="{{ $lead->id }}" @selected(old('lead_id', $item->lead_id ?? '')==$lead->id)>{{ $lead->name }} — {{ $lead->phone }}</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Nhân viên phụ trách (Sales)</label>
    <select name="sales_id" class="form-control">
        <option value="">-- Chọn --</option>
        @foreach($salesUsers as $u)
            <option value="{{ $u->id }}" @selected(old('sales_id', $item->sales_id ?? '')==$u->id)>{{ $u->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Loại tương tác *</label>
    <select name="type" class="form-control" required>
        @foreach(['Cuộc gọi','Lịch hẹn Test','Nhắn tin','Gặp trực tiếp'] as $t)
            <option value="{{ $t }}" @selected(old('type', $item->type ?? 'Lịch hẹn Test')===$t)>{{ $t }}</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Thời gian hẹn</label>
    <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at', isset($item->scheduled_at) ? $item->scheduled_at->format('Y-m-d\TH:i') : '') }}">
</div>
<div class="form-group"><label>Trạng thái</label>
    <select name="status" class="form-control">
        @foreach(['upcoming'=>'Sắp diễn ra','done'=>'Hoàn thành','cancelled'=>'Hủy','no_show'=>'Không đến'] as $k=>$v)
            <option value="{{ $k }}" @selected(old('status', $item->status ?? 'upcoming')===$k)>{{ $v }}</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Ghi chú</label><textarea name="notes" class="form-control" rows="3" placeholder="Nội dung cần trao đổi...">{{ old('notes', $item->notes ?? '') }}</textarea></div>
