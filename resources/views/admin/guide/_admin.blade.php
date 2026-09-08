<div class="guide-role-intro mb-3">
    <span class="badge badge-primary">Vai trò Admin / Super Admin</span>
    <p class="mb-0 mt-2 text-muted">Hệ thống &amp; quyền nâng cao. Hệ thống trống → làm tab <a href="{{ route('admin.guide', ['tab' => 'setup']) }}">Cài đặt ban đầu</a>. Nghiệp vụ ngày-ngày xem 3 tab vai trò kia.</p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-list-check"></i> Checklist triển khai lần đầu</h6>
    <p class="mb-2">Xem checklist <strong>tự tick theo dữ liệu</strong> tại tab
        <a href="{{ route('admin.guide', ['tab' => 'setup']) }}">Cài đặt ban đầu</a>
        (gạch ngang + dấu xanh khi xong).
    </p>
    <ol class="mb-0">
        <li>Tạo <a href="{{ route('admin.branches.index') }}"><strong>Chi nhánh</strong></a> (+ STK VietQR nếu thu CK).</li>
        <li><a href="{{ route('admin.settings.edit') }}"><strong>Cài đặt</strong></a> tên / logo; (tuỳ chọn) SMTP &amp; Zalo ZNS.</li>
        <li>Tạo <a href="{{ route('admin.users.index') }}"><strong>Người dùng</strong></a> — có thể gắn <em>nhiều role</em>.</li>
        <li>Kiểm tra <a href="{{ route('admin.permissions.edit') }}"><strong>Phân quyền</strong></a> +
            <a href="{{ route('admin.notification-templates.index') }}">Mẫu thông báo</a>.</li>
        <li>Đào tạo: môn → GV → lớp → TKB → HV → điểm danh → hoàn thành / nhật ký.</li>
        <li>Sale nhập lead; Kế toán thu phí; cấu hình <a href="{{ route('admin.commission-rules.index') }}">Quy tắc HH</a> nếu có.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-speedometer2"></i> 1. Bảng điều khiển</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
        · <a href="{{ route('admin.dashboard') }}">/admin</a>
    </p>
    <ul>
        <li>Tổng quan: số GV, HV, lớp, doanh thu, lead… theo chi nhánh header.</li>
        <li>Luôn chọn đúng chi nhánh (hoặc “Tất cả”) trước khi đọc số.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-building"></i> 2. Quản lý chi nhánh (+ QR thanh toán)</h6>
    <p class="guide-path">
        Hệ thống → <a href="{{ route('admin.branches.index') }}">Quản lý chi nhánh</a>
        · <a href="{{ route('admin.branches.index') }}">/admin/branches</a>
    </p>
    <ol>
        <li><em>Thêm chi nhánh</em> / sửa: Tên*, Mã, Địa chỉ, SĐT, tick <em>Đang hoạt động</em>.</li>
        <li>Phần <strong>Tài khoản nhận học phí (VietQR)</strong>:
            <ul>
                <li>Chọn <strong>Ngân hàng</strong> (danh sách BIN).</li>
                <li><strong>Số tài khoản</strong> (chỉ số).</li>
                <li><strong>Chủ tài khoản</strong> — khuyến nghị VIẾT HOA KHÔNG DẤU.</li>
            </ul>
        </li>
        <li>PDF hóa đơn của HĐ thuộc chi nhánh đó sẽ hiện QR (số tiền = còn nợ, nội dung = mã HĐ).</li>
        <li>Cột “Tài khoản NH” trên list: hiện STK hoặc “Chưa cấu hình”.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-person-gear"></i> 3. Quản lý người dùng (nhiều role)</h6>
    <p class="guide-path">
        Hệ thống → <a href="{{ route('admin.users.index') }}">Quản lý người dùng</a>
        · <a href="{{ route('admin.users.index') }}">/admin/users</a>
    </p>
    <ol>
        <li><em>Thêm người dùng</em>: Họ tên*, Email*, Mật khẩu.</li>
        <li><strong>Tick một hoặc nhiều vai trò</strong> — quyền thực tế = hợp quyền các role (Super Admin / Admin / Kế toán / Sales / Đào Tạo / Giáo viên).</li>
        <li>Gắn Chi nhánh, SĐT; tắt Active = khóa đăng nhập.</li>
        <li>GV: email user nên trùng email hồ sơ Giáo viên để nhận nhắc nhật ký.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-shield-lock"></i> 4. Phân quyền</h6>
    <p class="guide-path">
        Hệ thống → <a href="{{ route('admin.permissions.edit') }}">Phân quyền</a>
        · <a href="{{ route('admin.permissions.edit') }}">/admin/permissions</a>
    </p>
    <ol>
        <li>Ma trận: hàng = quyền, cột = vai trò.</li>
        <li>Tick / bỏ tick; có <em>Chọn tất cả</em> theo cột → <em>Lưu phân quyền</em>.</li>
        <li>Super Admin luôn full quyền. Menu sidebar ẩn theo quyền đã lưu.</li>
        <li>Chỉ chỉnh khi quy trình trung tâm khác bộ mặc định.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-gear"></i> 5. Cài đặt (SMTP / Zalo)</h6>
    <p class="guide-path">
        Hệ thống → <a href="{{ route('admin.settings.edit') }}">Cài đặt</a>
        · <a href="{{ route('admin.settings.edit') }}">/admin/settings</a>
    </p>
    <ul>
        <li>Thương hiệu: tên trung tâm / logo text (sidebar, PDF).</li>
        <li><strong>SMTP</strong>: bật + host/port/user/App password → Gửi email thử. Dùng cho nhắc nợ / xác nhận thanh toán.</li>
        <li><strong>Zalo ZNS</strong>: App ID, Secret, Access/Refresh Token; bật kênh + loại “Thanh toán học phí thành công”.</li>
        <li>Link hướng dẫn chính thức Gmail SMTP / Zalo OA nằm trên trang Cài đặt.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-envelope-paper"></i> 5b. Mẫu thông báo</h6>
    <p class="guide-path">
        Hệ thống → <a href="{{ route('admin.notification-templates.index') }}">Mẫu thông báo</a>
    </p>
    <ul>
        <li>Mẫu dùng chung Email + Zalo (placeholders dạng <code>@{{ten_bien}}</code>).</li>
        <li><code>payment_success</code>: gửi HV + PH khi ghi nhận thanh toán (nếu bật kênh + có liên hệ).</li>
        <li>Zalo: điền Template ID ZNS + JSON biến (<code>{{ '{'.'{ten_bien}'.'}' }}</code> giống Email).</li>
        <li>Xem lịch sử gửi ngay trên trang mẫu.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-sliders"></i> 6. Quy tắc hoa hồng</h6>
    <p class="guide-path">
        Tài chính → <a href="{{ route('admin.commission-rules.index') }}">Quy tắc HH</a>
        · <a href="{{ route('admin.commission-rules.index') }}">/admin/commission-rules</a>
    </p>
    <ol>
        <li><em>Thêm quy tắc</em>: Phạm vi <em>Toàn hệ thống (Global)</em> hoặc <em>Theo lớp</em> (+ chọn lớp), <strong>% *</strong>, Tier doanh thu tối thiểu (nếu dùng bậc).</li>
        <li>Sửa inline: %, Tier, Active → <em>Lưu</em>; có thể xóa.</li>
        <li>Khi tính HH: ưu tiên rule theo lớp hơn global; lấy tier cao nhất đạt được; rule Inactive không áp cho HĐ mới.</li>
        <li>HH = số tiền HĐ × %. Chỉ khi HĐ <em>Đã thu</em> và có Sales.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-wallet2"></i> 7. Duyệt chi phí &amp; chi ngay</h6>
    <p class="guide-path">
        <a href="{{ route('admin.expenses.index') }}">Chi phí</a>
        · <a href="{{ route('admin.finance.teacher-payroll') }}">Lương GV</a>
    </p>
    <ul>
        <li>Trên phiếu <em>Chờ duyệt</em>: bấm <em>Duyệt</em> hoặc <em>Từ chối</em> (cần quyền approve).</li>
        <li>Sau duyệt, Kế toán bấm <em>Đã chi</em> khi đã chuyển tiền.</li>
        <li>Quyền <em>Chi ngay / pay_immediate</em>: khi chi lương có thể tick bỏ qua chờ duyệt, ghi nhận dòng tiền luôn.</li>
        <li>Người đề xuất nhận thông báo khi phiếu được duyệt / từ chối / đã thanh toán.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-bar-chart"></i> 8. Báo cáo tổng hợp</h6>
    <p class="guide-path">
        Hệ thống → <a href="{{ route('admin.reports.index') }}">Báo cáo</a>
        · <a href="{{ route('admin.reports.index') }}">/admin/reports</a>
    </p>
    <ul>
        <li>Snapshot nhanh: Leads, Đã chốt, tỷ lệ, DT dự kiến, đã thu / chưa thu, HV, lớp, GV.</li>
        <li>Báo cáo tiền chi tiết hơn nằm ở <a href="{{ route('admin.finance.reports') }}">Báo cáo TC</a> (xuất Excel/PDF).</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-bell"></i> 9. Thông báo &amp; nhắc việc</h6>
    <p class="guide-path">
        Chuông header · <a href="{{ route('admin.notifications.index') }}">Thông báo</a>
        · <a href="{{ route('admin.notifications.index') }}">/admin/notifications</a>
    </p>
    <ul>
        <li>Loại thường gặp: phân lead; lead follow-up; buổi chưa cập nhật trạng thái; <strong>nhắc ghi nhật ký</strong>; đề xuất chi / duyệt; nhắc nợ HĐ.</li>
        <li><em>Đánh dấu đã đọc</em> từng dòng hoặc <em>Đánh dấu tất cả đã đọc</em>.</li>
        <li>Nhắc nền chạy khi có người đăng nhập admin (AJAX tick) hoặc qua scheduler server.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-database-add"></i> 10. Khởi tạo data demo</h6>
    <p class="guide-path">
        Hệ thống → <a href="{{ route('admin.demo-data.index') }}">Khởi tạo data demo</a>
        · <a href="{{ route('admin.demo-data.index') }}">/admin/demo-data</a>
    </p>
    <ul>
        <li>Tạo bộ mẫu: chi nhánh, sales, GV, môn, lớp, HV, buổi+điểm danh, lead, HĐ…</li>
        <li><strong>Cảnh báo:</strong> thao tác xóa / ghi đè dữ liệu nghiệp vụ demo — <em>không chạy trên production</em> đã có data thật.</li>
        <li>Bấm <em>Chạy khởi tạo data demo</em> chỉ trên máy thử.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-people"></i> 11. Bao quát các tab khác</h6>
    <ul>
        <li>
            <a href="{{ route('admin.guide', ['tab' => 'setup']) }}">Cài đặt ban đầu</a> ·
            <a href="{{ route('admin.guide', ['tab' => 'flow']) }}">Quy trình</a> ·
            <a href="{{ route('admin.guide', ['tab' => 'accountant']) }}">Kế toán</a> ·
            <a href="{{ route('admin.guide', ['tab' => 'training']) }}">Đào tạo</a> ·
            <a href="{{ route('admin.guide', ['tab' => 'sales']) }}">Sale</a>.
        </li>
        <li>Khi onboard nhân viên: gửi đúng link tab vai trò + tạo user với role tương ứng.</li>
    </ul>
</div>

<div class="guide-tip">
    <strong>Hay thiếu khi go-live:</strong> chưa STK chi nhánh (PDF không QR) · chưa SMTP/ZNS khi muốn gửi TT học phí · buổi chưa điểm danh đủ không lưu được Hoàn thành · email GV ≠ user (không nhận nhắc nhật ký) · chưa gắn Sales trên HĐ (không ra HH).
</div>
