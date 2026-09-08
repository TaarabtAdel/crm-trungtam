<div class="row">
    <div class="col-md-6">
        <div class="form-group"><label>Họ tên khách hàng *</label>
            <input name="name" class="form-control" value="{{ old('name', $lead->name ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Chi nhánh *</label>
            <select name="branch_id" class="form-control" required>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" @selected(old('branch_id', $lead->branch_id ?? $currentBranchId ?? '')==$b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>SĐT khách hàng</label>
            <input name="phone" class="form-control" value="{{ old('phone', $lead->phone ?? '') }}" placeholder="0988xxxxxx">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Email khách hàng</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $lead->email ?? '') }}" placeholder="email@gmail.com">
        </div>
    </div>

    <div class="col-12"><hr class="mt-1 mb-3"><h6 class="font-weight-bold mb-1">Người thân / Phụ huynh</h6>
        <small class="text-muted d-block mb-3">Không bắt buộc — giống hồ sơ học viên (liên hệ người thân).</small>
    </div>
    <div class="col-md-4">
        <div class="form-group"><label>Tên người thân</label>
            <input name="related_name" class="form-control" value="{{ old('related_name', $lead->related_name ?? '') }}" placeholder="Họ tên người liên hệ">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group"><label>SĐT người thân</label>
            <input name="related_phone" class="form-control" value="{{ old('related_phone', $lead->related_phone ?? '') }}" placeholder="0988xxxxxx">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group"><label>Email người thân</label>
            <input type="email" name="related_email" class="form-control" value="{{ old('related_email', $lead->related_email ?? '') }}" placeholder="email@gmail.com">
        </div>
    </div>

    <div class="col-12"><hr class="mt-1 mb-3"><h6 class="font-weight-bold mb-3">CRM / Sales</h6></div>
    <div class="col-md-6">
        <div class="form-group"><label>Nguồn tuyển sinh</label>
            <select name="source" class="form-control">
                @foreach(['Facebook Ads','Zalo','Google','Giới thiệu','Walk-in','Khác'] as $src)
                    <option value="{{ $src }}" @selected(old('source', $lead->source ?? 'Facebook Ads')===$src)>{{ $src }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Doanh thu dự kiến (VNĐ)</label>
            <input type="number" name="expected_revenue" class="form-control" value="{{ old('expected_revenue', $lead->expected_revenue ?? 0) }}" min="0">
            <small class="text-muted">Ước tính pipeline Sales — không phải doanh thu thực.</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Sales phụ trách</label>
            @if(auth()->user()->isSales())
                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                <input type="hidden" name="assigned_sales_id" value="{{ auth()->id() }}">
                <small class="text-muted">Lead sẽ gắn cho bạn (Sales đang đăng nhập).</small>
            @else
                <select name="assigned_sales_id" class="form-control js-lead-sales-select" style="width:100%" data-placeholder="-- Chọn Sales phụ trách --">
                    <option value=""></option>
                    @foreach($salesUsers as $u)
                        <option value="{{ $u->id }}" @selected(old('assigned_sales_id', $lead->assigned_sales_id ?? '')==$u->id)>{{ $u->name }}@if($u->email) — {{ $u->email }}@endif</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Trạng thái</label>
            <select name="status" class="form-control">
                @foreach(\App\Models\Lead::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(old('status', $lead->status ?? 'new')===$k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Hạn xử lý</label>
            <input type="date" name="follow_up_at" class="form-control" value="{{ old('follow_up_at', isset($lead) && $lead->follow_up_at ? $lead->follow_up_at->format('Y-m-d') : '') }}">
            <small class="text-muted">Nhắc khi lead còn <strong>Mới</strong>. Để trống khi tạo mới sẽ mặc định +2 ngày nếu đã gán Sales.</small>
        </div>
    </div>
</div>
