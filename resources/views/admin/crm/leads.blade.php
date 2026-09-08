@extends('layouts.admin')

@section('title', 'Leads')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Phễu Lead dùng để làm gì?',
            'body' => '<p class="mb-0">Theo dõi khách tiềm năng theo trạng thái (mới → liên hệ → quan tâm → <strong>Đã chốt</strong> / mất). Lọc theo tên, SĐT, ngày tạo và trạng thái để ưu tiên chăm sóc.</p>',
        ],
        [
            'title' => 'Thêm lead & nhập Excel',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Bấm <em>+ Thêm Lead mới</em> để nhập từng khách (gán Sales, nguồn, chi nhánh).</li>'
                .'<li>Bấm <em>Nhập Excel</em> → tải file mẫu → điền cột bắt buộc <strong>Họ tên</strong>, <strong>SĐT</strong> rồi tải lên.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Gán Sales & xem chi tiết',
            'body' => '<p class="mb-0">Cột <em>Phân bổ</em> cho biết Sales phụ trách. Bấm tên lead hoặc <em>Chi tiết</em> để mở hồ sơ, cập nhật trạng thái và ghi lịch sử tư vấn.</p>',
        ],
        [
            'title' => 'Phạm vi hiển thị',
            'body' => '<p class="mb-0">Tài khoản Sales chỉ thấy lead được gán cho mình. Quản lý / Admin xem toàn bộ theo chi nhánh đang chọn trên header.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Danh sách Leads</h5>
            <small class="text-muted">
                @if(auth()->user()->isSales())
                    Chỉ hiển thị lead được gán cho bạn.
                @else
                    Quản lý toàn bộ khách hàng tiềm năng và thông tin liên hệ tuyển sinh.
                @endif
            </small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalLeadsHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
            @canPerm('crm.leads.import')
            <button class="btn btn-outline-success btn-sm" data-toggle="modal" data-target="#modalImport">
                <i class="bi bi-file-earmark-excel"></i> Nhập Excel
            </button>
            @endcanPerm
            @canPerm('crm.leads.manage')
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Thêm Lead mới</button>
            @endcanPerm
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:240px" placeholder="Tìm theo tên, SĐT, email...">
            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" style="max-width:160px">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" style="max-width:160px">
            <select name="status" class="form-control form-control-sm" style="max-width:180px">
                <option value="">Tất cả trạng thái</option>
                @foreach(\App\Models\Lead::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected($status===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0 leads-table">
                <thead>
                <tr>
                    <th>Khách hàng</th>
                    <th>Liên hệ</th>
                    <th>Người liên quan</th>
                    <th>Phân bổ</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td>
                            <div class="d-flex align-items-start" style="gap:.5rem">
                                <div class="lead-avatar">{{ strtoupper(mb_substr($lead->name, 0, 1)) }}</div>
                                <div>
                                    <a href="{{ route('admin.leads.show', $lead) }}" class="font-weight-bold text-dark">{{ $lead->name }}</a>
                                    <div class="mt-1 d-flex align-items-center flex-wrap" style="gap:.35rem">
                                        <span class="badge lead-status {{ $lead->statusBadgeClass() }}">{{ $lead->statusLabel() }}</span>
                                        @if($lead->source)
                                            <span class="lead-meta-chip">{{ $lead->source }}</span>
                                        @endif
                                        @if(($lead->interactions_count ?? 0) > 0)
                                            <span class="lead-meta-chip"><i class="bi bi-chat-left-text"></i> {{ $lead->interactions_count }}</span>
                                        @endif
                                        @if($lead->follow_up_at && $lead->status === 'new')
                                            <span class="lead-meta-chip {{ $lead->follow_up_at->isPast() && !$lead->follow_up_at->isToday() ? 'text-danger' : '' }}">
                                                <i class="bi bi-alarm"></i> {{ $lead->follow_up_at->format('d/m') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>@if($lead->phone)<i class="bi bi-telephone text-muted mr-1"></i>{{ $lead->phone }}@else<span class="text-muted">—</span>@endif</div>
                            @if($lead->email)
                                <div class="small text-muted"><i class="bi bi-envelope mr-1"></i>{{ $lead->email }}</div>
                            @endif
                        </td>
                        <td>
                            @if($lead->related_name || $lead->related_phone || $lead->related_email)
                                <div>{{ $lead->related_name ?: '—' }}</div>
                                @if($lead->related_phone)<div class="small text-muted">{{ $lead->related_phone }}</div>@endif
                                @if($lead->related_email)<div class="small text-muted">{{ $lead->related_email }}</div>@endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $lead->branch?->name ?? '—' }}</div>
                            <div class="small text-muted">
                                <i class="bi bi-person-badge mr-1"></i>{{ $lead->assignedSales?->name ?? 'Chưa gán Sales' }}
                            </div>
                        </td>
                        <td class="text-nowrap small text-muted">{{ $lead->created_at->format('d/m/Y') }}</td>
                        <td class="text-nowrap text-right">
                            <a href="{{ route('admin.leads.show', $lead) }}" class="btn btn-sm btn-primary">Chi tiết</a>
                            @canPerm('crm.leads.manage')
                            <form action="{{ route('admin.leads.destroy', $lead) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button></form>
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Không tìm thấy dữ liệu phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $leads->links() }}
    </div>
</div>

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.leads.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Thêm Lead Mới</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.crm._lead_form', ['lead'=>null,'branches'=>$branches,'salesUsers'=>$salesUsers])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu Lead</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.leads.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Nhập Lead từ Excel</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Tải file mẫu, điền dữ liệu rồi tải lên. Cột bắt buộc: <strong>Họ tên *</strong>.
                    SĐT / Email không bắt buộc. Nếu để trống <em>Chi nhánh</em>, hệ thống dùng chi nhánh đang chọn trên header.
                </p>
                <div class="mb-3">
                    <a href="{{ route('admin.leads.import.template') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download"></i> Tải file mẫu Excel
                    </a>
                </div>
                <div class="form-group mb-0">
                    <label>File Excel (.xlsx, .xls, .csv) *</label>
                    <input type="file" name="file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                <button class="btn btn-success">Nhập dữ liệu</button>
            </div>
        </form>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalLeadsHelp',
    'title' => 'Hướng dẫn — Danh sách Leads',
    'items' => $helpItems,
])

@include('partials.select2')
@endsection

@push('scripts')
<script>
(function () {
    var $modal = $('#modalCreate');

    function initSalesSelect2($root) {
        var $el = ($root || $(document)).find('.js-lead-sales-select');
        if (!$el.length || typeof crmSelect2Local !== 'function') return;
        crmSelect2Local($el, {
            placeholder: 'Chọn Sales phụ trách',
            allowClear: true,
            dropdownParent: $root && $root.hasClass('modal') ? $root.find('.modal-content') : undefined
        });
    }

    $modal.on('shown.bs.modal', function () {
        initSalesSelect2($modal);
    });
})();
</script>
@endpush
