<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Trạng thái</div>
            <span class="badge lead-status {{ $lead->statusBadgeClass() }}">{{ $lead->statusLabel() }}</span>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Doanh thu dự kiến</div>
            <div class="stat-value" style="font-size:1.25rem">@vnd($lead->expected_revenue)</div>
            <small class="text-muted">Chỉ dùng cho CRM / pipeline Sales, không phải doanh thu thực.</small>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Lượt tư vấn</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $lead->interactions_count }}</div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Ngày tạo</div>
            <div class="font-weight-bold">{{ $lead->created_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>
</div>
@if($lead->follow_up_at)
<div class="alert {{ $lead->status === 'new' && $lead->follow_up_at->isPast() && !$lead->follow_up_at->isToday() ? 'alert-warning' : 'alert-light border' }} small mb-3">
    <i class="bi bi-alarm mr-1"></i>
    <strong>Hạn xử lý:</strong> {{ $lead->follow_up_at->format('d/m/Y') }}
    @if($lead->status === 'new')
        · Lead còn trạng thái Mới — cập nhật trạng thái khi đã xử lý.
    @endif
</div>
@endif

@canPerm('crm.leads.manage')
<form method="POST" action="{{ route('admin.leads.update', $lead) }}">
    @csrf @method('PUT')
    <input type="hidden" name="from_detail" value="1">
    @include('admin.crm._lead_form', ['lead' => $lead, 'branches' => $branches, 'salesUsers' => $salesUsers])
    <div class="alert alert-light border small mt-2 mb-3">
        <strong>Doanh thu dự kiến</strong> giúp Sales theo dõi pipeline và KPI trên Dashboard Sales.
        Doanh thu thực tế vẫn ghi nhận qua <em>hóa đơn đã thu</em>.
    </div>
    <div class="text-right">
        <button class="btn btn-primary">Lưu thông tin</button>
    </div>
</form>
@else
<div class="border rounded p-3">
    <div class="row">
        <div class="col-md-6 mb-2"><strong>Họ tên:</strong> {{ $lead->name }}</div>
        <div class="col-md-6 mb-2"><strong>SĐT khách:</strong> {{ $lead->phone ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Email khách:</strong> {{ $lead->email ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Nguồn:</strong> {{ $lead->source ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Chi nhánh:</strong> {{ $lead->branch?->name }}</div>
        <div class="col-md-6 mb-2"><strong>Sales:</strong> {{ $lead->assignedSales?->name ?? '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Hạn xử lý:</strong> {{ $lead->follow_up_at?->format('d/m/Y') ?? '—' }}</div>
        @if($lead->student_id)
            <div class="col-md-6 mb-2"><strong>Học viên:</strong>
                <a href="{{ route('admin.students.show', $lead->student_id) }}">#{{ $lead->student_id }} — xem hồ sơ</a>
            </div>
        @endif
        <div class="col-md-12 mb-2"><strong>Người thân:</strong>
            @if($lead->related_name || $lead->related_phone || $lead->related_email)
                {{ $lead->related_name ?: '—' }}
                @if($lead->related_phone) · {{ $lead->related_phone }} @endif
                @if($lead->related_email) · {{ $lead->related_email }} @endif
            @else
                —
            @endif
        </div>
    </div>
</div>
@endcanPerm
