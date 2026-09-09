@php
    /** @var array $setupChecklist */
    $checklist = $setupChecklist ?? ['items' => [], 'percent' => 0, 'required_done' => 0, 'required_total' => 0, 'done' => 0, 'total' => 0];
@endphp

<div class="guide-role-intro mb-3">
    <span class="badge badge-dark">Cài đặt ban đầu</span>
    <p class="mb-0 mt-2 text-muted">Dành cho lần đầu cấp tài khoản Admin khi hệ thống còn trống. Checklist bên dưới tự tick theo dữ liệu thực tế — xong thì gạch ngang + dấu xanh.</p>
</div>

@include('partials.setup_checklist', ['checklist' => $checklist, 'showSuccess' => true])

<div class="guide-tip mb-3">
    <strong>Ai làm?</strong> Super Admin hoặc Admin. Các bước gắn link — bấm để mở đúng trang (cần đăng nhập bằng tài khoản có quyền).
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-1-circle"></i> Bước 1 — Đặt tên trung tâm</h6>
    <p class="guide-path">
        <a href="{{ route('admin.settings.edit') }}">Cài đặt</a>
        · hiện trên sidebar &amp; PDF hóa đơn
    </p>
    <ol>
        <li>Vào <a href="{{ route('admin.settings.edit') }}">/admin/settings</a>.</li>
        <li>Nhập <strong>Tên trung tâm</strong> / <strong>Logo text</strong> (vd: <code>TPT Academy</code>).</li>
        <li>Bấm <em>Lưu thay đổi</em>, tải lại trang để thấy sidebar đổi tên.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-2-circle"></i> Bước 2 — Tạo chi nhánh + STK nhận học phí</h6>
    <p class="guide-path">
        <a href="{{ route('admin.branches.index') }}">Quản lý chi nhánh</a>
    </p>
    <ol>
        <li>Vào <a href="{{ route('admin.branches.index') }}">/admin/branches</a> → <em>Thêm chi nhánh</em>.</li>
        <li>Điền: <strong>Tên *</strong>, Mã, Địa chỉ, SĐT.</li>
        <li>Phần <strong>Tài khoản nhận học phí (VietQR)</strong>: Ngân hàng + Số TK + Chủ TK (HOA, không dấu).</li>
        <li>Tick <em>Đang hoạt động</em> → Lưu. Chọn chi nhánh ở dropdown góc trên khi làm việc.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-3-circle"></i> Bước 3 — Tạo tài khoản nhân sự (nhiều role)</h6>
    <p class="guide-path">
        <a href="{{ route('admin.users.index') }}">Quản lý người dùng</a>
    </p>
    <ol>
        <li>Vào <a href="{{ route('admin.users.index') }}">/admin/users</a> → <em>Thêm người dùng</em>.</li>
        <li>Họ tên*, Email*, Mật khẩu; <strong>tick một hoặc nhiều vai trò</strong> (quyền = hợp các role).</li>
        <li>Gợi ý: Sales, Đào Tạo, Kế toán, Giáo viên (tick tạo login trên hồ sơ GV; email trùng để nhật ký + <em>Bảng lương của tôi</em>).</li>
        <li>Gắn chi nhánh nếu cần giới hạn phạm vi.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-4-circle"></i> Bước 4 — Kiểm tra phân quyền</h6>
    <p class="guide-path">
        <a href="{{ route('admin.permissions.edit') }}">Phân quyền</a>
    </p>
    <ol>
        <li>Vào <a href="{{ route('admin.permissions.edit') }}">/admin/permissions</a>.</li>
        <li>Ma trận quyền theo role (gồm nhật ký lớp, mẫu thông báo, backup…). Chỉ sửa khi cần.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-5-circle"></i> Bước 5 — SMTP &amp; Zalo ZNS (tuỳ chọn)</h6>
    <p class="guide-path">
        <a href="{{ route('admin.settings.edit') }}">Cài đặt</a> ·
        <a href="{{ route('admin.notification-templates.index') }}">Mẫu thông báo</a>
    </p>
    <ol>
        <li><strong>SMTP (Gmail…)</strong>: bật SMTP, host <code>smtp.gmail.com</code>, port 587/TLS, App password → gửi thử.</li>
        <li><strong>Zalo ZNS</strong>: App ID / Secret / Access Token / Refresh Token; bật kênh + “Thanh toán học phí thành công”.</li>
        <li>Tại <a href="{{ route('admin.notification-templates.index') }}">Mẫu thông báo</a>: mẫu <code>payment_success</code> / <code>session_reminder</code>; Zalo Template ID + JSON biến dạng <code>{{ '{'.'{ten_bien}'.'}' }}</code> (giống Email).</li>
        <li>Khi ghi nhận thanh toán → hệ thống gửi HV + PH (nếu có SĐT/Email) theo mẫu.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-6-circle"></i> Bước 6 — (Tuỳ chọn) Quy tắc hoa hồng</h6>
    <p class="guide-path">
        <a href="{{ route('admin.commission-rules.index') }}">Quy tắc HH</a>
    </p>
    <ol>
        <li>Nếu Sale nhận HH khi HV đóng đủ học phí: thêm quy tắc Global hoặc theo lớp.</li>
        <li>Không tạo rule → không phát sinh dòng hoa hồng.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-7-circle"></i> Bước 7 — Dữ liệu nghiệp vụ nền (Đào tạo)</h6>
    <p class="guide-path">Làm khi đã có chi nhánh</p>
    <ol>
        <li><a href="{{ route('admin.subjects.index') }}">Môn học</a> → <a href="{{ route('admin.teachers.index') }}">Giáo viên</a> → <a href="{{ route('admin.classes.index') }}">Lớp học</a>.</li>
        <li>Vào lớp → tab <strong>Thời khóa biểu</strong> → tạo TKB.</li>
        <li><a href="{{ route('admin.students.index') }}">Học sinh</a> → gắn vào lớp.</li>
        <li>Vận hành: <strong>điểm danh đủ</strong> → mới được đánh dấu buổi <em>Hoàn thành</em> → hệ thống tạo <strong>Nhật ký</strong> + nhắc GV điền nội dung.</li>
    </ol>
    <p class="mb-0 text-muted small">Chi tiết: tab <a href="{{ route('admin.guide', ['tab' => 'training']) }}">Đào tạo</a> · <a href="{{ route('admin.guide', ['tab' => 'flow']) }}">Quy trình</a>.</p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-8-circle"></i> Bước 8 — (Tuỳ chọn) Data demo / Backup</h6>
    <p class="guide-path">
        <a href="{{ route('admin.demo-data.index') }}">Data demo</a> ·
        <a href="{{ route('admin.backups.index') }}">Backup</a>
    </p>
    <ul>
        <li>Demo chỉ trên máy thử — không chạy production có data thật.</li>
        <li>Backup SQL tại <a href="{{ route('admin.backups.index') }}">/admin/backups</a> khi đã vận hành.</li>
    </ul>
</div>

<div class="guide-tip">
    <strong>Xong checklist bắt buộc?</strong> Chuyển sang tab <a href="{{ route('admin.guide', ['tab' => 'flow']) }}">Quy trình</a> để chạy thử 1 vòng: lead → lớp → điểm danh → hoàn thành → thu học phí.
</div>
