<p class="small text-muted mb-3">Thêm chi nhánh mới. Có thể điền STK VietQR ngay (tuỳ chọn).</p>
<div class="form-row">
    <div class="form-group col-md-8">
        <label>Tên chi nhánh <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="form-group col-md-4">
        <label>Mã</label>
        <input name="code" class="form-control" value="{{ old('code') }}" placeholder="VD: MAIN">
    </div>
    <div class="form-group col-md-8">
        <label>Địa chỉ</label>
        <input name="address" class="form-control" value="{{ old('address') }}">
    </div>
    <div class="form-group col-md-4">
        <label>SĐT</label>
        <input name="phone" class="form-control" value="{{ old('phone') }}">
    </div>
</div>
<hr>
<div class="font-weight-bold small text-uppercase text-muted mb-2">Tài khoản nhận học phí (VietQR) — tuỳ chọn</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label>Ngân hàng</label>
        <select name="bank_bin" class="form-control">
            <option value="">-- Chọn --</option>
            @foreach($banks as $bin => $label)
                <option value="{{ $bin }}" @selected(old('bank_bin') == $bin)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6">
        <label>Số tài khoản</label>
        <input name="bank_account_number" class="form-control" value="{{ old('bank_account_number') }}">
    </div>
    <div class="form-group col-md-12 mb-0">
        <label>Chủ tài khoản</label>
        <input name="bank_account_name" class="form-control" value="{{ old('bank_account_name') }}" placeholder="VIẾT HOA KHÔNG DẤU">
    </div>
</div>
@if($branches->isNotEmpty())
    <p class="small text-success mt-2 mb-0"><i class="bi bi-check-circle"></i> Đã có {{ $branches->count() }} chi nhánh Active — có thể bỏ qua bằng “Chỉ chuyển bước”.</p>
@endif
