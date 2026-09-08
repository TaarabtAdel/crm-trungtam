@php $t = $template; @endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ $action }}" class="modal-content">
            @csrf
            @if(($method ?? 'POST') === 'PUT')
                @method('PUT')
            @endif
            <div class="modal-header">
                <h5 class="modal-title">{{ $t ? 'Sửa mẫu' : 'Thêm mẫu thông báo' }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Mã *</label>
                        <input name="code" class="form-control" value="{{ old('code', $t->code ?? '') }}" {{ $t && $t->code === 'payment_success' ? 'readonly' : '' }} required>
                    </div>
                    <div class="form-group col-md-8">
                        <label>Tiêu đề *</label>
                        <input name="title" class="form-control" value="{{ old('title', $t->title ?? '') }}" required>
                    </div>
                    <div class="form-group col-md-12">
                        <label>Tiêu đề Email</label>
                        <input name="email_subject" class="form-control" value="{{ old('email_subject', $t->email_subject ?? '') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label>Nội dung Email</label>
                        <textarea name="content_email" class="form-control" rows="6">{{ old('content_email', $t->content_email ?? '') }}</textarea>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Zalo Template ID (ZNS)</label>
                        <input name="zalo_template_id" class="form-control" value="{{ old('zalo_template_id', $t->zalo_template_id ?? '') }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label class="d-block">&nbsp;</label>
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="{{ $modalId }}_email" name="is_active_email" value="1" @checked(old('is_active_email', $t->is_active_email ?? true))>
                            <label class="custom-control-label" for="{{ $modalId }}_email">Bật Email</label>
                        </div>
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="{{ $modalId }}_zalo" name="is_active_zalo" value="1" @checked(old('is_active_zalo', $t->is_active_zalo ?? false))>
                            <label class="custom-control-label" for="{{ $modalId }}_zalo">Bật Zalo ZNS</label>
                        </div>
                    </div>
                    <div class="form-group col-md-12">
                        <label>Biến Zalo ZNS (JSON)</label>
                        @php
                            $zaloParamsPlaceholder = '{"customer_name":"'.'{{recipient_name}}'.'","student_name":"'.'{{student_name}}'.'","amount":"'.'{{amount_formatted}}'.'"}';
                            $zaloParamsExample = '{"customer_name":"'.'{{recipient_name}}'.'","date":"'.'{{session_date}}'.'"}';
                            $paramsJson = old('params_mapping_json', $t ? json_encode($t->params_mapping ?: new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '');
                        @endphp
                        <textarea name="params_mapping_json" class="form-control" rows="5" placeholder="{{ $zaloParamsPlaceholder }}">{{ $paramsJson }}</textarea>
                        <small class="text-muted">
                            Key = tên tham số trên mẫu ZNS đã duyệt · Value = <code>@{{ten_bien}}</code> giống Email (không cần map tên biến trung gian).
                            Ví dụ: <code>{{ $zaloParamsExample }}</code>
                        </small>
                    </div>
                    <div class="form-group col-md-12 mb-0">
                        <label>Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $t->notes ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                <button class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>
