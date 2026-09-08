<div class="guide-role-intro mb-3">
    <span class="badge badge-success">Vai trò Đào tạo</span>
    <p class="mb-0 mt-2 text-muted">Lớp, TKB, GV, học viên, điểm danh, thu học phí theo lớp. Menu: <strong>Quản trị đào tạo</strong> + <strong>Quản trị học viên</strong>.</p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-diagram-3"></i> Quy trình gợi ý</h6>
    <ol class="mb-0">
        <li>Tạo <a href="{{ route('admin.subjects.index') }}"><strong>Môn học</strong></a> → tạo <a href="{{ route('admin.teachers.index') }}"><strong>Giáo viên</strong></a> (email trùng user login để nhận nhắc nhật ký).</li>
        <li>Tạo <a href="{{ route('admin.classes.index') }}"><strong>Lớp</strong></a> (lịch + loại học phí + đơn giá).</li>
        <li>Vào lớp → tab <strong>Thời khóa biểu</strong> → Sinh buổi.</li>
        <li>Thêm <a href="{{ route('admin.students.index') }}"><strong>Học viên</strong></a> vào lớp.</li>
        <li><strong>Điểm danh đủ</strong> → đánh dấu buổi <em>Hoàn thành</em> → tab <strong>Nhật ký</strong> (GV điền nội dung).</li>
        <li>Tab <strong>Thu học phí</strong> tạo HĐ (hoặc nhờ Kế toán tại <a href="{{ route('admin.invoices.index') }}">Hóa đơn</a>).</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-book"></i> 1. Môn học</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.subjects.index') }}">Môn học</a>
        · <a href="{{ route('admin.subjects.index') }}">/admin/subjects</a>
    </p>
    <ol>
        <li>Bấm <em>+ Thêm môn học</em>.</li>
        <li>Điền: <strong>Tên *</strong>, Chi nhánh*, Mô tả, Trạng thái (Hoạt động / Ngưng).</li>
        <li>Sửa bằng nút bút chì; lọc theo tên / trạng thái.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-person-badge"></i> 2. Giáo viên</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.teachers.index') }}">Giáo viên</a>
        · <a href="{{ route('admin.teachers.index') }}">/admin/teachers</a>
    </p>
    <p class="mb-1"><strong>Thêm GV</strong> (<em>+ Thêm giáo viên</em>)</p>
    <ul>
        <li>Bắt buộc: Chi nhánh*, Họ tên*, Email* — <strong>email nên trùng tài khoản user</strong> để nhận thông báo nhắc ghi nhật ký.</li>
        <li>Khác: SĐT, chuyên môn, trình độ, <strong>Lương theo giờ</strong>, ngày vào làm, ghi chú, trạng thái.</li>
    </ul>
    <p class="mb-1 mt-2"><strong>Chi tiết GV</strong> — 4 tab (mở từ danh sách)</p>
    <ul>
        <li><em>Thông tin</em> — hồ sơ.</li>
        <li><em>Lớp đang dạy</em> — các lớp gắn GV.</li>
        <li><em>Lương GV</em> — lọc tháng; tạm tính = buổi <strong>Hoàn thành</strong> × đơn giá/giờ.</li>
        <li><em>Lịch dạy</em> — buổi theo tháng.</li>
    </ul>
    <p class="mb-0">Nút <em>Bảng lương</em> trên list để xuất payroll (nếu có quyền).</p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-journal-bookmark"></i> 3. Lớp học — tạo lớp</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.classes.index') }}">Lớp học</a>
        · <a href="{{ route('admin.classes.index') }}">/admin/classes</a>
    </p>
    <ol>
        <li>Bấm <em>+ Thêm lớp mới</em>.</li>
        <li>Điền: Chi nhánh*, Tên*, Mã, Môn, GV, Phòng.</li>
        <li>Lịch: tick thứ <em>T2–CN</em>, giờ bắt đầu/kết thúc, sĩ số tối đa.</li>
        <li><strong>Loại học phí:</strong> Theo tháng / Theo buổi (+ đơn giá Học phí).</li>
        <li>Ngày bắt đầu / kết thúc, Trạng thái: Đang học / Ngưng / Kết thúc.</li>
        <li>Lọc list theo tên, trạng thái, môn → <em>Lọc</em>. Vào lớp bằng <em>Chi tiết</em>.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-layout-text-window"></i> 4. Chi tiết lớp — các tab</h6>
    <p class="guide-path">
        Từ <a href="{{ route('admin.classes.index') }}">danh sách lớp</a> → Chi tiết · Header có nút
        <a href="{{ route('admin.attendances.index') }}">Điểm danh</a>
    </p>

    <div class="guide-subcards">
        <div class="guide-subcard">
            <strong>Thông tin</strong>
            <p class="mb-0">Xem / sửa thông tin lớp, học phí, lịch cấu hình.</p>
        </div>
        <div class="guide-subcard">
            <strong>Học viên</strong>
            <p class="mb-0"><em>Thêm học viên vào lớp</em> (tìm &amp; chọn nhiều). Gỡ bằng nút xóa trên dòng. Cảnh báo khi đủ sĩ số max. <em>Không</em> tự tạo HĐ khi thêm tại đây.</p>
        </div>
        <div class="guide-subcard">
            <strong>Thời khóa biểu</strong>
            <p class="mb-0">Chọn tháng xem. Tạo TKB / thêm buổi. Điểm danh trên từng buổi. <strong>Chỉ đánh dấu Hoàn thành sau khi đã điểm danh đủ HV</strong>.</p>
        </div>
        <div class="guide-subcard">
            <strong>Nhật ký</strong>
            <p class="mb-0">Tự tạo khi buổi Hoàn thành (ngày, lớp, sĩ số, vắng KP/CP, muộn). Giáo viên điền: tên bài, nội dung, nhận xét. Admin/Đào tạo không cần mở modal sau khi hoàn thành — chỉ thông báo.</p>
        </div>
        <div class="guide-subcard">
            <strong>Thu học phí</strong>
            <p class="mb-0">Chọn tháng billing. <em>Tạo hóa đơn học phí</em> (modal) — chi tiết mục 5 bên dưới.</p>
        </div>
    </div>

    <p class="mb-1 mt-2"><strong>Trạng thái buổi học</strong></p>
    <ul>
        <li><em>Đã lên lịch</em> → sau khi dạy: điểm danh đủ → đổi <em>Hoàn thành</em> (tính lương + tạo nhật ký) hoặc <em>Hủy</em>.</li>
        <li>Buổi đã qua mà vẫn “Đã lên lịch” → thông báo nhắc cập nhật trạng thái.</li>
        <li>Buổi vừa hoàn thành → thông báo nhắc GV ghi nhật ký (user trùng email hồ sơ GV).</li>
        <li>GV trên từng buổi (kể cả dạy thay) dùng để tính lương.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-cash-stack"></i> 5. Tab Thu học phí — tạo HĐ</h6>
    <p class="guide-path">
        Lớp → tab Thu học phí → <em>Tạo hóa đơn học phí</em>
        (hoặc <a href="{{ route('admin.invoices.index') }}">Hóa đơn</a>)
    </p>
    <ol>
        <li>Chọn hình thức: Theo tháng / Theo buổi / Theo khóa (giống Kế toán).</li>
        <li>Theo buổi: tick các buổi cần thu.</li>
        <li>Tab modal:
            <ul>
                <li><strong>Hàng loạt</strong> — tạo cho nhiều HV; có thể tick <em>Bỏ qua HV đã có HĐ</em>; nhập giảm giá / lý do / hạn TT → <em>Tạo HĐ cho N học viên</em>.</li>
                <li><strong>1 học viên</strong> — chọn 1 HV rồi tạo.</li>
            </ul>
        </li>
        <li>Danh sách HĐ lớp full width: mở <em>Thu / Chi tiết</em>, xóa nếu chưa thu.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-mortarboard"></i> 6. Học sinh</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.students.index') }}">Học sinh</a>
        · <a href="{{ route('admin.students.index') }}">/admin/students</a>
    </p>
    <ol>
        <li><em>+ Thêm học viên</em>: Chi nhánh*, Họ tên*; DOB, giới tính (Nam/Nữ/Khác), trạng thái (Đang học / Bảo lưu / Hoàn thành / Nghỉ), SĐT/Email, người thân, tick lớp, địa chỉ, ghi chú.</li>
        <li><em>Nhập Excel</em>: tải mẫu → cột Họ tên* bắt buộc; lớp tùy chọn (nhiều lớp cách bằng <code>;</code>) → import.</li>
        <li>Lọc: tên, trạng thái, lớp.</li>
        <li><strong>Chi tiết HV</strong> tabs: Thông tin | Lớp học | Lịch sử học phí | Điểm danh. Có thể gắn lớp kèm tùy chọn <em>Tự tạo hóa đơn học phí</em>.</li>
        <li>Shortcut: <em>Hóa đơn</em>, <em>Điểm danh lớp</em>.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-clipboard-check"></i> 7. Điểm danh</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.attendances.index') }}">Điểm danh</a>
        · <a href="{{ route('admin.attendances.index') }}">/admin/attendances</a>
    </p>
    <ol>
        <li>Chọn <strong>lớp</strong> + <strong>ngày</strong>.</li>
        <li>Với từng HV: Có mặt / Muộn / Vắng / Vắng có phép (+ ghi chú).</li>
        <li>Bấm <em>Lưu điểm danh</em>. Lớp chưa có HV thì không điểm danh được.</li>
        <li><strong>Bắt buộc:</strong> phải điểm danh đủ HV mới được đánh dấu buổi <em>Hoàn thành</em> trên TKB.</li>
        <li>Hoàn thành buổi → tạo nhật ký + tính lương; không làm trên trang điểm danh.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-journal-text"></i> 8. Nhật ký lớp học</h6>
    <p class="guide-path">Chi tiết lớp → tab <strong>Nhật ký</strong></p>
    <ol>
        <li>Đào tạo / Admin đánh dấu buổi Hoàn thành (sau điểm danh) → nhật ký được tạo sẵn.</li>
        <li>Số liệu tự điền: Ngày, Tên lớp, Sĩ số, Vắng không phép, Vắng có phép, Đi muộn (từ điểm danh).</li>
        <li>Giáo viên nhận thông báo hệ thống → điền Tên bài học, Nội dung, Nhận xét → Lưu.</li>
        <li>Lưu điểm danh lại sẽ cập nhật lại các số liệu sĩ số / vắng / muộn trên nhật ký.</li>
    </ol>
</div>

<div class="guide-tip">
    <strong>Hay quên:</strong> Chưa điểm danh đủ thì không lưu được Hoàn thành · Buổi phải <em>Hoàn thành</em> mới tính lương · Email GV hồ sơ ≠ email user thì không nhận nhắc nhật ký.
    Xem vòng đời đầy đủ: <a href="{{ route('admin.guide', ['tab' => 'flow']) }}">Quy trình</a>.
</div>
