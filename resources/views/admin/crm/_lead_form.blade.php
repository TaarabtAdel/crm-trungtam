<div class="row">
    <div class="col-md-6">
        <div class="form-group"><label>Họ tên khách hàng *</label><input name="name" class="form-control" value="{{ old('name', $lead->name ?? '') }}" required></div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Số điện thoại *</label><input name="phone" class="form-control" value="{{ old('phone', $lead->phone ?? '') }}" placeholder="0988xxxxxx" required></div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $lead->email ?? '') }}" placeholder="email@gmail.com"></div>
    </div>
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
        <div class="form-group"><label>Chi nhánh *</label>
            <select name="branch_id" class="form-control" required>
                @foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $lead->branch_id ?? $currentBranchId ?? '')==$b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Doanh thu dự kiến (VNĐ)</label>
            <input type="number" name="expected_revenue" class="form-control" value="{{ old('expected_revenue', $lead->expected_revenue ?? 0) }}" min="0">
            <small class="text-muted">Ước tính cho pipeline Sales — không phải doanh thu thực.</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Sales phụ trách</label>
            @if(auth()->user()->isSales())
                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                <input type="hidden" name="assigned_sales_id" value="{{ auth()->id() }}">
                <small class="text-muted">Lead sẽ gắn cho bạn (Sales đang đăng nhập).</small>
            @else
                <select name="assigned_sales_id" class="form-control">
                    <option value="">-- Chọn Sales phụ trách --</option>
                    @foreach($salesUsers as $u)<option value="{{ $u->id }}" @selected(old('assigned_sales_id', $lead->assigned_sales_id ?? '')==$u->id)>{{ $u->name }}</option>@endforeach
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

    <div class="col-12"><hr class="mt-1 mb-3"><h6 class="font-weight-bold mb-2">Người liên quan / Phụ huynh</h6>
        <small class="text-muted d-block mb-3">Không bắt buộc — dùng khi cần liên hệ người thân hoặc phụ huynh.</small>
    </div>
    <div class="col-md-4">
        <div class="form-group"><label>Họ tên</label><input name="related_name" class="form-control" value="{{ old('related_name', $lead->related_name ?? '') }}" placeholder="Họ tên người liên hệ"></div>
    </div>
    <div class="col-md-4">
        <div class="form-group"><label>Số điện thoại</label><input name="related_phone" class="form-control" value="{{ old('related_phone', $lead->related_phone ?? '') }}" placeholder="0988xxxxxx"></div>
    </div>
    <div class="col-md-4">
        <div class="form-group"><label>Email</label><input type="email" name="related_email" class="form-control" value="{{ old('related_email', $lead->related_email ?? '') }}" placeholder="email@gmail.com"></div>
    </div>
</div>
