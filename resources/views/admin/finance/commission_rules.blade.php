@extends('layouts.admin')

@section('title', 'Quy tắc hoa hồng')

@section('content')
@php
    $helpItems = [
        [
            'title' => '1. Hoa hồng được tính khi nào?',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Chỉ tính khi <strong>hóa đơn thu đủ</strong> (trạng thái <em>Đã thu</em>).</li>'
                .'<li>Hóa đơn phải có gắn <strong>Sales phụ trách</strong> (chọn khi tạo HĐ).</li>'
                .'<li>Số tiền HH = <code>tổng tiền HĐ × %</code> theo quy tắc phù hợp.</li>'
                .'<li>Sau khi tính, vào menu <strong>Hoa hồng</strong> để xem và bấm <em>Đã trả HH</em> khi chi trả cho Sales.</li>'
                .'</ul>',
        ],
        [
            'title' => '2. Phạm vi Global vs Theo lớp',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Toàn hệ thống (Global)</strong>: áp dụng mặc định cho mọi hóa đơn.</li>'
                .'<li><strong>Theo lớp</strong>: chỉ áp dụng HĐ của lớp đó; <em>ưu tiên cao hơn Global</em>.</li>'
                .'<li>Ví dụ: Global 5%, lớp IELTS 8% → HĐ lớp IELTS dùng 8%; lớp khác dùng 5%.</li>'
                .'</ul>',
        ],
        [
            'title' => '3. Tier doanh thu tối thiểu là gì?',
            'body' => '<p class="mb-2">Dùng để thưởng % cao hơn khi giá trị HĐ đủ lớn. Hệ thống chọn rule <strong>tier cao nhất mà HĐ vẫn đạt</strong>.</p>'
                .'<ul class="mb-0 pl-3">'
                .'<li>Để trống / 0 = áp dụng mọi mức tiền.</li>'
                .'<li>Ví dụ Global: <code>5%</code> (tier 0) và <code>8%</code> (tier 10.000.000).</li>'
                .'<li>HĐ 8 triệu → 5%. HĐ 12 triệu → 8%.</li>'
                .'</ul>',
        ],
        [
            'title' => '4. Cách cấu hình nhanh (khuyến nghị)',
            'body' => '<ol class="mb-0 pl-3">'
                .'<li>Tạo 1 rule <strong>Global</strong>, ví dụ 5%, tier trống, bật Active.</li>'
                .'<li>Nếu lớp đặc biệt: thêm rule <strong>Theo lớp</strong> với % riêng.</li>'
                .'<li>Khi tạo hóa đơn, nhớ chọn <strong>Sales phụ trách</strong>.</li>'
                .'<li>Thu đủ HĐ → hệ thống tự tạo dòng hoa hồng.</li>'
                .'<li>Vào <a href="'.route('admin.commissions.index').'">Hoa hồng</a> để theo dõi / đánh dấu đã trả.</li>'
                .'</ol>',
        ],
        [
            'title' => '5. Active / tắt rule',
            'body' => '<p class="mb-0">Bỏ tick <strong>Active</strong> rồi Lưu = tạm ngưng rule (không xóa). Rule không Active sẽ không được dùng để tính HH mới.</p>',
        ],
    ];
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Quy tắc hoa hồng</h5>
            <small class="text-muted">Cấu hình % hoa hồng cho Sales khi hóa đơn được thu đủ.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <a href="{{ route('admin.commissions.index') }}" class="btn btn-sm btn-outline-secondary">Danh sách hoa hồng</a>
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalCommissionHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        <form method="POST" action="{{ route('admin.commission-rules.store') }}" class="border rounded p-3 mb-3 bg-light">
            @csrf
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Thêm quy tắc mới</strong>
            </div>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Phạm vi *</label>
                    <select name="scope" class="form-control" id="ruleScope">
                        <option value="global">Toàn hệ thống (Global)</option>
                        <option value="class">Theo lớp</option>
                    </select>
                    <small class="text-muted">Lớp ưu tiên hơn Global</small>
                </div>
                <div class="form-group col-md-3" id="ruleClassWrap" style="display:none">
                    <label>Lớp *</label>
                    <select name="class_id" class="form-control">
                        <option value="">-- Chọn lớp --</option>
                        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>% hoa hồng *</label>
                    <input type="number" step="0.01" name="percent" class="form-control" value="5" min="0" max="100" required>
                    <small class="text-muted">VD: 5 = 5%</small>
                </div>
                <div class="form-group col-md-2">
                    <label>Tier DT tối thiểu</label>
                    <input type="number" name="tier_min_revenue" class="form-control" placeholder="Để trống = mọi mức" min="0">
                    <small class="text-muted">Áp dụng nếu tiền HĐ ≥ mức này</small>
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-block">Thêm quy tắc</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Phạm vi</th>
                    <th>Lớp</th>
                    <th>Chỉnh % / Tier / Active</th>
                    <th>Tier hiện tại</th>
                    <th>Đang dùng</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td>
                            <span class="lead-meta-chip">{{ $rule->scope === 'class' ? 'Theo lớp' : 'Global' }}</span>
                        </td>
                        <td>{{ $rule->courseClass?->name ?: '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.commission-rules.update', $rule) }}" class="form-inline">
                                @csrf @method('PUT')
                                <input type="number" step="0.01" name="percent" class="form-control form-control-sm mr-1" style="width:90px" value="{{ $rule->percent }}" title="% hoa hồng">
                                <input type="number" name="tier_min_revenue" class="form-control form-control-sm mr-1" style="width:120px" value="{{ $rule->tier_min_revenue }}" placeholder="Tier" title="Tier DT tối thiểu">
                                <label class="mr-2 mb-0 small"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active)> Active</label>
                                <button class="btn btn-sm btn-outline-primary">Lưu</button>
                            </form>
                        </td>
                        <td>{{ $rule->tier_min_revenue ? number_format($rule->tier_min_revenue, 0, ',', '.').' đ' : 'Mọi mức' }}</td>
                        <td>
                            @if($rule->is_active)
                                <span class="badge lead-status lead-status-won">Có</span>
                            @else
                                <span class="badge lead-status lead-status-lost">Không</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('admin.commission-rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Xóa quy tắc này?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Chưa có quy tắc. Hãy thêm rule Global 5% để bắt đầu.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalCommissionHelp',
    'title' => 'Hướng dẫn — Quy tắc hoa hồng',
    'items' => $helpItems,
])
@endsection

@push('scripts')
<script>
$('#ruleScope').on('change', function(){ $('#ruleClassWrap').toggle(this.value==='class'); }).trigger('change');
</script>
@endpush
