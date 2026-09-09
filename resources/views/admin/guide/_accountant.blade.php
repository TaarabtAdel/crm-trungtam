<div class="guide-role-intro mb-3">
    <span class="badge badge-info">Vai trò Kế toán</span>
    <p class="mb-0 mt-2 text-muted">Thu học phí, công nợ học phí, chi phí, hoàn tiền, hoa hồng và báo cáo. Menu chính: <strong>Quản trị tài chính</strong>.</p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-pie-chart"></i> 1. Dashboard tài chính</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.finance.dashboard') }}">Dashboard TC</a>
        · <a href="{{ route('admin.finance.dashboard') }}">/admin/finance</a>
    </p>
    <ol>
        <li>Chọn <strong>chi nhánh</strong> góc trên (hoặc “Tất cả”) trước khi xem số liệu.</li>
        <li>Đọc 4 chỉ số: <em>Thu tháng này</em>, <em>Chi tháng này</em>, <em>Lãi/Lỗ tháng</em>, <em>Tổng công nợ</em>.</li>
        <li>Khối <strong>Lương GV tháng này</strong>: Tạm tính / Đã chi / Còn phải chi — đối chiếu trước khi chi lương.</li>
        <li>Bảng <strong>Công nợ ưu tiên</strong> → bấm <em>Chi tiết</em> để mở hóa đơn và thu tiền.</li>
        <li>Nút tắt: <em>+ Hóa đơn</em>, <em>Lương GV</em>, <em>Báo cáo</em>.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-receipt"></i> 2. Hóa đơn học phí — tạo &amp; lọc</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.invoices.index') }}">Hóa đơn học phí</a>
        · <a href="{{ route('admin.invoices.index') }}">/admin/invoices</a>
    </p>
    <p class="mb-1"><strong>Lọc danh sách</strong></p>
    <ul>
        <li>Ô tìm: mã HĐ hoặc tên học viên.</li>
        <li>Dropdown <em>Tất cả lớp</em> / chọn 1 lớp.</li>
        <li>Trạng thái: <em>Chưa thu</em> · <em>Thu một phần</em> · <em>Đã thu</em> · <em>Hủy</em> → bấm <em>Lọc</em>.</li>
    </ul>
    <p class="mb-1 mt-2"><strong>Tạo hóa đơn</strong> (nút <em>+ Tạo hóa đơn</em>)</p>
    <ol>
        <li>Chọn <strong>Học viên *</strong>.</li>
        <li>Chọn <strong>Lớp</strong> (khuyến nghị): hệ thống gợi ý số tiền theo cấu hình lớp.</li>
        <li>Chọn <strong>Hình thức thu</strong>:
            <ul>
                <li><em>Theo tháng</em> — đơn giá lớp × số buổi trong tháng tham chiếu (không tính buổi Hủy).</li>
                <li><em>Theo buổi</em> — tick ≥ 1 buổi trên danh sách → thành tiền = đơn giá × số buổi chọn.</li>
                <li><em>Theo khóa</em> — đơn giá × toàn bộ buổi lịch lớp.</li>
            </ul>
        </li>
        <li>Nhập <strong>Tháng tham chiếu</strong> (vd <code>2026-09</code>) khi thu theo tháng/buổi theo tháng.</li>
        <li>Có thể nhập <strong>Giảm giá</strong> + <strong>Lý do giảm</strong> → xem <em>Thành tiền</em> sau giảm.</li>
        <li>Không chọn lớp → nhập <strong>Số tiền</strong> thủ công.</li>
        <li>Tùy chọn: Sales phụ trách (để tính hoa hồng), số kỳ trả góp, hạn TT, ghi chú.</li>
        <li>Trạng thái ban đầu thường để <em>Chưa thu</em> (trừ khi thu đủ ngay).</li>
    </ol>
    <p class="mb-0 mt-2"><strong>Xóa:</strong> chỉ xóa được HĐ chưa thu / chưa có tiền đã thu. Tick nhiều dòng → <em>Xóa đã chọn</em>.</p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-cash-coin"></i> 3. Chi tiết hóa đơn — thu tiền, trả góp, PDF</h6>
    <p class="guide-path">
        Từ <a href="{{ route('admin.invoices.index') }}">danh sách hóa đơn</a> → bấm mã HĐ hoặc <em>Chi tiết</em>
    </p>
    <p class="mb-1"><strong>Ghi nhận thu</strong></p>
    <ol>
        <li>Nhập <strong>Số tiền *</strong> (≤ còn nợ), <strong>Phương thức</strong>: Tiền mặt / Chuyển khoản / Thẻ / Ví điện tử.</li>
        <li>Chọn <strong>Ngày thu</strong>; kỳ trả góp (hoặc để <em>Tự phân bổ</em>).</li>
        <li>Có thể đính kèm biên lai → bấm <em>Ghi nhận thu</em>.</li>
        <li>Thu từng phần được phép đến khi hết nợ → trạng thái chuyển <em>Thu một phần</em> rồi <em>Đã thu</em>.</li>
    </ol>
    <p class="mb-1 mt-2"><strong>Trả góp</strong></p>
    <ul>
        <li>Nhập số kỳ + hạn kỳ đầu → <em>Cập nhật kỳ</em>. Hệ thống chia lịch; từng kỳ: Chưa thu / Một phần / Đã thu / Quá hạn.</li>
    </ul>
    <p class="mb-1 mt-2"><strong>PDF</strong> (nút <em>PDF</em>)</p>
    <ul>
        <li>Xuất phiếu: thông tin trung tâm / HV, hạng mục, giảm giá (nếu có), tổng / đã thu / còn lại.</li>
        <li>Nếu chi nhánh đã cấu hình STK → block <strong>Thanh toán chuyển khoản (VietQR)</strong>: QR + STK + số còn nợ + nội dung CK = mã HĐ.</li>
    </ul>
    <p class="mb-0 mt-2"><strong>Hoàn tiền:</strong> trên dòng thanh toán bấm <em>Hoàn</em> → nhập số tiền hoàn + lý do → <em>Gửi yêu cầu</em> (duyệt ở <a href="{{ route('admin.refunds.index') }}">Hoàn tiền</a>).</p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-exclamation-triangle"></i> 4. Công nợ học phí</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.debts.index') }}">Công nợ học phí</a>
        · <a href="{{ route('admin.debts.index') }}">/admin/debts</a>
    </p>
    <ol>
        <li>Lọc: <em>Tất cả</em> / <em>Quá hạn</em> / <em>Sắp đến hạn (7 ngày)</em>.</li>
        <li>Chỉ hiện HĐ còn nợ → <em>Chi tiết</em> để thu.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-wallet2"></i> 5. Chi phí</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.expenses.index') }}">Chi phí</a>
        · <a href="{{ route('admin.expenses.index') }}">/admin/expenses</a>
    </p>
    <ol>
        <li>Bấm <em>+ Đề xuất chi</em>.</li>
        <li>Điền: Chi nhánh, <strong>Loại chi *</strong> (Vận hành / Lương GV / Ứng lương GV / Lương NV / Ứng lương NV / Marketing / Khác), Số tiền*, Ngày chi*, ghi chú, chứng từ.</li>
        <li>Nếu loại <em>Lương GV</em> / <em>Ứng lương GV</em>: chọn Giáo viên* + Tháng lương*.</li>
        <li>Nếu loại <em>Lương nhân viên</em> / <em>Ứng lương NV</em>: chọn User* + Tháng lương*. Nên tạo ứng từ trang Lương GV/NV.</li>
        <li>Trạng thái: <em>Chờ duyệt</em> → (Admin) <em>Duyệt</em> / <em>Từ chối</em> → bấm <em>Đã chi</em> khi đã chuyển tiền.</li>
        <li>Badge đỏ trên menu = số phiếu đang chờ duyệt. Xóa chỉ khi Chờ duyệt hoặc Từ chối.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-person-badge"></i> 6. Lương giáo viên</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.finance.teacher-payroll') }}">Lương GV</a>
        · <a href="{{ route('admin.finance.teacher-payroll') }}">/admin/finance/teacher-payroll</a>
    </p>
    <ol>
        <li>Chọn <strong>Tháng / Năm</strong> → <em>Xem</em>.</li>
        <li><strong>Gốc</strong> = buổi <em>Hoàn thành</em> × đơn giá/giờ (kể cả dạy thay).</li>
        <li><strong>Phải trả (net)</strong> = Gốc + Thưởng − Phạt − Ứng trước. <strong>Còn lại</strong> = Net − phiếu <em>Lương GV</em>.</li>
        <li>Menu <em>Hành động</em> từng dòng: Thưởng / Phạt / Ứng / Chi lương / PDF cá nhân — thưởng·phạt·ứng cần <strong>ghi chú bắt buộc</strong>.
            <em>Ứng</em> tạo phiếu Chi phí <em>Ứng lương GV</em> ngay.</li>
        <li>Menu <em>Thao tác</em> (header): <em>Thưởng tất cả</em> / <em>Phạt tất cả</em> / phiếu chi lương.</li>
        <li>Bấm <em>Chi lương</em> khi còn lại &gt; 0 (mặc định = còn lại; có thể chi từng phần). Tick <em>Đã chi ngay</em> nếu có quyền.</li>
        <li><em>Xuất tổng hợp</em>: PDF gộp cột Gốc/Thưởng/Phạt/Ứng. Lịch sử điều chỉnh ở cuối trang (xóa được nếu ứng chưa Đã chi).</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-cash-stack"></i> 6b. Lương nhân viên</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.finance.staff-payroll') }}">Lương NV</a>
        · trước đó chấm công tại <a href="{{ route('admin.staff-attendances.index') }}">Chấm công NV</a>
    </p>
    <ol>
        <li>Chấm công (có mặt / nửa ngày) → chọn tháng trên <em>Lương NV</em>.</li>
        <li>Công thức giống Lương GV: Gốc (công × lương/ngày) + Thưởng − Phạt − Ứng = Net.</li>
        <li>Menu <em>Hành động</em> / <em>Thao tác</em> tương tự Lương GV (ứng → phiếu <em>Ứng lương NV</em>).</li>
        <li>PDF cá nhân / xuất tổng hợp (cột gộp) giống Lương GV.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-wallet"></i> 6c. Bảng lương của tôi</h6>
    <p class="guide-path">
        Sidebar / avatar → <a href="{{ route('admin.my-payroll') }}">Bảng lương của tôi</a>
        · <a href="{{ route('admin.my-payroll') }}">/admin/my-payroll</a>
    </p>
    <ul>
        <li>Mỗi user tự xem lương cá nhân (không cần vào trang Lương GV/NV tổng hợp).</li>
        <li>Chỉ role Giáo viên → lương GV; role khác → lương NV; vừa GV vừa role khác → 2 tab chọn.</li>
        <li>GV cần email trùng hồ sơ Giáo viên mới ra số buổi / đơn giá.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-arrow-counterclockwise"></i> 7. Hoàn tiền</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.refunds.index') }}">Hoàn tiền</a>
        · <a href="{{ route('admin.refunds.index') }}">/admin/refunds</a>
    </p>
    <ul>
        <li>Không tạo mới tại đây — tạo từ chi tiết HĐ (nút <em>Hoàn</em> trên dòng thanh toán).</li>
        <li>Lọc: Chờ duyệt / Đã duyệt / Từ chối → <em>Duyệt</em> hoặc <em>Từ chối</em>.</li>
        <li>Khi duyệt: giảm tiền đã thu trên HĐ, cập nhật công nợ / trạng thái HĐ.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-percent"></i> 8. Hoa hồng (xem)</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.commissions.index') }}">Hoa hồng</a>
        · <a href="{{ route('admin.commissions.index') }}">/admin/commissions</a>
    </p>
    <ul>
        <li>HH phát sinh khi HĐ <em>Đã thu</em> + đã gắn Sales + có quy tắc Active.</li>
        <li>Lọc: Chưa thanh toán / Đã thanh toán. Có thể đánh dấu <em>Đã trả HH</em>.</li>
        <li>Cấu hình % nằm ở <a href="{{ route('admin.commission-rules.index') }}">Quy tắc HH</a> (thường do Admin).</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-bar-chart-line"></i> 9. Báo cáo tài chính</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.finance.reports') }}">Báo cáo TC</a>
        · <a href="{{ route('admin.finance.reports') }}">/admin/finance/reports</a>
    </p>
    <ol>
        <li>Chọn kiểu kỳ: Ngày / Tháng / Quý / Năm hoặc Từ–Đến → <em>Xem</em>.</li>
        <li>Đọc: Thu, Hoàn, Thu ròng, Chi, Lãi/Lỗ (+ lương tạm tính / đã chi).</li>
        <li>Xem breakdown theo lớp / chi nhánh / Sales.</li>
        <li>Xuất <em>Excel</em> hoặc <em>PDF</em> nếu có quyền export.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-mortarboard"></i> 10. Học sinh (tra cứu)</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.students.index') }}">Học sinh</a>
        · <a href="{{ route('admin.students.index') }}">/admin/students</a>
    </p>
    <ul>
        <li>Tìm HV để đối chiếu khi thu phí / xem lớp đang học. Thường không tạo/sửa hồ sơ (do Đào tạo).</li>
    </ul>
</div>

<div class="guide-tip">
    <strong>Checklist thu phí 1 HV:</strong>
    Mở <a href="{{ route('admin.invoices.index') }}">Hóa đơn học phí</a> → Tạo HĐ → Chi tiết → Ghi nhận thu → (tuỳ chọn) PDF gửi PH.
    QR không hiện → nhờ Admin cấu hình STK tại <a href="{{ route('admin.branches.index') }}">Chi nhánh</a>.
</div>
