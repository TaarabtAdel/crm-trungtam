@php
    $openJournalId = (int) request('open_journal', 0);
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem">
    <p class="small text-muted mb-0">
        Nhật ký tự tạo khi buổi được đánh dấu <strong>Hoàn thành</strong>.
        Số liệu sĩ số / vắng / muộn lấy từ điểm danh; giáo viên điền thêm tên bài, nội dung, nhận xét.
    </p>
    <form method="GET" action="{{ route('admin.classes.show', $class) }}" class="d-flex align-items-center" style="gap:.5rem">
        <input type="hidden" name="tab" value="journal">
        <label class="mb-0 small text-muted">Lọc tháng</label>
        <select name="month" class="form-control form-control-sm" style="width:170px" onchange="this.form.submit()">
            <option value="">Tất cả</option>
            @foreach($availableMonths as $ym)
                <option value="{{ $ym }}" @selected($month === $ym)>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('m/Y') }}
                </option>
            @endforeach
            @if($month !== '' && ! in_array($month, $availableMonths, true))
                <option value="{{ $month }}" selected>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('m/Y') }}</option>
            @endif
        </select>
        @if($month !== '')
            <a href="{{ route('admin.classes.show', ['class' => $class, 'tab' => 'journal']) }}" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
        @endif
    </form>
</div>

<div class="table-responsive border rounded">
    <table class="table table-hover mb-0">
        <thead>
        <tr>
            <th>Ngày</th>
            <th>GV dạy</th>
            <th>Sĩ số</th>
            <th>Vắng KP</th>
            <th>Vắng CP</th>
            <th>Muộn</th>
            <th>Bài học</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($sessions as $session)
            @php $journal = $session->journal; @endphp
            <tr>
                <td class="text-nowrap">{{ $session->session_date->format('d/m/Y') }}</td>
                <td>{{ $session->teacher?->name ?? $class->teacher?->name ?? '—' }}</td>
                <td>{{ $journal?->enrollment_count ?? '—' }}</td>
                <td>{{ $journal?->absent_count ?? '—' }}</td>
                <td>{{ $journal?->excused_count ?? '—' }}</td>
                <td>{{ $journal?->late_count ?? '—' }}</td>
                <td>
                    @if($journal?->lesson_title)
                        <span class="font-weight-bold">{{ $journal->lesson_title }}</span>
                    @else
                        <span class="text-muted small">Chưa điền</span>
                    @endif
                </td>
                <td class="text-nowrap">
                    @if($journal)
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-toggle="modal"
                                data-target="#journalSession{{ $session->id }}">
                            <i class="bi bi-journal-text"></i>
                            {{ $journal->isFilled() ? 'Xem / Sửa' : 'Điền nhật ký' }}
                        </button>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    Chưa có buổi hoàn thành{{ $month !== '' ? ' trong tháng này' : '' }}.
                    Đánh dấu hoàn thành ở tab <strong>Thời khóa biểu</strong>.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@foreach($sessions as $session)
    @include('admin.training.class_tabs._journal_modal', [
        'class' => $class,
        'session' => $session,
        'journal' => $session->journal,
        'autoOpen' => $openJournalId === (int) $session->id,
    ])
@endforeach
