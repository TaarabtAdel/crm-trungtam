<div class="modal fade" id="taskCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{ route('admin.tasks.store') }}" class="modal-content" id="taskCreateForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle mr-1"></i> Tạo công việc</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Tên công việc <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required maxlength="255" placeholder="Ví dụ: Chuẩn bị báo cáo tháng...">
                    <small class="form-text text-muted">Tạo xong sẽ là <strong>bản nháp</strong> — chưa gửi thông báo. Bấm <em>Công bố</em> khi sẵn sàng.</small>
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Mô tả chi tiết (tuỳ chọn)"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Người thực hiện</label>
                        @if($canAssign)
                            <select name="assignee_id" class="form-control js-create-assignee" data-placeholder="Chọn người thực hiện...">
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" @selected($u->id === auth()->id())>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                            <input type="hidden" name="assignee_id" value="{{ auth()->id() }}">
                        @endif
                    </div>
                    <div class="form-group col-md-4">
                        <label>Ưu tiên</label>
                        <select name="priority" class="form-control">
                            @foreach(\App\Models\Task::PRIORITIES as $k => $label)
                                <option value="{{ $k }}" @selected($k === 'medium')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Hạn hoàn thành</label>
                        <input type="datetime-local" name="due_date" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Người liên quan</label>
                    <select name="watcher_ids[]" class="form-control js-create-watchers" multiple data-placeholder="Chọn người theo dõi...">
                        @foreach($users as $u)
                            @if($u->id !== auth()->id())
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>Checklist</label>
                    <div id="createChecklistWrap" class="task-create-checklist">
                        <div class="task-create-check-row">
                            <input type="text" name="checklist[]" class="form-control form-control-sm" placeholder="Mục việc cần làm...">
                        </div>
                    </div>
                    <button type="button" class="btn btn-link btn-sm px-0 mt-1" id="addChecklistRow">
                        <i class="bi bi-plus-lg"></i> Thêm mục
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                <button class="btn btn-primary"><i class="bi bi-check2 mr-1"></i> Tạo công việc</button>
            </div>
        </form>
    </div>
</div>
