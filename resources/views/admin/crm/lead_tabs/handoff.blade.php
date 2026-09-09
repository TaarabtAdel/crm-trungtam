@php
    $items = [
        ['done' => $handoff['has_student'], 'label' => 'Tạo học viên từ lead', 'hint' => $handoff['has_student'] ? 'HV #'.$handoff['student']?->id : 'Chốt lead → Đưa vào danh sách HV'],
        ['done' => $handoff['has_placement'], 'label' => 'Test đầu vào / xếp level', 'hint' => $handoff['interest_subject'] ? 'Quan tâm: '.$handoff['interest_subject'] : 'Ghi kết quả ở tab Test đầu vào'],
        ['done' => $handoff['enrolled'], 'label' => 'Gắn học viên vào lớp', 'hint' => $handoff['enrolled'] ? $handoff['class_count'].' lớp' : 'Mở hồ sơ HV → tab Lớp học'],
        ['done' => $handoff['has_invoice'], 'label' => 'Tạo hóa đơn học phí', 'hint' => $handoff['has_invoice'] ? $handoff['invoice_count'].' HĐ' : 'Hóa đơn từ lớp hoặc menu Hóa đơn'],
    ];
@endphp

<div class="mb-3">
    <h6 class="font-weight-bold mb-1">Checklist bàn giao Sales → Đào tạo / Kế toán</h6>
    <p class="small text-muted mb-0">Sau khi chốt: tạo HV → test (nếu cần) → gắn lớp → thu học phí.</p>
</div>

<div class="list-group mb-3">
    @foreach($items as $i => $item)
        <div class="list-group-item d-flex align-items-start">
            <span class="mr-3 mt-1">
                @if($item['done'])
                    <i class="bi bi-check-circle-fill text-success" style="font-size:1.25rem"></i>
                @else
                    <i class="bi bi-circle text-muted" style="font-size:1.25rem"></i>
                @endif
            </span>
            <div>
                <div class="font-weight-bold">{{ $i + 1 }}. {{ $item['label'] }}</div>
                <div class="small text-muted">{{ $item['hint'] }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex flex-wrap" style="gap:.5rem">
    @if(! $handoff['has_student'] && $lead->status === 'won')
        @canPerm('students.manage')
        <form method="POST" action="{{ route('admin.leads.convert-student', $lead) }}">
            @csrf
            <button class="btn btn-primary btn-sm">Tạo học viên ngay</button>
        </form>
        @endcanPerm
    @endif
    @if($handoff['student'])
        <a href="{{ route('admin.students.show', $handoff['student']) }}" class="btn btn-outline-primary btn-sm">Hồ sơ HV</a>
        <a href="{{ route('admin.students.show', ['student' => $handoff['student'], 'tab' => 'classes']) }}" class="btn btn-outline-secondary btn-sm">Gắn lớp</a>
        @canPerm('finance.invoices.manage')
        <a href="{{ route('admin.students.show', ['student' => $handoff['student'], 'tab' => 'tuition']) }}" class="btn btn-outline-secondary btn-sm">Học phí / HĐ</a>
        @endcanPerm
    @endif
    <a href="{{ route('admin.leads.show', ['lead' => $lead, 'tab' => 'placement']) }}" class="btn btn-outline-info btn-sm">Test đầu vào</a>
</div>
