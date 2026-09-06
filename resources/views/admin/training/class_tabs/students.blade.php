<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="border rounded p-3 h-100">
            <h6 class="font-weight-bold mb-3">Thêm học viên vào lớp</h6>
            @if($availableStudents->isEmpty())
                <p class="text-muted small mb-0">Không còn học viên phù hợp để thêm (cùng chi nhánh, đang học, chưa trong lớp).</p>
                <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-outline-primary mt-2">Quản lý học viên</a>
            @else
                <form method="POST" action="{{ route('admin.classes.students.attach', $class) }}">
                    @csrf
                    <div class="form-group">
                        <label>Chọn học viên *</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">-- Chọn --</option>
                            @foreach($availableStudents as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} @if($s->parent_phone)— {{ $s->parent_phone }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="createInvoice" name="create_invoice" value="1" checked>
                            <label class="custom-control-label" for="createInvoice">Tự tạo hóa đơn học phí</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Số kỳ trả góp</label>
                        <input type="number" name="installment_count" class="form-control" value="1" min="1" max="24">
                    </div>
                    <button class="btn btn-primary btn-block btn-sm">Thêm vào lớp</button>
                </form>
            @endif
            @if($class->max_students > 0 && $classStudents->count() >= $class->max_students)
                <div class="alert alert-warning py-2 small mt-3 mb-0">Lớp đã đạt sĩ số tối đa ({{ $class->max_students }}).</div>
            @endif
        </div>
    </div>
    <div class="col-lg-8 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Danh sách học viên ({{ $classStudents->count() }})</strong>
        </div>
        <div class="table-responsive border rounded">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Họ tên</th>
                    <th>Phụ huynh</th>
                    <th>SĐT</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($classStudents as $i => $student)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $student->name }}</td>
                        <td>{{ $student->parent_name ?: '—' }}</td>
                        <td>{{ $student->parent_phone ?: '—' }}</td>
                        <td><span class="badge badge-info">{{ $student->status }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('admin.classes.students.detach', [$class, $student]) }}" class="d-inline" onsubmit="return confirm('Gỡ học viên khỏi lớp?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-person-dash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Chưa có học viên trong lớp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
