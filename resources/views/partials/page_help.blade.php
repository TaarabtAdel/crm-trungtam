{{--
  Reusable help modal (popup).
  Props: $modalId, $title, $items (array of ['title'=>, 'body'=> html string])
--}}
@php
    $helpTitle = $title ?? 'Hướng dẫn sử dụng';
    $helpItems = $items ?? [];
    $helpModalId = $modalId ?? 'helpGuideModal';
@endphp

<div class="modal fade" id="{{ $helpModalId }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $helpTitle }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                @foreach($helpItems as $item)
                    <div class="mb-3 page-help-body">
                        <h6 class="font-weight-bold text-dark mb-2">{{ $item['title'] }}</h6>
                        <div class="small text-muted">{!! $item['body'] !!}</div>
                    </div>
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
