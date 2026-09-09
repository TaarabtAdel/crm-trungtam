@php
    $latest = $placementTests->first();
@endphp

<div class="row">
    <div class="col-lg-5 mb-3">
        <div class="border rounded p-3">
            <h6 class="font-weight-bold mb-2">Ghi kết quả test đầu vào</h6>
            <form method="POST" action="{{ route('admin.leads.placement.store', $lead) }}">
                @csrf
                <div class="form-group">
                    <label>Môn test</label>
                    <select name="subject_id" class="form-control">
                        <option value="">—</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" @selected(old('subject_id', $lead->interest_subject_id)==$s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label>Điểm</label>
                        <input type="number" step="0.01" name="score" class="form-control" value="{{ old('score') }}" min="0">
                    </div>
                    <div class="form-group col-6">
                        <label>Level</label>
                        <select name="level" class="form-control">
                            <option value="">—</option>
                            @foreach(\App\Models\PlacementTest::levelOptions() as $k=>$v)
                                <option value="{{ $k }}" @selected(old('level')===$k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Đề xuất lớp</label>
                    <select name="recommended_class_id" class="form-control">
                        <option value="">— Chưa xếp —</option>
                        @foreach($recommendClasses as $c)
                            <option value="{{ $c->id }}" @selected(old('recommended_class_id')==$c->id)>
                                {{ $c->name }}@if($c->code) ({{ $c->code }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Ngày test</label>
                    <input type="date" name="tested_at" class="form-control" value="{{ old('tested_at', now()->toDateString()) }}">
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
                <button class="btn btn-primary btn-sm btn-block">Lưu kết quả</button>
            </form>
        </div>
        @if($latest?->recommended_class_id && $lead->student_id)
            <a href="{{ route('admin.students.show', ['student' => $lead->student_id, 'tab' => 'classes']) }}" class="btn btn-outline-success btn-sm btn-block mt-2">
                Gắn HV vào lớp đề xuất →
            </a>
        @endif
    </div>
    <div class="col-lg-7 mb-3">
        <div class="table-responsive border rounded">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Môn</th>
                    <th>Điểm</th>
                    <th>Level</th>
                    <th>Lớp đề xuất</th>
                </tr>
                </thead>
                <tbody>
                @forelse($placementTests as $pt)
                    <tr>
                        <td>{{ optional($pt->tested_at)->format('d/m/Y') ?: '—' }}</td>
                        <td>{{ $pt->subject?->name ?: '—' }}</td>
                        <td>{{ $pt->score !== null ? $pt->score : '—' }}</td>
                        <td>{{ $pt->levelLabel() }}</td>
                        <td>
                            @if($pt->recommendedClass)
                                <a href="{{ route('admin.classes.show', $pt->recommendedClass) }}">{{ $pt->recommendedClass->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Chưa có bài test.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
