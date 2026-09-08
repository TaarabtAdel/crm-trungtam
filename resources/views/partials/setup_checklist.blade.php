@php
    /** @var array $checklist */
    $checklist = $checklist ?? ['items' => [], 'percent' => 0, 'required_done' => 0, 'required_total' => 0, 'done' => 0, 'total' => 0];
    $showSuccess = $showSuccess ?? true;
    $compact = $compact ?? false;
@endphp

<div class="page-card mb-{{ $compact ? '0' : '4' }} setup-checklist">
    <div class="card-header-custom py-2">
        <div>
            <div class="font-weight-bold small mb-0">
                <i class="bi bi-list-check text-primary mr-1"></i> Checklist cài đặt
            </div>
            <small class="text-muted" style="font-size:.75rem">
                Bắt buộc {{ $checklist['required_done'] }}/{{ $checklist['required_total'] }}
                · Tất cả {{ $checklist['done'] }}/{{ $checklist['total'] }}
            </small>
        </div>
        <div class="d-flex align-items-center" style="min-width:120px;max-width:180px;width:100%">
            <div class="progress flex-grow-1 mr-2" style="height:6px">
                <div class="progress-bar {{ $checklist['percent'] >= 100 ? 'bg-success' : 'bg-primary' }}"
                     role="progressbar"
                     style="width: {{ (int) $checklist['percent'] }}%"
                     aria-valuenow="{{ (int) $checklist['percent'] }}"
                     aria-valuemin="0"
                     aria-valuemax="100"></div>
            </div>
            <span class="font-weight-bold text-muted" style="font-size:.75rem">
                {{ (int) $checklist['percent'] }}%
            </span>
        </div>
    </div>

    <div class="card-body-custom py-1 {{ $compact ? '' : '' }}" style="font-size:.8125rem">
        <div class="list-group list-group-flush mx-n3">
            @foreach($checklist['items'] as $item)
                @php
                    $done = ! empty($item['done']);
                    $optional = ! empty($item['optional']);
                @endphp
                <div class="list-group-item border-left-0 border-right-0 px-3 py-2 {{ $loop->first ? 'border-top-0' : '' }} {{ $loop->last ? 'border-bottom-0' : '' }}">
                    <div class="d-flex align-items-start">
                        <div class="mr-2" style="width:1.1rem;flex-shrink:0;line-height:1.4">
                            @if($done)
                                <i class="bi bi-check-circle-fill text-success" style="font-size:.95rem"></i>
                            @else
                                <i class="bi bi-circle text-muted" style="font-size:.95rem;opacity:.4"></i>
                            @endif
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-center" style="gap:.3rem">
                                @if(!empty($item['url']))
                                    <a href="{{ $item['url'] }}" class="{{ $done ? 'text-body' : 'font-weight-bold' }}" style="font-size:.8125rem">
                                        {{ $item['label'] }}
                                    </a>
                                @else
                                    <span class="{{ $done ? '' : 'font-weight-bold' }}" style="font-size:.8125rem">
                                        {{ $item['label'] }}
                                    </span>
                                @endif
                                @if($optional)
                                    <span class="badge badge-secondary" style="font-size:.65rem;font-weight:500">Tuỳ chọn</span>
                                @endif
                            </div>
                            @if(!empty($item['hint']))
                                <div class="text-muted mt-0" style="font-size:.72rem;line-height:1.35">
                                    {{ $item['hint'] }}
                                </div>
                            @endif
                        </div>
                        @if(!empty($item['url']) && ! $done)
                            <a href="{{ $item['url'] }}" class="btn btn-sm btn-outline-primary ml-2 flex-shrink-0 py-0 px-2" style="font-size:.7rem;line-height:1.6">
                                Mở
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($showSuccess && $checklist['percent'] >= 100)
            <div class="alert alert-success mb-2 mt-2 py-1 small">
                <i class="bi bi-check2-all"></i> Phần bắt buộc đã xong.
                <a href="{{ route('admin.guide', ['tab' => 'flow']) }}" class="alert-link font-weight-bold ml-1">
                    Xem quy trình →
                </a>
            </div>
        @endif
    </div>
</div>
