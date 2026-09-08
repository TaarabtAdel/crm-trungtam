@extends('layouts.admin')

@section('title', 'Cài đặt')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'SMTP (Gmail)',
            'body' => '<p class="mb-2">Bật SMTP, điền host/port/user/pass (Gmail: <code>smtp.gmail.com</code>, cổng 587 + TLS hoặc 465 + SSL). Lưu rồi bấm <em>Gửi email thử</em>.</p>'
                .'<p class="mb-0">Hướng dẫn chính thức: '
                .'<a href="https://support.google.com/mail/answer/7126229" target="_blank" rel="noopener">Cài đặt SMTP Gmail</a>'
                .' · '
                .'<a href="https://support.google.com/accounts/answer/185833" target="_blank" rel="noopener">Tạo App password</a>'
                .'.</p>',
        ],
        [
            'title' => 'Zalo OA & ZNS',
            'body' => '<p class="mb-2">Cấu hình App ID / Secret / Access Token / Refresh Token tại đây. Mẫu tin &amp; bật kênh gửi nằm ở <em>Mẫu thông báo</em>. Khi ghi nhận thanh toán, CRM gửi Email + ZNS cho HV &amp; PH (nếu có liên hệ).</p>'
                .'<p class="mb-0">Hướng dẫn chính thức: '
                .'<a href="https://developers.zalo.me/docs/official-account" target="_blank" rel="noopener">Zalo Official Account</a>'
                .' · '
                .'<a href="https://docs.zaloplatforms.com/docs/OA/bat-dau/xac-thuc-va-uy-quyen-cho-ung-dung-new" target="_blank" rel="noopener">Lấy Access Token</a>'
                .' · '
                .'<a href="https://developers.zalo.me/docs/api/zalo-notification-service-api" target="_blank" rel="noopener">ZNS API</a>'
                .'.</p>',
        ],
        [
            'title' => 'Thương hiệu / liên hệ',
            'body' => '<p class="mb-0">Tên &amp; logo text hiện sidebar/login. Email/SĐT/địa chỉ là thông tin liên hệ chính thức của trung tâm.</p>',
        ],
    ];
    $s = fn (string $key) => old($key, $settings[$key] ?? '');
    $checked = fn (string $key) => old($key, $settings[$key] ?? '0') == '1';
@endphp

<div class="settings-page">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h4 class="mb-1 font-weight-bold">Cài đặt hệ thống</h4>
            <p class="text-muted mb-0 small">Thương hiệu, liên hệ, SMTP gửi email và Zalo OA.</p>
        </div>
        <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalSettingsHelp">
            <i class="bi bi-question-circle"></i> Hướng dẫn
        </button>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" id="settingsMainForm">
        @csrf @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="page-card mb-3">
                    <div class="card-header-custom">
                        <div>
                            <h6 class="mb-0 font-weight-bold"><i class="bi bi-building mr-1"></i> Thương hiệu</h6>
                            <small class="text-muted">Sidebar, trang đăng nhập, tài liệu</small>
                        </div>
                    </div>
                    <div class="card-body-custom">
                        <div class="form-row">
                            <div class="form-group col-md-7">
                                <label>Tên trung tâm <span class="text-danger">*</span></label>
                                <input name="center_name" class="form-control" value="{{ $s('center_name') }}" required>
                            </div>
                            <div class="form-group col-md-5">
                                <label>Logo text (sidebar) <span class="text-danger">*</span></label>
                                <input name="logo_text" class="form-control" value="{{ $s('logo_text') }}" maxlength="50" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-card mb-3">
                    <div class="card-header-custom">
                        <div>
                            <h6 class="mb-0 font-weight-bold"><i class="bi bi-telephone mr-1"></i> Thông tin liên hệ</h6>
                            <small class="text-muted">Email / điện thoại / địa chỉ chính thức</small>
                        </div>
                    </div>
                    <div class="card-body-custom">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Email liên hệ</label>
                                <input type="email" name="center_email" class="form-control" value="{{ $s('center_email') }}" placeholder="lienhe@trungtam.vn">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Số điện thoại</label>
                                <input name="center_phone" class="form-control" value="{{ $s('center_phone') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Hotline</label>
                                <input name="center_hotline" class="form-control" value="{{ $s('center_hotline') }}">
                            </div>
                            <div class="form-group col-md-12">
                                <label>Địa chỉ</label>
                                <input name="center_address" class="form-control" value="{{ $s('center_address') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Website</label>
                                <input name="center_website" class="form-control" value="{{ $s('center_website') }}" placeholder="https://...">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Fanpage Facebook</label>
                                <input name="center_fanpage" class="form-control" value="{{ $s('center_fanpage') }}" placeholder="https://facebook.com/...">
                            </div>
                            <div class="form-group col-md-12 mb-0">
                                <label>Ghi chú liên hệ</label>
                                <textarea name="contact_note" class="form-control" rows="2">{{ $s('contact_note') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-card mb-3">
                    <div class="card-header-custom d-flex justify-content-between align-items-start flex-wrap" style="gap:.5rem">
                        <div>
                            <h6 class="mb-0 font-weight-bold"><i class="bi bi-envelope-at mr-1"></i> Gửi email (SMTP)</h6>
                            <small class="text-muted">Dùng cho nhắc nợ / email hệ thống thay vì cấu hình trong file .env</small>
                        </div>
                        <a class="btn btn-sm btn-outline-secondary" href="https://support.google.com/mail/answer/7126229" target="_blank" rel="noopener" title="Hướng dẫn SMTP Gmail (Google)">
                            <i class="bi bi-box-arrow-up-right"></i> Gmail SMTP
                        </a>
                    </div>
                    <div class="card-body-custom">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="smtp_enabled" name="smtp_enabled" value="1" @checked($checked('smtp_enabled'))>
                            <label class="custom-control-label font-weight-bold" for="smtp_enabled">Sử dụng SMTP từ trang này</label>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>SMTP Host</label>
                                <input name="smtp_host" class="form-control" value="{{ $s('smtp_host') }}" placeholder="smtp.gmail.com">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Port</label>
                                <input type="number" name="smtp_port" class="form-control" value="{{ $s('smtp_port') ?: '587' }}" min="1" max="65535">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Mã hóa</label>
                                <select name="smtp_encryption" class="form-control">
                                    <option value="tls" @selected($s('smtp_encryption') === 'tls')>TLS (587)</option>
                                    <option value="ssl" @selected($s('smtp_encryption') === 'ssl')>SSL (465)</option>
                                    <option value="none" @selected($s('smtp_encryption') === 'none')>Không</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Username</label>
                                <input name="smtp_username" class="form-control" value="{{ $s('smtp_username') }}" autocomplete="off">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Password</label>
                                <input type="password" name="smtp_password" class="form-control" value="" autocomplete="new-password" placeholder="{{ !empty($settings['smtp_password_set']) ? '•••••••• (để trống nếu giữ nguyên)' : 'Mật khẩu SMTP / App password' }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>From email</label>
                                <input type="email" name="smtp_from_address" class="form-control" value="{{ $s('smtp_from_address') }}" placeholder="Mặc định = Email liên hệ">
                            </div>
                            <div class="form-group col-md-6">
                                <label>From name</label>
                                <input name="smtp_from_name" class="form-control" value="{{ $s('smtp_from_name') }}" placeholder="Mặc định = Tên trung tâm">
                            </div>
                        </div>
                        <p class="small text-muted mb-0">
                            Gợi ý Gmail: bật xác minh 2 bước → tạo
                            <a href="https://support.google.com/accounts/answer/185833" target="_blank" rel="noopener">App password</a>
                            → Host <code>smtp.gmail.com</code>, Port <code>587</code>, TLS.
                            ·
                            <a href="https://support.google.com/mail/answer/7126229" target="_blank" rel="noopener">Hướng dẫn SMTP Gmail</a>
                        </p>
                    </div>
                </div>

                <div class="page-card mb-3">
                    <div class="card-header-custom d-flex justify-content-between align-items-start flex-wrap" style="gap:.5rem">
                        <div>
                            <h6 class="mb-0 font-weight-bold"><i class="bi bi-chat-dots mr-1"></i> Zalo Official Account</h6>
                            <small class="text-muted">Kênh OA &amp; tùy chọn thông báo học viên</small>
                        </div>
                        <div class="d-flex flex-wrap" style="gap:.35rem">
                            <a class="btn btn-sm btn-outline-secondary" href="https://developers.zalo.me/docs/official-account" target="_blank" rel="noopener" title="Tài liệu Zalo Official Account">
                                <i class="bi bi-box-arrow-up-right"></i> Zalo OA
                            </a>
                            <a class="btn btn-sm btn-outline-secondary" href="https://developers.zalo.me/docs/api/zalo-notification-service-api" target="_blank" rel="noopener" title="Tài liệu Zalo ZNS API">
                                <i class="bi bi-box-arrow-up-right"></i> ZNS API
                            </a>
                        </div>
                    </div>
                    <div class="card-body-custom">
                        <div class="alert alert-warning border small mb-3">
                            <strong>Zalo ZNS:</strong> cấu hình App ID / Secret / Access Token / Refresh Token bên dưới.
                            Mẫu tin &amp; bật kênh gửi nằm ở
                            <a href="{{ route('admin.notification-templates.index') }}">Mẫu thông báo</a>.
                            Khi ghi nhận thanh toán, hệ thống gửi cho HV + PH (nếu có SĐT/Email).
                            ·
                            <a href="https://docs.zaloplatforms.com/docs/OA/bat-dau/xac-thuc-va-uy-quyen-cho-ung-dung-new" target="_blank" rel="noopener">Lấy Access Token (chính thức)</a>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Tên Zalo OA</label>
                                <input name="zalo_oa_name" class="form-control" value="{{ $s('zalo_oa_name') }}" placeholder="TPT Academy OA">
                            </div>
                            <div class="form-group col-md-6">
                                <label>OA ID / App ID (hiển thị)</label>
                                <input name="zalo_oa_id" class="form-control" value="{{ $s('zalo_oa_id') }}" placeholder="OA ID">
                            </div>
                            <div class="form-group col-md-12">
                                <label>Link chat Zalo OA</label>
                                <input name="zalo_oa_link" class="form-control" value="{{ $s('zalo_oa_link') }}" placeholder="https://zalo.me/...">
                            </div>
                            <div class="form-group col-md-12">
                                <label>Access Token OA (cũ / dự phòng)</label>
                                <input type="password" name="zalo_oa_access_token" class="form-control" value="" autocomplete="new-password" placeholder="{{ !empty($settings['zalo_oa_access_token_set']) ? '•••••••• (để trống nếu giữ nguyên)' : 'Token OA' }}">
                            </div>
                        </div>

                        <hr>
                        <div class="font-weight-bold mb-2">Zalo ZNS API</div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>ZNS App ID</label>
                                <input name="zalo_zns_app_id" class="form-control" value="{{ $s('zalo_zns_app_id') }}" placeholder="App ID Zalo">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Secret Key</label>
                                <input type="password" name="zalo_zns_secret_key" class="form-control" value="" autocomplete="new-password" placeholder="{{ !empty($settings['zalo_zns_secret_set']) ? '•••••••• (giữ nguyên nếu trống)' : 'Secret Key' }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Access Token (ZNS)</label>
                                <input type="password" name="zalo_zns_access_token" class="form-control" value="" autocomplete="new-password" placeholder="{{ !empty($settings['zalo_zns_access_token_set']) ? '•••••••• (giữ nguyên nếu trống)' : 'Access Token' }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Refresh Token</label>
                                <input type="password" name="zalo_zns_refresh_token" class="form-control" value="" autocomplete="new-password" placeholder="{{ !empty($settings['zalo_zns_refresh_token_set']) ? '•••••••• (giữ nguyên nếu trống)' : 'Refresh Token' }}">
                            </div>
                            @if(!empty($settings['zalo_zns_token_expires_at']))
                            <div class="form-group col-md-12">
                                <small class="text-muted">Token hết hạn (ước tính): {{ $settings['zalo_zns_token_expires_at'] }}</small>
                            </div>
                            @endif
                        </div>

                        <hr>
                        <div class="font-weight-bold mb-2">Loại thông báo dự kiến</div>
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="zalo_notify_enabled" name="zalo_notify_enabled" value="1" @checked($checked('zalo_notify_enabled'))>
                            <label class="custom-control-label" for="zalo_notify_enabled">Bật kênh thông báo Zalo</label>
                        </div>
                        <div class="pl-1 mb-2">
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input" id="zalo_notify_payment" name="zalo_notify_payment" value="1" @checked($checked('zalo_notify_payment'))>
                                <label class="custom-control-label" for="zalo_notify_payment">Thanh toán học phí thành công</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input" id="zalo_notify_debt" name="zalo_notify_debt" value="1" @checked($checked('zalo_notify_debt'))>
                                <label class="custom-control-label" for="zalo_notify_debt">Nhắc học phí / công nợ</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input" id="zalo_notify_schedule" name="zalo_notify_schedule" value="1" @checked($checked('zalo_notify_schedule'))>
                                <label class="custom-control-label" for="zalo_notify_schedule">Nhắc lịch học (Zalo trước buổi 2 tiếng)</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="zalo_notify_attendance" name="zalo_notify_attendance" value="1" @checked($checked('zalo_notify_attendance'))>
                                <label class="custom-control-label" for="zalo_notify_attendance">Thông báo điểm danh (vắng / muộn)</label>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label>Ghi chú nội bộ</label>
                            <textarea name="zalo_oa_note" class="form-control" rows="2" placeholder="VD: OA chăm sóc PH; template ZNS đã duyệt...">{{ $s('zalo_oa_note') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center flex-wrap" style="gap:.75rem">
                    <button class="btn btn-primary px-4" type="submit"><i class="bi bi-check2 mr-1"></i> Lưu thay đổi</button>
                    <span class="text-muted small">SMTP áp dụng ngay sau khi lưu (nếu đã bật).</span>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="page-card mb-3 settings-preview sticky-top" style="top:1rem">
                    <div class="card-header-custom">
                        <h6 class="mb-0 font-weight-bold">Trạng thái</h6>
                    </div>
                    <div class="card-body-custom">
                        <div class="settings-preview-brand mb-3">
                            <div class="settings-preview-logo">{{ $s('logo_text') ?: 'Logo' }}</div>
                            <div class="font-weight-bold">{{ $s('center_name') ?: 'Tên trung tâm' }}</div>
                        </div>

                        <div class="small mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>SMTP</span>
                                @if($checked('smtp_enabled') && $s('smtp_host'))
                                    <span class="badge badge-success">Đang bật</span>
                                @else
                                    <span class="badge badge-secondary">Tắt / dùng .env</span>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Zalo OA</span>
                                @if($s('zalo_oa_id') || $s('zalo_oa_link'))
                                    <span class="badge badge-info">Đã lưu OA</span>
                                @else
                                    <span class="badge badge-secondary">Chưa cấu hình</span>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Zalo ZNS</span>
                                @if(!empty($settings['zalo_zns_access_token_set']) && ($s('zalo_zns_app_id') || $s('zalo_oa_id')))
                                    <span class="badge badge-success">Đã cấu hình</span>
                                @else
                                    <span class="badge badge-secondary">Thiếu token/App ID</span>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>TT học phí → HV/PH</span>
                                @if($checked('zalo_notify_enabled') && $checked('zalo_notify_payment'))
                                    <span class="badge badge-info">Bật</span>
                                @else
                                    <span class="badge badge-secondary">Tắt</span>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Nhắc lịch học → HV/PH</span>
                                @if($checked('zalo_notify_enabled') && $checked('zalo_notify_schedule'))
                                    <span class="badge badge-info">Bật</span>
                                @else
                                    <span class="badge badge-secondary">Tắt</span>
                                @endif
                            </div>
                            <p class="text-muted mb-0 mt-2" style="font-size:.75rem">
                                Học phí: mẫu <code>payment_success</code>.
                                Lịch học: mẫu <code>session_reminder</code> (trước buổi ~2 tiếng).
                            </p>
                        </div>

                        <ul class="list-unstyled small mb-3 settings-preview-list">
                            @if($s('center_email'))
                                <li><i class="bi bi-envelope"></i> {{ $s('center_email') }}</li>
                            @endif
                            @if($s('center_phone'))
                                <li><i class="bi bi-telephone"></i> {{ $s('center_phone') }}</li>
                            @endif
                            @if($s('zalo_oa_name') || $s('zalo_oa_link'))
                                <li><i class="bi bi-chat-dots"></i> {{ $s('zalo_oa_name') ?: 'Zalo OA' }}</li>
                            @endif
                        </ul>

                        <div class="border-top pt-3">
                            <div class="font-weight-bold small mb-2">Gửi email thử (SMTP)</div>
                            <div class="form-group mb-2">
                                <input type="email" name="test_email" form="settingsTestMailForm" class="form-control form-control-sm" value="{{ old('test_email', $s('center_email')) }}" placeholder="email@example.com" required>
                            </div>
                            <button type="submit" form="settingsTestMailForm" class="btn btn-sm btn-outline-primary btn-block">
                                <i class="bi bi-send"></i> Gửi email thử
                            </button>
                            <p class="text-muted mb-0 mt-2" style="font-size:.75rem">Lưu SMTP trước, rồi gửi thử.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form id="settingsTestMailForm" method="POST" action="{{ route('admin.settings.test-mail') }}" class="d-none">
        @csrf
    </form>
</div>

@include('partials.page_help', [
    'modalId' => 'modalSettingsHelp',
    'title' => 'Hướng dẫn — Cài đặt',
    'items' => $helpItems,
])
@endsection
