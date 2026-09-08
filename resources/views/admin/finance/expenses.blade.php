@extends('layouts.admin')

@section('title', 'Chi phí')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Luồng xử lý chi phí',
            'body' => '<ol class="mb-0 pl-3">'
                .'<li><strong>Đề xuất</strong> — tạo khoản chi (trạng thái Chờ duyệt).</li>'
                .'<li><strong>Duyệt / Từ chối</strong> — người có quyền duyệt xác nhận.</li>'
                .'<li><strong>Đã chi</strong> — sau khi duyệt, đánh dấu đã thực chi tiền.</li>'
                .'</ol>',
        ],
        [
            'title' => 'Quyền cần có',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><em>Đề xuất / Đã chi / Xóa</em>: quyền quản lý chi phí.</li>'
                .'<li><em>Duyệt / Từ chối</em>: quyền duyệt chi phí (thường dành quản lý).</li>'
                .'</ul>',
        ],
        [
            'title' => 'Lương GV',
            'body' => '<p class="mb-0">Chọn loại <em>Lương GV</em> sẽ hiện ô chọn giáo viên (Select2) và tháng lương. Có thể chi nhanh hơn tại menu <a href="'.route('admin.finance.teacher-payroll').'">Lương GV</a>.</p>',
        ],
        [
            'title' => 'Lương nhân viên',
            'body' => '<p class="mb-0">Chọn loại <em>Lương nhân viên</em> → chọn user + tháng lương. Hoặc chi nhanh tại <a href="'.route('admin.finance.staff-payroll').'">Lương NV</a> sau khi đã chấm công.</p>',
        ],
        [
            'title' => 'Lọc & chứng từ',
            'body' => '<p class="mb-0">Lọc theo trạng thái hoặc loại chi để theo dõi. Có thể đính kèm chứng từ khi đề xuất; mở link <em>Chứng từ</em> trên từng dòng để xem lại.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Quản lý chi phí</h5>
            <small class="text-muted">Đề xuất → duyệt → thực chi.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            @canPerm('finance.expenses.manage')
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Đề xuất chi</button>
            @endcanPerm
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalExpensesHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <select name="status" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả TT</option>
                @foreach(\App\Models\Expense::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <select name="category" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả loại</option>
                @foreach(\App\Models\Expense::categoryOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($category ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Ngày</th><th>Loại</th><th>Số tiền</th><th>Chi nhánh</th><th>Người tạo</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->expense_date?->format('d/m/Y') }}</td>
                        <td>
                            <div>{{ $item->categoryLabel() }}</div>
                            @if($item->teacher)
                                <div class="small"><i class="bi bi-person-badge"></i> {{ $item->teacher->name }}@if($item->billing_month) · {{ \Carbon\Carbon::createFromFormat('Y-m', $item->billing_month)->format('m/Y') }}@endif</div>
                            @endif
                            @if($item->staffUser)
                                <div class="small"><i class="bi bi-person"></i> {{ $item->staffUser->name }}@if($item->billing_month) · {{ \Carbon\Carbon::createFromFormat('Y-m', $item->billing_month)->format('m/Y') }}@endif</div>
                            @endif
                            @if($item->note)<div class="small text-muted">{{ \Illuminate\Support\Str::limit($item->note, 50) }}</div>@endif
                            @if($item->attachment_path)<a class="small" href="{{ \App\Support\TenantStorage::url($item->attachment_path) }}" target="_blank">Chứng từ</a>@endif
                        </td>
                        <td class="font-weight-bold">{{ $fmt($item->amount) }}</td>
                        <td>{{ $item->branch?->name ?: '—' }}</td>
                        <td>{{ $item->creator?->name }}</td>
                        <td><span class="badge lead-status {{ $item->statusBadgeClass() }}">{{ $item->statusLabel() }}</span></td>
                        <td class="text-nowrap text-right">
                            @canPerm('finance.expenses.approve')
                            @if($item->status==='pending')
                            <form action="{{ route('admin.expenses.approve', $item) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-success">Duyệt</button></form>
                            <form action="{{ route('admin.expenses.reject', $item) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Từ chối</button></form>
                            @endif
                            @endcanPerm
                            @canPerm('finance.expenses.manage')
                            @if($item->status==='approved')
                            <form action="{{ route('admin.expenses.paid', $item) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-primary">Đã chi</button></form>
                            @endif
                            @if(in_array($item->status, ['pending','rejected'], true))
                            <form action="{{ route('admin.expenses.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                            @endif
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có khoản chi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>

@canPerm('finance.expenses.manage')
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.expenses.store') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Đề xuất chi</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <div class="form-group"><label>Chi nhánh</label>
                    <select name="branch_id" class="form-control">
                        <option value="">-- Theo mặc định --</option>
                        @foreach($branches as $b)<option value="{{ $b->id }}" @selected(($currentBranchId ?? '')==$b->id)>{{ $b->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label>Loại chi *</label>
                    <select name="category" id="expenseCategory" class="form-control" required>
                        @foreach(\App\Models\Expense::categoryOptions() as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div id="expenseSalaryFields" style="display:none">
                    <div class="form-group">
                        <label>Giáo viên *</label>
                        <select name="teacher_id" id="expenseTeacherId" class="form-control" style="width:100%">
                            <option value="">-- Chọn giáo viên --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}@if($t->phone) — {{ $t->phone }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="expenseStaffSalaryFields" style="display:none">
                    <div class="form-group">
                        <label>Nhân viên *</label>
                        <select name="user_id" id="expenseStaffUserId" class="form-control" style="width:100%">
                            <option value="">-- Chọn nhân viên --</option>
                            @foreach($staffUsers as $su)
                                <option value="{{ $su->id }}">{{ $su->name }}@if($su->phone) — {{ $su->phone }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="expenseBillingFields" style="display:none">
                    <div class="form-group">
                        <label>Tháng lương *</label>
                        <input type="month" name="billing_month" id="expenseBillingMonth" class="form-control" value="{{ now()->format('Y-m') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Số tiền *</label><input type="number" name="amount" class="form-control" min="1" required></div>
                    <div class="form-group col-md-6"><label>Ngày chi *</label><input type="date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                </div>
                <div class="form-group"><label>Ghi chú</label><textarea name="note" class="form-control" rows="2"></textarea></div>
                <div class="form-group mb-0"><label>Chứng từ</label><input type="file" name="attachment" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Gửi đề xuất</button></div>
        </form>
    </div>
</div>
@endcanPerm

@include('partials.page_help', [
    'modalId' => 'modalExpensesHelp',
    'title' => 'Hướng dẫn — Chi phí',
    'items' => $helpItems,
])
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
    var $modal = $('#modalCreate');
    var $category = $('#expenseCategory');
    var $salaryFields = $('#expenseSalaryFields');
    var $staffFields = $('#expenseStaffSalaryFields');
    var $billingFields = $('#expenseBillingFields');
    var $teacher = $('#expenseTeacherId');
    var $staff = $('#expenseStaffUserId');
    var teacherSelect2Ready = false;
    var staffSelect2Ready = false;

    function toggleSalaryFields() {
        var cat = $category.val();
        var isTeacher = cat === 'salary';
        var isStaff = cat === 'staff_salary';
        $salaryFields.toggle(isTeacher);
        $staffFields.toggle(isStaff);
        $billingFields.toggle(isTeacher || isStaff);
        $teacher.prop('required', isTeacher);
        $staff.prop('required', isStaff);
        $('#expenseBillingMonth').prop('required', isTeacher || isStaff);
        if (!isTeacher) {
            $teacher.val(null).trigger('change');
        }
        if (!isStaff) {
            $staff.val(null).trigger('change');
        }
    }

    function initTeacherSelect2() {
        if (teacherSelect2Ready || !$teacher.length) return;
        $teacher.select2({
            theme: 'bootstrap4',
            placeholder: '-- Chọn giáo viên --',
            allowClear: true,
            width: '100%',
            dropdownParent: $modal,
            language: {
                noResults: function () { return 'Không tìm thấy giáo viên'; },
                searching: function () { return 'Đang tìm...'; }
            }
        });
        teacherSelect2Ready = true;
    }

    function initStaffSelect2() {
        if (staffSelect2Ready || !$staff.length) return;
        $staff.select2({
            theme: 'bootstrap4',
            placeholder: '-- Chọn nhân viên --',
            allowClear: true,
            width: '100%',
            dropdownParent: $modal,
            language: {
                noResults: function () { return 'Không tìm thấy nhân viên'; },
                searching: function () { return 'Đang tìm...'; }
            }
        });
        staffSelect2Ready = true;
    }

    $category.on('change', toggleSalaryFields);

    $modal.on('shown.bs.modal', function () {
        initTeacherSelect2();
        initStaffSelect2();
        toggleSalaryFields();
    });

    $modal.on('hidden.bs.modal', function () {
        if (teacherSelect2Ready) {
            $teacher.val(null).trigger('change');
        }
        if (staffSelect2Ready) {
            $staff.val(null).trigger('change');
        }
        $category.val('operations');
        toggleSalaryFields();
    });
})();
</script>
@endpush
