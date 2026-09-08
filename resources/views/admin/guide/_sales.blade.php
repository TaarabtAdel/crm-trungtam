<div class="guide-role-intro mb-3">
    <span class="badge badge-warning text-dark">Vai trò Sale</span>
    <p class="mb-0 mt-2 text-muted">Tuyển sinh &amp; chăm sóc: lead, lịch hẹn; theo dõi công nợ / hoa hồng. Menu: <strong>Tuyển sinh (CRM)</strong>.</p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-funnel"></i> Pipeline gợi ý</h6>
    <ol class="mb-0">
        <li>Nhập / được gán <a href="{{ route('admin.leads.index') }}"><strong>Lead</strong></a> → gọi / nhắn / hẹn (<a href="{{ route('admin.interactions.index') }}">Tương tác</a>).</li>
        <li>Cập nhật trạng thái + <strong>Hạn xử lý</strong> (follow-up).</li>
        <li>Chốt (<em>Đã chốt</em>) → Đào tạo tạo HV / xếp lớp (hệ thống <strong>không</strong> tự tạo HV khi chốt).</li>
        <li>Theo dõi công nợ PH; khi HĐ thu đủ → kiểm tra <a href="{{ route('admin.commissions.index') }}"><strong>Hoa hồng</strong></a>.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-graph-up"></i> 1. Dashboard Sales</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.crm.sales') }}">Dashboard Sales</a>
        · <a href="{{ route('admin.crm.sales') }}">/admin/crm/sales</a>
    </p>
    <ul>
        <li>KPI: Khách mới hôm nay, Tương tác hôm nay, Đã chốt hôm nay, Tỷ lệ chuyển đổi, Doanh thu pipeline.</li>
        <li>Chọn kỳ: <em>Tất cả thời gian</em> / <em>Tháng này</em>.</li>
        <li>Bảng hiệu suất: Được phân / Tương tác / Đã chốt / Doanh thu / Tỷ lệ.</li>
        <li>Sale thường chỉ thấy số liệu của mình. Link tắt: <a href="{{ route('admin.leads.index') }}">Danh sách Leads</a>, <a href="{{ route('admin.interactions.index') }}">Lịch hẹn</a>.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-people"></i> 2. Danh sách Leads</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.leads.index') }}">Danh sách Leads</a>
        · <a href="{{ route('admin.leads.index') }}">/admin/leads</a>
    </p>
    <p class="mb-1"><strong>Lọc</strong></p>
    <ul>
        <li>Tìm theo tên / SĐT; khoảng ngày; trạng thái → <em>Lọc</em>.</li>
        <li>Trạng thái: <em>Mới</em> → <em>Đã liên hệ</em> → <em>Quan tâm</em> → <em>Đã chốt</em> / <em>Thất bại</em>.</li>
    </ul>
    <p class="mb-1 mt-2"><strong>Thêm Lead</strong> (<em>+ Thêm Lead mới</em>)</p>
    <ol>
        <li>Bắt buộc: <strong>Họ tên *</strong>, <strong>SĐT *</strong>, Chi nhánh*.</li>
        <li>Khác: Email, Nguồn (Facebook Ads / Zalo / Google / Giới thiệu / Walk-in / Khác), DT dự kiến, Sales phụ trách, Trạng thái.</li>
        <li><strong>Hạn xử lý</strong>: ngày cần chăm tiếp. Nếu tạo mới và gán Sales mà để trống → hệ thống mặc định +2 ngày.</li>
        <li>Người liên quan (nếu có).</li>
    </ol>
    <p class="mb-1 mt-2"><strong>Import Excel</strong> (<em>Nhập Excel</em>)</p>
    <ul>
        <li>Tải mẫu → cột Họ tên*, SĐT* → import hàng loạt.</li>
    </ul>
    <p class="mb-0 mt-2"><strong>List:</strong> chip hạn xử lý hiện khi lead còn <em>Mới</em>. Sale thường chỉ thấy lead được gán cho mình.</p>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-person-lines-fill"></i> 3. Chi tiết Lead</h6>
    <p class="guide-path">
        Từ <a href="{{ route('admin.leads.index') }}">danh sách leads</a> → Chi tiết
    </p>
    <ul>
        <li>Tab <em>Thông tin</em>: sửa form → <em>Lưu thông tin</em>. Alert nếu tới / quá hạn xử lý.</li>
        <li>Tab <em>Lịch sử tư vấn</em>: thêm tương tác gắn lead (gọi, hẹn test…).</li>
        <li>Khi Admin gán lead cho bạn → nhận <strong>thông báo</strong> “Bạn được phân lead mới” (không báo nếu tự gán mình).</li>
        <li>Lead sắp tới hạn / quá hạn follow-up mà vẫn chưa xử lý → thông báo nhắc trên chuông.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-calendar-check"></i> 4. Lịch hẹn / Tương tác</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.interactions.index') }}">Lịch hẹn / Tương tác</a>
        · <a href="{{ route('admin.interactions.index') }}">/admin/interactions</a>
    </p>
    <ol>
        <li>Bấm <em>+ Thêm lịch hẹn mới</em>.</li>
        <li>Chọn <strong>Lead *</strong>, Loại*: Cuộc gọi / Lịch hẹn Test / Nhắn tin / Gặp trực tiếp.</li>
        <li>Thời gian hẹn, Trạng thái: Sắp diễn ra / Hoàn thành / Hủy / Không đến, Ghi chú.</li>
        <li>Lọc list theo từ khóa, loại, trạng thái. Sửa / xóa trên từng dòng.</li>
        <li>Cũng có thể tạo tương tác từ tab Lịch sử tư vấn của lead.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-mortarboard"></i> 5. Học sinh (xem)</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.students.index') }}">Học sinh</a>
        · <a href="{{ route('admin.students.index') }}">/admin/students</a>
    </p>
    <ul>
        <li>Tra cứu HV đã nhập học sau khi chốt. Tạo hồ sơ / xếp lớp do Đào tạo hoặc Admin.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-receipt"></i> 6. Hóa đơn &amp; công nợ (theo dõi)</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.invoices.index') }}">Hóa đơn</a>
        · <a href="{{ route('admin.debts.index') }}">Công nợ</a>
    </p>
    <ul>
        <li>Xem HĐ / còn nợ liên quan HV mình phụ trách (theo quyền).</li>
        <li>Nhắc PH thanh toán; <strong>không</strong> ghi nhận thu tiền tại đây nếu không có quyền Kế toán — nhờ Kế toán thu và xuất PDF/QR.</li>
        <li>Công nợ lọc: Tất cả / Quá hạn / Sắp đến hạn 7 ngày.</li>
    </ul>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-percent"></i> 7. Hoa hồng</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.commissions.index') }}">Hoa hồng</a>
        · <a href="{{ route('admin.commissions.index') }}">/admin/commissions</a>
    </p>
    <ol>
        <li>HH chỉ xuất hiện khi: HĐ gắn đúng Sales của bạn + trạng thái <em>Đã thu</em> + quy tắc HH đang Active.</li>
        <li>Lọc Chưa thanh toán / Đã thanh toán.</li>
        <li>Thiếu dòng HH? Kiểm tra 3 điều trên; quy tắc (% / theo lớp) do Admin cấu hình ở <a href="{{ route('admin.commission-rules.index') }}">Quy tắc HH</a>.</li>
    </ol>
</div>

<div class="guide-section">
    <h6 class="guide-section-title"><i class="bi bi-bar-chart"></i> 8. Báo cáo tổng hợp</h6>
    <p class="guide-path">
        Sidebar → <a href="{{ route('admin.reports.index') }}">Báo cáo</a>
        · <a href="{{ route('admin.reports.index') }}">/admin/reports</a>
    </p>
    <ul>
        <li>Snapshot: Leads, Đã chốt, tỷ lệ, DT dự kiến, đã thu / chưa thu, HV, lớp, GV (xem nhanh, không xuất file).</li>
    </ul>
</div>

<div class="guide-tip">
    <strong>Mẹo chăm sóc:</strong> Mỗi lần gọi xong → ghi Tương tác + cập nhật trạng thái lead + đặt lại Hạn xử lý.
    Vòng đời đầy đủ: <a href="{{ route('admin.guide', ['tab' => 'flow']) }}">Quy trình</a>.
</div>
