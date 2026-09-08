@php $b = $branch ?? null; @endphp
<div class="form-row">
    <div class="form-group col-md-8">
        <label>Tên *</label>
        <input name="name" class="form-control" value="{{ old('name', $b->name ?? '') }}" required>
    </div>
    <div class="form-group col-md-4">
        <label>Mã</label>
        <input name="code" class="form-control" value="{{ old('code', $b->code ?? '') }}">
    </div>
    <div class="form-group col-md-8">
        <label>Địa chỉ</label>
        <input name="address" class="form-control" value="{{ old('address', $b->address ?? '') }}">
    </div>
    <div class="form-group col-md-4">
        <label>SĐT</label>
        <input name="phone" class="form-control" value="{{ old('phone', $b->phone ?? '') }}">
    </div>
</div>

<hr class="my-2">
<div class="small text-muted mb-2 font-weight-bold text-uppercase">Tài khoản nhận học phí (VietQR)</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label>Ngân hàng</label>
        <select name="bank_bin" class="form-control">
            <option value="">-- Chọn ngân hàng --</option>
            @foreach($banks as $bin => $label)
                <option value="{{ $bin }}" @selected(old('bank_bin', $b->bank_bin ?? '') == $bin)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6">
        <label>Số tài khoản</label>
        <input name="bank_account_number" class="form-control" value="{{ old('bank_account_number', $b->bank_account_number ?? '') }}" placeholder="Chỉ nhập số">
    </div>
    <div class="form-group col-md-12">
        <label>Chủ tài khoản</label>
        <input name="bank_account_name" class="form-control" value="{{ old('bank_account_name', $b->bank_account_name ?? '') }}" placeholder="VIẾT HOA KHÔNG DẤU (khuyến nghị)">
        <small class="text-muted">Dùng cho QR chuyển khoản trên PDF hóa đơn.</small>
    </div>
</div>

@if($b)
<div class="form-check mt-1">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="active{{ $b->id }}" {{ old('is_active', $b->is_active) ? 'checked' : '' }}>
    <label class="form-check-label" for="active{{ $b->id }}">Đang hoạt động</label>
</div>
@endif
