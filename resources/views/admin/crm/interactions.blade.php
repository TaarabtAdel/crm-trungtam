@extends('layouts.admin')

@section('title', 'Lịch hẹn / Tương tác')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Lên lịch follow-up',
            'body' => '<p class="mb-0">Bấm <em>+ Thêm lịch hẹn mới</em> để tạo gọi điện, nhắn tin, gặp mặt hoặc test năng lực. Chọn lead, thời gian dự kiến, Sales phụ trách và ghi chú.</p>',
        ],
        [
            'title' => 'Trạng thái & lọc',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Lọc theo loại tương tác và trạng thái (sắp tới, hoàn thành, hủy…).</li>'
                .'<li>Cập nhật trạng thái sau mỗi lần chăm sóc để pipeline và dashboard phản ánh đúng.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Phạm vi Sales',
            'body' => '<p class="mb-0">Tài khoản Sales chỉ thấy lịch hẹn do mình phụ trách. Admin / quản lý xem theo chi nhánh đang chọn. Bấm tên khách để mở hồ sơ lead (tab lịch sử).</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Lịch hẹn / Tương tác</h5>
            <small class="text-muted">
                @if(auth()->user()->isSales())
                    Chỉ hiển thị lịch hẹn / tương tác do bạn phụ trách.
                @else
                    Quản lý lịch gọi, nhắn tin, gặp mặt và lịch hẹn test năng lực.
                @endif
            </small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalInteractionsHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
            @canPerm('crm.interactions.manage')
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Thêm lịch hẹn mới</button>
            @endcanPerm
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:240px" placeholder="Tìm khách, SĐT, ghi chú...">
            <select name="type" class="form-control form-control-sm" style="max-width:180px">
                <option value="">Tất cả loại</option>
                @foreach(\App\Models\Interaction::typeOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($type ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <select name="status" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả trạng thái</option>
                @foreach(\App\Models\Interaction::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0 interactions-table">
                <thead>
                <tr>
                    <th>Khách hàng</th>
                    <th>Tương tác</th>
                    <th>Thời gian</th>
                    <th>Phân bổ</th>
                    <th>Ghi chú</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($interactions as $item)
                    <tr>
                        <td>
                            <div class="d-flex align-items-start" style="gap:.5rem">
                                <div class="interaction-avatar">{{ strtoupper(mb_substr($item->lead?->name ?? '?', 0, 1)) }}</div>
                                <div>
                                    @if($item->lead)
                                        <a href="{{ route('admin.leads.show', ['lead' => $item->lead, 'tab' => 'history']) }}" class="font-weight-bold text-dark">{{ $item->lead->name }}</a>
                                        @if($item->lead->phone)
                                            <div class="small text-muted"><i class="bi bi-telephone mr-1"></i>{{ $item->lead->phone }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">Lead đã xóa</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center flex-wrap" style="gap:.35rem">
                                <span class="badge lead-status {{ $item->statusBadgeClass() }}">{{ $item->statusLabel() }}</span>
                                <span class="lead-meta-chip"><i class="bi bi-{{ $item->typeIcon() }}"></i> {{ $item->type }}</span>
                            </div>
                        </td>
                        <td class="text-nowrap">
                            @if($item->scheduled_at)
                                <div class="font-weight-bold">{{ $item->scheduled_at->format('d/m/Y') }}</div>
                                <div class="small text-muted">{{ $item->scheduled_at->format('H:i') }}</div>
                            @else
                                <span class="text-muted">Chưa hẹn giờ</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $item->branch?->name ?? '—' }}</div>
                            <div class="small text-muted">
                                <i class="bi bi-person-badge mr-1"></i>{{ $item->sales?->name ?? 'Chưa gán Sales' }}
                            </div>
                        </td>
                        <td>
                            @if($item->notes)
                                <div class="small text-muted" style="max-width:220px">{{ \Illuminate\Support\Str::limit($item->notes, 80) }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-nowrap text-right">
                            @canPerm('crm.interactions.manage')
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#edit{{ $item->id }}">Sửa</button>
                            <form action="{{ route('admin.interactions.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button></form>
                            @else
                            <span class="text-muted small">—</span>
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Không tìm thấy dữ liệu phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $interactions->links() }}
    </div>
</div>

@canPerm('crm.interactions.manage')
@foreach($interactions as $item)
<div class="modal fade" id="edit{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.interactions.update', $item) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa lịch hẹn</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.crm._interaction_form', ['item'=>$item,'leads'=>$leads,'salesUsers'=>$salesUsers])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.interactions.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Thêm lịch hẹn / tương tác mới</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.crm._interaction_form', ['item'=>null,'leads'=>$leads,'salesUsers'=>$salesUsers])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>
@endcanPerm

@include('partials.page_help', [
    'modalId' => 'modalInteractionsHelp',
    'title' => 'Hướng dẫn — Lịch hẹn / Tương tác',
    'items' => $helpItems,
])

@include('partials.select2')
@endsection

@push('scripts')
<script>
(function () {
    var leadsUrl = @json(route('admin.lookup.leads'));

    function initInteractionSelect2($modal) {
        if (!$modal || !$modal.length || typeof crmSelect2Ajax !== 'function') return;
        crmSelect2Ajax($modal.find('.js-interaction-lead'), leadsUrl, {
            placeholder: 'Tìm lead theo tên, SĐT...',
            allowClear: false,
            dropdownParent: $modal.find('.modal-content')
        });
        var $sales = $modal.find('.js-interaction-sales');
        if ($sales.length) {
            crmSelect2Local($sales, {
                placeholder: 'Chọn Sales',
                allowClear: true,
                dropdownParent: $modal.find('.modal-content')
            });
        }
    }

    $(document).on('shown.bs.modal', '.modal', function () {
        var $modal = $(this);
        if ($modal.find('.js-interaction-lead').length) {
            initInteractionSelect2($modal);
        }
    });
})();
</script>
@endpush
