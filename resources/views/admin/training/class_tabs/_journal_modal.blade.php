@php
    /** @var \App\Models\ClassSession $session */
    /** @var \App\Models\ClassSessionJournal|null $journal */
    $autoOpen = $autoOpen ?? false;
@endphp
@if($journal)
<div class="modal fade" id="journalSession{{ $session->id }}" tabindex="-1"
     @if($autoOpen) data-auto-open="1" @endif>
    <div class="modal-dialog modal-lg">
        <form method="POST"
              action="{{ route('admin.classes.timetable.sessions.journal.update', [$class, $session]) }}"
              class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">
                    Nhật ký buổi học — {{ $journal->session_date->format('d/m/Y') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="border rounded p-3 bg-light mb-3">
                    <div class="font-weight-bold mb-2">Thông tin có sẵn (từ điểm danh)</div>
                    <div class="row small">
                        <div class="col-md-4 mb-2">
                            <span class="text-muted">Ngày:</span>
                            <strong>{{ $journal->session_date->format('d/m/Y') }}</strong>
                        </div>
                        <div class="col-md-8 mb-2">
                            <span class="text-muted">Tên lớp:</span>
                            <strong>{{ $journal->class_name }}</strong>
                        </div>
                        <div class="col-6 col-md-3 mb-1">
                            <span class="text-muted">Sĩ số:</span>
                            <strong>{{ $journal->enrollment_count }}</strong>
                        </div>
                        <div class="col-6 col-md-3 mb-1">
                            <span class="text-muted">Có mặt:</span>
                            <strong>{{ $journal->present_count }}</strong>
                        </div>
                        <div class="col-6 col-md-3 mb-1">
                            <span class="text-muted">Vắng không phép:</span>
                            <strong class="text-danger">{{ $journal->absent_count }}</strong>
                        </div>
                        <div class="col-6 col-md-3 mb-1">
                            <span class="text-muted">Vắng có phép:</span>
                            <strong>{{ $journal->excused_count }}</strong>
                        </div>
                        <div class="col-6 col-md-3 mb-1">
                            <span class="text-muted">Đi muộn:</span>
                            <strong class="text-warning">{{ $journal->late_count }}</strong>
                        </div>
                    </div>
                    <p class="small text-muted mb-0 mt-2">
                        Số liệu cập nhật lại khi lưu điểm danh hoặc lưu nhật ký.
                    </p>
                </div>

                @canPerm('training.journals.manage')
                    <div class="form-group">
                        <label>Tên bài học</label>
                        <input type="text" name="lesson_title" class="form-control"
                               value="{{ old('lesson_title', $journal->lesson_title) }}"
                               placeholder="VD: Unit 5 — Present Perfect">
                    </div>
                    <div class="form-group">
                        <label>Nội dung</label>
                        <textarea name="content" class="form-control" rows="4"
                                  placeholder="Nội dung giảng dạy trong buổi...">{{ old('content', $journal->content) }}</textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Nhận xét</label>
                        <textarea name="remarks" class="form-control" rows="3"
                                  placeholder="Nhận xét lớp / học viên...">{{ old('remarks', $journal->remarks) }}</textarea>
                    </div>
                    @if($journal->filled_at)
                        <p class="small text-muted mt-2 mb-0">
                            Cập nhật lần cuối: {{ $journal->filled_at->format('d/m/Y H:i') }}
                            @if($journal->filledByUser)
                                · {{ $journal->filledByUser->name }}
                            @endif
                        </p>
                    @endif
                @else
                    <div class="form-group">
                        <label class="text-muted">Tên bài học</label>
                        <div>{{ $journal->lesson_title ?: '—' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted">Nội dung</label>
                        <div style="white-space:pre-wrap">{{ $journal->content ?: '—' }}</div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="text-muted">Nhận xét</label>
                        <div style="white-space:pre-wrap">{{ $journal->remarks ?: '—' }}</div>
                    </div>
                @endcanPerm
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button>
                @canPerm('training.journals.manage')
                    <button class="btn btn-primary">Lưu nhật ký</button>
                @endcanPerm
            </div>
        </form>
    </div>
</div>
@endif
