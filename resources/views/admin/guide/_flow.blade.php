<div class="guide-role-intro mb-3">
    <span class="badge badge-secondary">Quy trình hoạt động</span>
    <p class="mb-0 mt-2 text-muted">Vòng đời end-to-end: từ tuyển sinh → sắp lớp → dạy học → thu học phí → chi lương / báo cáo. Làm theo thứ tự; mỗi bước có link mở trang tương ứng.</p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-signpost-2"></i> Sơ đồ nhanh</h6>
    <ol class="mb-0">
        <li><strong>Nền tảng</strong> — chi nhánh, user, cài đặt (<a href="{{ route('admin.guide', ['tab' => 'setup']) }}">Cài đặt ban đầu</a>).</li>
        <li><strong>Đào tạo dựng khung</strong> — môn → GV → lớp → TKB.</li>
        <li><strong>Sale tuyển sinh</strong> — lead → chăm sóc → chốt.</li>
        <li><strong>Nhập học</strong> — tạo HV → xếp lớp.</li>
        <li><strong>Vận hành lớp</strong> — điểm danh đủ → đánh dấu buổi Hoàn thành → nhật ký + nhắc GV.</li>
        <li><strong>Thu học phí</strong> — tạo HĐ → thu tiền → PDF/QR → (tuỳ chọn) Email/ZNS xác nhận TT.</li>
        <li><strong>Sau thu</strong> — hoa hồng, công nợ, chi phí, lương GV, báo cáo.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-building"></i> Giai đoạn A — Nền tảng (Admin)</h6>
    <ol>
        <li>Cài tên trung tâm: <a href="{{ route('admin.settings.edit') }}">Cài đặt</a>.</li>
        <li>Tạo chi nhánh + STK: <a href="{{ route('admin.branches.index') }}">Chi nhánh</a>.</li>
        <li>Tạo user theo vai (có thể nhiều role / 1 tài khoản): <a href="{{ route('admin.users.index') }}">Người dùng</a>.</li>
        <li>(Tuỳ chọn) SMTP / Zalo ZNS + <a href="{{ route('admin.notification-templates.index') }}">Mẫu thông báo</a>.</li>
        <li>(Tuỳ chọn) Quy tắc HH: <a href="{{ route('admin.commission-rules.index') }}">Quy tắc HH</a>.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-journal-bookmark"></i> Giai đoạn B — Dựng khung đào tạo</h6>
    <p class="guide-path">Thường do <strong>Đào tạo</strong> hoặc Admin</p>
    <ol>
        <li>
            <strong>Tạo môn</strong> tại <a href="{{ route('admin.subjects.index') }}">Môn học</a>
            → <em>+ Thêm môn học</em> (Tên*, Chi nhánh*).
        </li>
        <li>
            <strong>Tạo giáo viên</strong> tại <a href="{{ route('admin.teachers.index') }}">Giáo viên</a>
            → điền Họ tên*, Email*, Chi nhánh*, <em>Lương theo giờ</em> nếu tính lương theo buổi.
        </li>
        <li>
            <strong>Tạo lớp</strong> tại <a href="{{ route('admin.classes.index') }}">Lớp học</a>
            → chọn môn, GV, lịch thứ (T2–CN), giờ, loại học phí (<em>Theo tháng / Theo buổi</em>), đơn giá, ngày bắt đầu–kết thúc.
        </li>
        <li>
            Vào chi tiết lớp → tab <strong>Thời khóa biểu</strong>
            → <em>Tạo thời khóa biểu</em> (khoảng Từ–Đến) hoặc <em>Thêm buổi</em> thủ công.
            Trạng thái mặc định: <em>Đã lên lịch</em>.
        </li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-people"></i> Giai đoạn C — Tuyển sinh (Sale)</h6>
    <p class="guide-path">
        <a href="{{ route('admin.crm.sales') }}">Dashboard Sales</a> ·
        <a href="{{ route('admin.leads.index') }}">Leads</a> ·
        <a href="{{ route('admin.interactions.index') }}">Lịch hẹn</a>
    </p>
    <ol>
        <li>
            Tạo lead tại <a href="{{ route('admin.leads.index') }}">Danh sách Leads</a>
            (<em>+ Thêm Lead mới</em>) hoặc <em>Nhập Excel</em>. Bắt buộc Họ tên*, SĐT*, Chi nhánh*.
        </li>
        <li>Gán <strong>Sales phụ trách</strong> + đặt <strong>Hạn xử lý</strong> (follow-up).</li>
        <li>
            Chăm sóc: ghi tương tác tại <a href="{{ route('admin.interactions.index') }}">Lịch hẹn / Tương tác</a>
            (Cuộc gọi / Hẹn test / Nhắn tin / Gặp trực tiếp) hoặc từ tab lịch sử của lead.
        </li>
        <li>
            Cập nhật trạng thái lead theo pipeline:
            <em>Mới</em> → <em>Đã liên hệ</em> → <em>Quan tâm</em> → <em>Đã chốt</em> (hoặc <em>Thất bại</em>).
        </li>
        <li>
            <strong>Lưu ý:</strong> chuyển sang <em>Đã chốt</em> <u>không</u> tự tạo học viên — phải tạo HV ở giai đoạn D.
        </li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-mortarboard"></i> Giai đoạn D — Nhập học &amp; xếp lớp</h6>
    <p class="guide-path">
        <a href="{{ route('admin.students.index') }}">Học sinh</a>
        · thường do Đào tạo
    </p>
    <ol>
        <li>
            Tạo HV tại <a href="{{ route('admin.students.index') }}">Học sinh</a>
            (<em>+ Thêm học viên</em>) hoặc import Excel. Điền Chi nhánh*, Họ tên*, SĐT/PH nếu có.
        </li>
        <li>
            Xếp lớp theo 1 trong 2 cách:
            <ul>
                <li>Trong hồ sơ HV → tab Lớp học → gắn lớp (có thể tick tự tạo HĐ).</li>
                <li>Trong lớp → tab <strong>Học viên</strong> → <em>Thêm học viên vào lớp</em> (không tự tạo HĐ).</li>
            </ul>
        </li>
        <li>Kiểm tra sĩ số max của lớp; đủ chỗ mới thêm.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-clipboard-check"></i> Giai đoạn E — Vận hành buổi học</h6>
    <p class="guide-path">
        <a href="{{ route('admin.attendances.index') }}">Điểm danh</a>
        · tab TKB / Nhật ký của lớp
    </p>
    <ol>
        <li>
            Điểm danh tại <a href="{{ route('admin.attendances.index') }}">Điểm danh</a>
            hoặc nút điểm danh trên TKB: chọn lớp + ngày → Có mặt / Muộn / Vắng / Vắng có phép → <em>Lưu điểm danh</em>.
        </li>
        <li>
            <strong>Bắt buộc điểm danh đủ học viên</strong> trước khi đánh dấu buổi <em>Hoàn thành</em>
            (hệ thống chặn lưu nếu thiếu).
        </li>
        <li>
            Sau buổi dạy: tab <strong>Thời khóa biểu</strong> → đổi trạng thái thành <em>Hoàn thành</em>
            → hệ thống tạo sẵn <strong>Nhật ký</strong> (ngày, lớp, sĩ số, vắng, muộn) và gửi thông báo nhắc giáo viên.
        </li>
        <li>
            Giáo viên mở chuông thông báo hoặc tab <strong>Nhật ký</strong> để điền: Tên bài học, Nội dung, Nhận xét.
        </li>
        <li>
            Chỉ buổi <em>Hoàn thành</em> mới vào tạm tính <a href="{{ route('admin.finance.teacher-payroll') }}">Lương GV</a>.
        </li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-receipt"></i> Giai đoạn F — Thu học phí</h6>
    <p class="guide-path">
        <a href="{{ route('admin.invoices.index') }}">Hóa đơn</a>
        · hoặc tab Thu học phí trong lớp
    </p>
    <ol>
        <li>
            <strong>Tạo hóa đơn</strong> theo 1 cách:
            <ul>
                <li>Từ lớp → tab <strong>Thu học phí</strong> → <em>Tạo hóa đơn học phí</em> (Hàng loạt / 1 HV).</li>
                <li>Từ <a href="{{ route('admin.invoices.index') }}">Hóa đơn</a> → <em>+ Tạo hóa đơn</em>.</li>
            </ul>
        </li>
        <li>
            Chọn hình thức: <em>Theo tháng</em> / <em>Theo buổi</em> (tick buổi) / <em>Theo khóa</em>;
            nhập giảm giá + lý do nếu có; gắn Sales nếu tính HH.
        </li>
        <li>
            Mở chi tiết HĐ → <em>Ghi nhận thu</em> (tiền mặt / CK / thẻ / ví). Thu từng phần được phép.
        </li>
        <li>
            Xuất <strong>PDF</strong> gửi PH. Nếu chi nhánh có STK → QR VietQR (số còn nợ, nội dung = mã HĐ).
        </li>
        <li>
            Theo dõi còn nợ tại <a href="{{ route('admin.debts.index') }}">Công nợ</a>
            (Tất cả / Quá hạn / Sắp đến hạn 7 ngày).
        </li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-cash-stack"></i> Giai đoạn G — Sau khi thu (tài chính)</h6>
    <ol>
        <li>
            <strong>Hoa hồng:</strong> khi HĐ <em>Đã thu</em> + có Sales + có quy tắc Active
            → xem tại <a href="{{ route('admin.commissions.index') }}">Hoa hồng</a>, đánh dấu đã trả HH khi chi cho Sale.
        </li>
        <li>
            <strong>Hoàn tiền:</strong> từ dòng thanh toán trên HĐ bấm <em>Hoàn</em>
            → duyệt tại <a href="{{ route('admin.refunds.index') }}">Hoàn tiền</a>.
        </li>
        <li>
            <strong>Chi phí vận hành / marketing:</strong>
            <a href="{{ route('admin.expenses.index') }}">Chi phí</a>
            → Đề xuất chi → Admin duyệt → Đã chi.
        </li>
        <li>
            <strong>Chi lương GV:</strong>
            <a href="{{ route('admin.finance.teacher-payroll') }}">Lương GV</a>
            (tháng/năm) → Chi lương (tạo phiếu chi loại Lương GV).
        </li>
        <li>
            <strong>Báo cáo:</strong>
            <a href="{{ route('admin.finance.dashboard') }}">Dashboard TC</a>,
            <a href="{{ route('admin.finance.reports') }}">Báo cáo TC</a> (xuất Excel/PDF),
            <a href="{{ route('admin.reports.index') }}">Báo cáo tổng hợp</a>.
        </li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-arrow-repeat"></i> Chu kỳ lặp hàng tháng (gợi ý)</h6>
    <ul class="mb-0">
        <li>Sale: nhập lead mới, follow-up hạn xử lý, cập nhật trạng thái.</li>
        <li>Đào tạo: sinh TKB tháng mới (nếu cần), điểm danh đủ → hoàn thành buổi → GV ghi nhật ký.</li>
        <li>Kế toán: tạo HĐ tháng / theo buổi → thu → đối soát công nợ → chi lương GV → xem báo cáo.</li>
        <li>Admin: duyệt chi phí chờ, kiểm tra thông báo, điều chỉnh quyền / chi nhánh khi có thay đổi.</li>
    </ul>
</div>

<div class="guide-tip">
    <strong>Đi sâu theo vai:</strong>
    <a href="{{ route('admin.guide', ['tab' => 'sales']) }}">Sale</a> ·
    <a href="{{ route('admin.guide', ['tab' => 'training']) }}">Đào tạo</a> ·
    <a href="{{ route('admin.guide', ['tab' => 'accountant']) }}">Kế toán</a> ·
    <a href="{{ route('admin.guide', ['tab' => 'admin']) }}">Admin</a>.
    Hệ thống trống? Quay lại <a href="{{ route('admin.guide', ['tab' => 'setup']) }}">Cài đặt ban đầu</a>.
</div>
