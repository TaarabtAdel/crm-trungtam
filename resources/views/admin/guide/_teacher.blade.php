<div class="guide-role-intro mb-3">
    <span class="badge badge-success">Vai trò Giáo viên</span>
    <p class="mb-0 mt-2 text-muted">
        Ghi nhật ký buổi dạy, xem lớp mình phụ trách, điểm danh (nếu được giao), và xem
        <a href="{{ route('admin.my-payroll') }}">Bảng lương của tôi</a>.
        Tài khoản phải <strong>cùng email</strong> với hồ sơ Giáo viên.
    </p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-key"></i> Trước khi bắt đầu</h6>
    <ul>
        <li>Admin / Đào tạo tạo hồ sơ tại <em>Giáo viên</em> và tick <em>Tạo tài khoản đăng nhập</em> (role Giáo viên), <strong>hoặc</strong> tạo user role Giáo viên với email trùng hồ sơ.</li>
        <li>Đăng nhập → kiểm tra chuông thông báo và mục <a href="{{ route('admin.my-payroll') }}">Bảng lương của tôi</a>.</li>
        <li>Nếu vừa có role Giáo viên vừa role khác (Đào tạo, Admin…): xem thêm tab hướng dẫn tương ứng; lương cá nhân có <strong>2 tab</strong> GV / NV.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-list"></i> Menu bạn thường thấy</h6>
    <ul>
        <li><a href="{{ route('admin.classes.index') }}">Lớp học</a> — chỉ lớp mình phụ trách / có buổi dạy.</li>
        <li><a href="{{ route('admin.attendances.index') }}">Điểm danh</a> — điểm danh học viên (theo quyền).</li>
        <li><a href="{{ route('admin.my-payroll') }}">Bảng lương của tôi</a> — lương theo buổi hoàn thành.</li>
        <li>Chuông thông báo — nhắc ghi nhật ký khi buổi được đánh dấu Hoàn thành.</li>
    </ul>
    <p class="mb-0 small text-muted">Không vào được Thu học phí, danh sách quản lý tất cả GV, hay trang Lương GV tổng hợp (đó là việc của Kế toán / Admin).</p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-journal-bookmark"></i> 1. Lớp học của bạn</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.classes.index') }}">Lớp học</a>
        · <a href="{{ route('admin.classes.index') }}">/admin/classes</a>
    </p>
    <ol>
        <li>Mở lớp mình dạy → các tab: Thông tin (chỉ xem), Học viên, Thời khóa biểu, <strong>Nhật ký</strong>.</li>
        <li>Tab <em>Thời khóa biểu</em>: xem buổi theo tháng; có thể vào điểm danh buổi (nếu có quyền).</li>
        <li>Đào tạo / Admin mới được đánh dấu buổi <em>Hoàn thành</em> (sau khi điểm danh đủ) — khi đó hệ thống tạo nhật ký và gửi thông báo cho bạn.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-journal-text"></i> 2. Ghi nhật ký buổi học</h6>
    <p class="guide-path">
        Lớp → tab <strong>Nhật ký</strong>
        · hoặc bấm thông báo nhắc nhật ký trên chuông
    </p>
    <ol>
        <li>Chỉ các buổi <em>Hoàn thành</em> (và thường là buổi bạn dạy) mới hiện để điền.</li>
        <li>Bấm <em>Điền nhật ký</em> / <em>Xem / Sửa</em> → nhập <strong>Tên bài học</strong>, <strong>Nội dung</strong>, <strong>Nhận xét</strong> → Lưu.</li>
        <li>Sĩ số / vắng KP·CP / muộn lấy từ điểm danh (đổi điểm danh sau sẽ cập nhật lại các số này).</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-clipboard-check"></i> 3. Điểm danh</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.attendances.index') }}">Điểm danh</a>
        · hoặc từ TKB lớp bấm điểm danh buổi
    </p>
    <ul>
        <li>Chọn lớp / ngày / buổi → đánh dấu có mặt, vắng, muộn… → Lưu.</li>
        <li>Vắng / muộn có thể kích hoạt Zalo / Email phụ huynh (theo cấu hình trung tâm).</li>
        <li>Buổi chưa điểm danh đủ thì Đào tạo không lưu được trạng thái <em>Hoàn thành</em> (ảnh hưởng lương &amp; nhật ký).</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-wallet"></i> 4. Bảng lương của tôi</h6>
    <p class="guide-path">
        Sidebar / avatar → <a href="{{ route('admin.my-payroll') }}">Bảng lương của tôi</a>
        · <a href="{{ route('admin.my-payroll') }}">/admin/my-payroll</a>
    </p>
    <ol>
        <li>Chọn tháng / năm → xem gốc (buổi HT), thưởng / phạt / ứng, phải trả (net).</li>
        <li>Chi tiết từng buổi dạy + xuất PDF cá nhân.</li>
        <li>Không thấy số liệu? Kiểm tra email login = email hồ sơ Giáo viên; buổi phải ở trạng thái <em>Hoàn thành</em>.</li>
        <li>Thưởng / phạt / ứng / chi lương do Kế toán thao tác trên trang Lương GV — bạn chỉ xem phần của mình.</li>
    </ol>
</div>

<div class="guide-tip">
    <strong>Hay quên:</strong>
    Email hồ sơ ≠ email login → không nhận nhắc nhật ký / không ra lương ·
    Buổi chưa Hoàn thành → chưa tính lương ·
    Cần quyền rộng hơn (sửa lớp, thu phí)? Nhờ Admin gán thêm role Đào tạo / Kế toán.
</div>
