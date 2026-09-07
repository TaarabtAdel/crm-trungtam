@extends('layouts.admin')

@section('title', 'Phân quyền')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Ma trận phân quyền',
            'body' => '<p class="mb-0">Mỗi cột là một <strong>vai trò</strong>, mỗi dòng là một quyền (xem/sửa menu hoặc thao tác). Tick = được phép; bỏ tick = không truy cập (sidebar cũng ẩn theo).</p>',
        ],
        [
            'title' => 'Cách cấu hình nhanh',
            'body' => '<ol class="mb-0 pl-3">'
                .'<li>Dùng <em>Chọn tất cả</em> trên cột vai trò để bật/tắt nhanh cả nhóm quyền.</li>'
                .'<li>Điều chỉnh từng ô cho đúng nhu cầu từng role.</li>'
                .'<li>Bấm <strong>Lưu phân quyền</strong> để áp dụng.</li>'
                .'</ol>',
        ],
        [
            'title' => 'Super Admin',
            'body' => '<p class="mb-0"><strong>Super Admin</strong> luôn có toàn quyền, không bị giới hạn bởi ma trận này. Các role khác chỉ thấy menu/thao tác đã được tick.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Phân quyền theo vai trò</h5>
            <small class="text-muted">Cấu hình quyền truy cập menu và thao tác cho từng vai trò. Super Admin luôn có toàn quyền.</small>
        </div>
        <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalPermissionsHelp">
            <i class="bi bi-question-circle"></i> Hướng dẫn
        </button>
    </div>
    <div class="card-body-custom">
        <form method="POST" action="{{ route('admin.permissions.update') }}">
            @csrf @method('PUT')
            <div class="table-responsive">
                <table class="table table-bordered table-sm permission-matrix mb-3">
                    <thead class="thead-light">
                    <tr>
                        <th style="min-width:260px">Quyền</th>
                        @foreach($roles as $roleKey => $roleLabel)
                            <th class="text-center" style="min-width:110px">
                                {{ $roleLabel }}
                                <div class="mt-1">
                                    <button type="button" class="btn btn-link btn-sm p-0 js-check-col" data-role="{{ $roleKey }}">Chọn tất cả</button>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($groups as $group)
                        <tr class="table-secondary">
                            <td colspan="{{ count($roles) + 1 }}"><strong>{{ $group['label'] }}</strong></td>
                        </tr>
                        @foreach($group['permissions'] as $permKey => $permLabel)
                            <tr>
                                <td>
                                    <div>{{ $permLabel }}</div>
                                    <small class="text-muted">{{ $permKey }}</small>
                                </td>
                                @foreach($roles as $roleKey => $roleLabel)
                                    <td class="text-center align-middle">
                                        <input type="checkbox"
                                               class="js-perm"
                                               data-role="{{ $roleKey }}"
                                               name="permissions[{{ $roleKey }}][]"
                                               value="{{ $permKey }}"
                                               @checked(in_array($permKey, $matrix[$roleKey] ?? [], true))>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem">
                <small class="text-muted">Bỏ chọn = không được truy cập. Menu sidebar cũng ẩn theo quyền.</small>
                <button class="btn btn-primary">Lưu phân quyền</button>
            </div>
        </form>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalPermissionsHelp',
    'title' => 'Hướng dẫn — Phân quyền',
    'items' => $helpItems,
])
@endsection

@push('scripts')
<script>
$('.js-check-col').on('click', function () {
    var role = $(this).data('role');
    var boxes = $('.js-perm[data-role="'+role+'"]');
    var allChecked = boxes.length === boxes.filter(':checked').length;
    boxes.prop('checked', !allChecked);
});
</script>
@endpush
