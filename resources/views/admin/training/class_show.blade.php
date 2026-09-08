@extends('layouts.admin')

@section('title', $class->name)

@section('content')
@php
    $tabs = [
        'info' => ['label' => 'Thông tin', 'icon' => 'bi-info-circle'],
        'students' => ['label' => 'Học viên', 'icon' => 'bi-people', 'count' => $class->students_count],
        'timetable' => ['label' => 'Thời khóa biểu', 'icon' => 'bi-calendar3', 'count' => $class->sessions_count],
        'journal' => ['label' => 'Nhật ký', 'icon' => 'bi-journal-text'],
        'tuition' => ['label' => 'Thu học phí', 'icon' => 'bi-cash-coin'],
    ];
    $helpItems = [
        [
            'title' => 'Các tab chi tiết',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Thông tin</strong>: cấu hình lớp, GV, học phí.</li>'
                .'<li><strong>Học viên</strong>: ghi danh học viên vào lớp (có thể tự tạo hóa đơn).</li>'
                .'<li><strong>Thời khóa biểu</strong>: buổi học theo tháng; <em>điểm danh đủ</em> rồi mới đánh dấu hoàn thành (tạo nhật ký).</li>'
                .'<li><strong>Nhật ký</strong>: sĩ số / vắng / muộn từ điểm danh; GV điền tên bài, nội dung, nhận xét.</li>'
                .'<li><strong>Thu học phí</strong>: tạo HĐ hàng loạt / từng HV và mở thu tiền.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Ghi danh học viên',
            'body' => '<p class="mb-0">Tab Học viên → bấm <em>Thêm học viên vào lớp</em> → tìm và tick nhiều HV cùng lúc. Tạo hóa đơn học phí ở tab <em>Thu học phí</em>.</p>',
        ],
        [
            'title' => 'Hóa đơn & thu tiền',
            'body' => '<p class="mb-0">Tab Thu học phí: chọn hình thức <em>Theo tháng / Theo buổi / Theo khóa</em> (hiển thị số buổi theo lịch), có thể giảm giá + lý do. Tạo hàng loạt hoặc từng học viên, rồi bấm <em>Thu / Chi tiết</em> để ghi nhận thanh toán.</p>',
        ],
        [
            'title' => 'Nhật ký lớp học',
            'body' => '<p class="mb-0">Đào tạo / Admin đánh dấu buổi <em>Hoàn thành</em> trên TKB → hệ thống tạo nhật ký sẵn và gửi <strong>thông báo trong hệ thống</strong> cho giáo viên (user có cùng email với hồ sơ GV). Giáo viên mở chuông thông báo hoặc tab Nhật ký để điền tên bài, nội dung, nhận xét.</p>',
        ],
    ];
@endphp

<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap" style="gap:.75rem">
    <div>
        <a href="{{ route('admin.classes.index') }}" class="text-muted small"><i class="bi bi-arrow-left"></i> Danh sách lớp học</a>
        <h4 class="mb-1 mt-1 font-weight-bold">{{ $class->name }}</h4>
        <div class="text-muted small">
            @if($class->code)<span class="mr-2">Mã: {{ $class->code }}</span>@endif
            <span class="mr-2">{{ $class->branch?->name }}</span>
            @if($class->subject)<span class="mr-2">· {{ $class->subject->name }}</span>@endif
            @if($class->teacher)<span class="mr-2">· GV: {{ $class->teacher->name }}</span>@endif
            <span class="badge badge-info">{{ $class->status }}</span>
        </div>
    </div>
    <div class="d-flex align-items-center" style="gap:.5rem">
        <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalClassShowHelp">
            <i class="bi bi-question-circle"></i> Hướng dẫn
        </button>
        <a href="{{ route('admin.attendances.index', ['class_id' => $class->id]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-clipboard-check"></i> Điểm danh
        </a>
    </div>
</div>

<ul class="nav nav-tabs class-detail-tabs mb-0">
    @foreach($tabs as $key => $meta)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
               href="{{ route('admin.classes.show', ['class' => $class, 'tab' => $key] + (in_array($key, ['timetable', 'journal'], true) ? ['month' => $month ?? now()->format('Y-m')] : []) + ($key === 'tuition' ? ['billing_month' => $billingMonth ?? now()->format('Y-m')] : [])) }}">
                <i class="bi {{ $meta['icon'] }} mr-1"></i>{{ $meta['label'] }}
                @if(!empty($meta['count']))
                    <span class="badge badge-light border ml-1">{{ $meta['count'] }}</span>
                @endif
            </a>
        </li>
    @endforeach
</ul>

<div class="page-card class-detail-panel border-top-0" style="border-top-left-radius:0;border-top-right-radius:0">
    <div class="card-body-custom">
        @if($tab === 'info')
            @include('admin.training.class_tabs.info')
        @elseif($tab === 'students')
            @include('admin.training.class_tabs.students')
        @elseif($tab === 'timetable')
            @include('admin.training.class_tabs.timetable')
        @elseif($tab === 'journal')
            @include('admin.training.class_tabs.journal')
        @elseif($tab === 'tuition')
            @include('admin.training.class_tabs.tuition')
        @endif
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalClassShowHelp',
    'title' => 'Hướng dẫn — Chi tiết lớp học',
    'items' => $helpItems,
])
@endsection

@push('scripts')
<script>
$(document).on('change', '.js-tuition-type', function () {
    var label = $(this).closest('form').find('.js-tuition-fee-label');
    label.text(this.value === 'per_session' ? 'Học phí / buổi' : 'Học phí / tháng');
});

(function () {
    var $modal = $('#modalAttachStudents');
    if (!$modal.length) return;

    var url = @json(route('admin.classes.students.available', $class));
    var searchTimer = null;

    function updateSelectedCount() {
        var n = $modal.find('input[name="student_ids[]"]:checked').length;
        $('#attachSelectedCount').text('Đã chọn: ' + n);
        $('#attachSubmitBtn').prop('disabled', n === 0);
    }

    function renderStudents(students) {
        var $list = $('#attachStudentsList');
        if (!students.length) {
            $list.html('<div class="text-muted small text-center py-4">Không tìm thấy học viên phù hợp.</div>');
            updateSelectedCount();
            return;
        }
        var html = '<div class="row px-1">';
        students.forEach(function (s) {
            var meta = [s.phone, s.parent_name].filter(Boolean).join(' · ');
            html += '<div class="col-md-6 mb-2">'
                + '<div class="custom-control custom-checkbox">'
                + '<input type="checkbox" class="custom-control-input js-attach-student" id="attachStu' + s.id + '" name="student_ids[]" value="' + s.id + '">'
                + '<label class="custom-control-label" for="attachStu' + s.id + '">'
                + '<span class="font-weight-bold">' + $('<div>').text(s.name).html() + '</span>'
                + (meta ? '<br><span class="small text-muted">' + $('<div>').text(meta).html() + '</span>' : '')
                + '</label></div></div>';
        });
        html += '</div>';
        $list.html(html);
        updateSelectedCount();
    }

    function loadStudents(q) {
        $('#attachStudentsList').html('<div class="text-muted small text-center py-4">Đang tải danh sách...</div>');
        $.getJSON(url, { q: q || '' })
            .done(function (res) { renderStudents(res.students || []); })
            .fail(function () {
                $('#attachStudentsList').html('<div class="text-danger small text-center py-4">Không tải được danh sách học viên.</div>');
            });
    }

    $modal.on('show.bs.modal', function () {
        $('#attachStudentSearch').val('');
        loadStudents('');
    });

    $('#attachStudentSearch').on('input', function () {
        var q = $(this).val();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { loadStudents(q); }, 250);
    });

    $('#attachSelectAll').on('click', function () {
        $modal.find('input[name="student_ids[]"]').prop('checked', true);
        updateSelectedCount();
    });
    $('#attachClearAll').on('click', function () {
        $modal.find('input[name="student_ids[]"]').prop('checked', false);
        updateSelectedCount();
    });
    $modal.on('change', 'input[name="student_ids[]"]', updateSelectedCount);

    $('#formAttachStudents').on('submit', function () {
        if ($modal.find('input[name="student_ids[]"]:checked').length === 0) {
            alert('Vui lòng chọn ít nhất một học viên.');
            return false;
        }
    });
})();

$(document).on('change', '.js-session-status', function () {
    var $form = $(this).closest('form');
    $form.find('.js-session-completed-flag').prop('checked', this.value === 'completed');
});

$(document).on('change', '.js-session-completed-flag', function () {
    var $form = $(this).closest('form');
    var $status = $form.find('.js-session-status');
    if (this.checked) {
        $status.val('completed');
    } else if ($status.val() === 'completed') {
        $status.val('scheduled');
    }
});

$(document).on('show.bs.modal', '[id^="editSession"]', function () {
    // Mặc định không tick; chỉ tick khi trạng thái đang / chọn Hoàn thành
    var $form = $(this).find('form');
    $form.find('.js-session-completed-flag').prop('checked', $form.find('.js-session-status').val() === 'completed');
});

$(function () {
    var $auto = $('.modal[data-auto-open="1"]').first();
    if ($auto.length) {
        $auto.modal('show');
    }
});
</script>
@endpush
