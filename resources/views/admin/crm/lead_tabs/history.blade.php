<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="border rounded p-3 h-100">
            <h6 class="font-weight-bold mb-3">Thêm lịch sử tư vấn</h6>
            @canPerm('crm.interactions.manage', 'crm.leads.manage')
            <form method="POST" action="{{ route('admin.leads.interactions.store', $lead) }}">
                @csrf
                @unless(auth()->user()->isSales())
                <div class="form-group">
                    <label>Sales phụ trách</label>
                    <select name="sales_id" class="form-control">
                        <option value="">-- Mặc định: Sales của lead --</option>
                        @foreach($salesUsers as $u)
                            <option value="{{ $u->id }}" @selected(old('sales_id', $lead->assigned_sales_id)==$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endunless
                <div class="form-group">
                    <label>Loại tương tác *</label>
                    <select name="type" class="form-control" required>
                        @foreach(['Cuộc gọi','Lịch hẹn Test','Nhắn tin','Gặp trực tiếp','Tư vấn','Khác'] as $t)
                            <option value="{{ $t }}" @selected(old('type', 'Cuộc gọi')===$t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Thời gian</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at', now()->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        @foreach(\App\Models\Interaction::statusOptions() as $k=>$v)
                            <option value="{{ $k }}" @selected(old('status', 'done')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Nội dung tư vấn</label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Ghi chú kết quả cuộc gọi, nhu cầu khách...">{{ old('notes') }}</textarea>
                </div>
                <button class="btn btn-primary btn-sm btn-block">Lưu tư vấn</button>
            </form>
            @else
                <p class="text-muted small mb-0">Bạn không có quyền thêm lịch sử tư vấn.</p>
            @endcanPerm
        </div>
    </div>

    <div class="col-lg-8 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Timeline tư vấn</strong>
        </div>
        @forelse($interactions as $item)
            <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:.5rem">
                    <div>
                        <strong>{{ $item->type }}</strong>
                        <span class="badge lead-status {{ $item->statusBadgeClass() }} ml-1">{{ $item->statusLabel() }}</span>
                        <div class="small text-muted mt-1">
                            {{ optional($item->scheduled_at)->format('d/m/Y H:i') ?? $item->created_at->format('d/m/Y H:i') }}
                            · {{ $item->sales?->name ?? '—' }}
                        </div>
                    </div>
                </div>
                @if($item->notes)
                    <div class="mt-2 mb-0" style="white-space:pre-wrap">{{ $item->notes }}</div>
                @else
                    <div class="mt-2 text-muted small mb-0">Không có ghi chú.</div>
                @endif
            </div>
        @empty
            <div class="text-center text-muted border rounded py-4">Chưa có lịch sử tư vấn.</div>
        @endforelse

        @if(method_exists($interactions, 'links'))
            <div class="mt-2">{{ $interactions->appends(['tab' => 'history'])->links() }}</div>
        @endif
    </div>
</div>
