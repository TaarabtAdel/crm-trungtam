<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="border rounded p-3 h-100">
            <h6 class="font-weight-bold mb-3">Thêm vào lớp</h6>
            @canPerm('students.manage')
                @if($availableClasses->isEmpty())
                    <p class="text-muted small mb-0">Không còn lớp phù hợp để thêm (cùng chi nhánh, đang học, chưa tham gia).</p>
                    <a href="{{ route('admin.classes.index') }}" class="btn btn-sm btn-outline-primary mt-2">Quản lý lớp</a>
                @else
                    <form method="POST" action="{{ route('admin.students.classes.attach', $student) }}">
                        @csrf
                        <div class="form-group">
                            <label>Chọn lớp *</label>
                            <select name="class_id" class="form-control" required>
                                <option value="">-- Chọn --</option>
                                @foreach($availableClasses as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} @if($c->teacher)— {{ $c->teacher->name }}@endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="createInvoiceStudent" name="create_invoice" value="1" checked>
                                <label class="custom-control-label" for="createInvoiceStudent">Tự tạo hóa đơn học phí</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Số kỳ trả góp</label>
                            <input type="number" name="installment_count" class="form-control" value="1" min="1" max="24">
                        </div>
                        <button class="btn btn-primary btn-block btn-sm">Thêm vào lớp</button>
                    </form>
                @endif
            @else
                <p class="text-muted small mb-0">Bạn chỉ có quyền xem danh sách lớp.</p>
            @endcanPerm
        </div>
    </div>
    <div class="col-lg-8 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Lớp đang theo học ({{ $studentClasses->count() }})</strong>
        </div>
        <div class="table-responsive border rounded">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Lớp</th>
                    <th>Môn / GV</th>
                    <th>Lịch</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($studentClasses as $class)
                    <tr>
                        <td>
                            <a href="{{ route('admin.classes.show', $class) }}" class="font-weight-bold text-dark">{{ $class->name }}</a>
                            @if($class->code)<div class="small text-muted">{{ $class->code }}</div>@endif
                        </td>
                        <td>
                            <div>{{ $class->subject?->name ?: '—' }}</div>
                            <div class="small text-muted">{{ $class->teacher?->name ?: 'Chưa gán GV' }}</div>
                        </td>
                        <td class="small">
                            @if(!empty($class->schedule_days))
                                {{ implode(', ', $class->schedule_days) }}
                                <div class="text-muted">
                                    {{ $class->start_time ? substr($class->start_time, 0, 5) : '' }}
                                    @if($class->start_time || $class->end_time)–@endif
                                    {{ $class->end_time ? substr($class->end_time, 0, 5) : '' }}
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><span class="badge lead-status {{ $class->statusBadgeClass() }}">{{ $class->statusLabel() }}</span></td>
                        <td class="text-nowrap text-right">
                            <a href="{{ route('admin.classes.show', ['class' => $class, 'tab' => 'timetable']) }}" class="btn btn-sm btn-outline-secondary" title="TKB"><i class="bi bi-calendar3"></i></a>
                            @canPerm('students.manage')
                            <form method="POST" action="{{ route('admin.students.classes.detach', [$student, $class]) }}" class="d-inline" onsubmit="return confirm('Gỡ khỏi lớp?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-person-dash"></i></button>
                            </form>
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Chưa xếp lớp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
