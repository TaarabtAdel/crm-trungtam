@extends('layouts.admin')

@section('title', 'Quy tắc hoa hồng')

@section('content')
<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Quy tắc hoa hồng</h5>
            <small class="text-muted">Ưu tiên rule theo lớp, sau đó rule global. Có thể gắn tier doanh số tối thiểu.</small>
        </div>
    </div>
    <div class="card-body-custom">
        <form method="POST" action="{{ route('admin.commission-rules.store') }}" class="border rounded p-3 mb-3">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-3"><label>Phạm vi</label>
                    <select name="scope" class="form-control" id="ruleScope">
                        <option value="global">Toàn hệ thống</option>
                        <option value="class">Theo lớp</option>
                    </select>
                </div>
                <div class="form-group col-md-3" id="ruleClassWrap" style="display:none"><label>Lớp</label>
                    <select name="class_id" class="form-control">
                        <option value="">-- Chọn --</option>
                        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group col-md-2"><label>% HH</label><input type="number" step="0.01" name="percent" class="form-control" value="5" required></div>
                <div class="form-group col-md-2"><label>Tier DT tối thiểu</label><input type="number" name="tier_min_revenue" class="form-control" placeholder="0"></div>
                <div class="form-group col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block">Thêm</button></div>
            </div>
        </form>

        <table class="table table-hover mb-0">
            <thead><tr><th>Phạm vi</th><th>Lớp</th><th>%</th><th>Tier min</th><th>Active</th><th></th></tr></thead>
            <tbody>
            @foreach($rules as $rule)
                <tr>
                    <td>{{ $rule->scope === 'class' ? 'Lớp' : 'Global' }}</td>
                    <td>{{ $rule->courseClass?->name ?: '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.commission-rules.update', $rule) }}" class="form-inline">
                            @csrf @method('PUT')
                            <input type="number" step="0.01" name="percent" class="form-control form-control-sm mr-1" style="width:90px" value="{{ $rule->percent }}">
                            <input type="number" name="tier_min_revenue" class="form-control form-control-sm mr-1" style="width:110px" value="{{ $rule->tier_min_revenue }}">
                            <label class="mr-2 mb-0"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active)> Active</label>
                            <button class="btn btn-sm btn-outline-primary">Lưu</button>
                        </form>
                    </td>
                    <td>{{ $rule->tier_min_revenue ? number_format($rule->tier_min_revenue,0,',','.') : '—' }}</td>
                    <td>{{ $rule->is_active ? 'Có' : 'Không' }}</td>
                    <td>
                        <form action="{{ route('admin.commission-rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#ruleScope').on('change', function(){ $('#ruleClassWrap').toggle(this.value==='class'); }).trigger('change');
</script>
@endpush
