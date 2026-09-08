<div class="text-center py-4">
    @if($checklist['percent'] >= 100)
        <div class="display-4 text-success mb-2"><i class="bi bi-check-circle"></i></div>
        <h5 class="font-weight-bold">Phần bắt buộc đã hoàn tất</h5>
        <p class="text-muted">Bạn có thể bắt đầu vận hành: lead → lớp → điểm danh → thu học phí.</p>
    @else
        <div class="display-4 text-warning mb-2"><i class="bi bi-exclamation-circle"></i></div>
        <h5 class="font-weight-bold">Còn {{ $checklist['required_total'] - $checklist['required_done'] }} mục bắt buộc</h5>
        <p class="text-muted">Xem checklist bên phải — bấm mục chưa xong để quay lại bước tương ứng.</p>
    @endif

    <div class="d-flex justify-content-center flex-wrap mt-3" style="gap:.5rem">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">Vào bảng điều khiển</a>
        <a href="{{ route('admin.guide', ['tab' => 'flow']) }}" class="btn btn-outline-secondary">Xem quy trình</a>
        <a href="{{ route('admin.quick-setup.show', ['step' => \App\Support\GuideSetupChecklist::firstIncompleteStep()]) }}" class="btn btn-outline-primary">Tiếp tục mục còn thiếu</a>
    </div>
</div>
