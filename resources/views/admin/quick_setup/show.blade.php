@extends('layouts.admin')

@section('title', 'Cài đặt nhanh')

@section('content')
@php
    $checklistById = collect($checklist['items'])->keyBy('id');
@endphp

<div class="quick-setup-page">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h4 class="mb-1 font-weight-bold">Cài đặt nhanh</h4>
            <p class="text-muted mb-0 small">Wizard từng bước — điền form, lưu và chuyển bước. Checklist bên phải cập nhật theo dữ liệu thực.</p>
        </div>
        <a href="{{ route('admin.guide', ['tab' => 'setup']) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-book"></i> Hướng dẫn chi tiết
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="page-card mb-3">
                <div class="card-body-custom">
                    <div class="quick-setup-stepper mb-3">
                        @foreach($steps as $i => $s)
                            @php
                                $stepDone = collect($s['checklist_ids'])->every(function ($id) use ($checklistById) {
                                    $item = $checklistById->get($id);
                                    return ! $item || $item['done'] || ! empty($item['optional']);
                                });
                                if ($s['key'] === 'done') {
                                    $stepDone = $checklist['percent'] >= 100;
                                }
                            @endphp
                            <a href="{{ route('admin.quick-setup.show', ['step' => $s['key']]) }}"
                               class="quick-setup-step {{ $step === $s['key'] ? 'is-active' : '' }} {{ $stepDone ? 'is-done' : '' }}">
                                <span class="quick-setup-step-num">
                                    @if($stepDone && $s['key'] !== 'done')
                                        <i class="bi bi-check-lg"></i>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </span>
                                <span class="quick-setup-step-title">{{ $s['title'] }}</span>
                                @if(!empty($s['optional']))
                                    <span class="badge badge-light border">Tuỳ chọn</span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    @if($step === 'done')
                        @include('admin.quick_setup.steps.done')
                    @else
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 font-weight-bold">
                                Bước: {{ $stepMeta['title'] ?? $step }}
                                @if(!empty($stepMeta['optional']))
                                    <span class="badge badge-secondary">Tuỳ chọn</span>
                                @endif
                            </h5>
                            <div class="small text-muted">
                                @if($prevKey)
                                    <a href="{{ route('admin.quick-setup.show', ['step' => $prevKey]) }}">← Quay lại</a>
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.quick-setup.store', ['step' => $step]) }}">
                            @csrf
                            @include('admin.quick_setup.steps.'.$step)

                            <div class="d-flex flex-wrap align-items-center mt-3" style="gap:.5rem">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check2"></i> Lưu &amp; tiếp tục
                                </button>
                                @if(!empty($stepMeta['optional']))
                                    <button type="submit" name="skip" value="1" class="btn btn-outline-secondary">Bỏ qua bước này</button>
                                @endif
                                @if($nextKey)
                                    <a href="{{ route('admin.quick-setup.show', ['step' => $nextKey]) }}" class="btn btn-link">Chỉ chuyển bước →</a>
                                @endif
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="sticky-top" style="top:1rem">
                @include('partials.setup_checklist', ['checklist' => $checklist, 'compact' => true])
                <p class="small text-muted mt-2 mb-0">
                    Tick xanh + gạch ngang = đã xong theo dữ liệu hiện tại. Bấm mục checklist để nhảy đúng bước.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
