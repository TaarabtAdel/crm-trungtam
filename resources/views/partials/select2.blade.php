{{-- Select2 CDN + helper CRM (AJAX phân trang). Include 1 lần / trang qua @include. --}}
@once
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
<style>
/* Khớp Bootstrap 4 form-control trong modal */
.modal .select2-container { width: 100% !important; display: block; }
.select2-container--bootstrap4 .select2-selection--single {
    height: calc(1.5em + .75rem + 2px) !important;
    padding: .375rem .75rem !important;
    border: 1px solid #ced4da !important;
    border-radius: .25rem !important;
}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
    line-height: 1.5 !important;
    padding: 0 !important;
    color: #495057;
}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + .75rem) !important;
    top: 1px !important;
    right: 3px !important;
}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
    line-height: 1.5 !important;
    color: #6c757d;
}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__clear {
    margin-right: 1.5rem;
}
/* Dropdown nằm trong modal → z-index đủ cao */
.modal .select2-container--bootstrap4 .select2-dropdown,
.modal .select2-dropdown {
    z-index: 1060;
    border-color: #80bdff;
}
.modal .select2-container--bootstrap4.select2-container--focus .select2-selection--single,
.modal .select2-container--bootstrap4.select2-container--open .select2-selection--single {
    border-color: #80bdff !important;
    box-shadow: 0 0 0 .2rem rgba(0,123,255,.25);
}
.select2-container--bootstrap4 .select2-results__option--highlighted,
.select2-container--bootstrap4 .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #007bff;
    color: #fff;
}
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
window.crmSelect2Parent = function ($el, explicit) {
    if (explicit) return explicit;
    var $modal = $el.closest('.modal');
    if ($modal.length) {
        return $modal.find('.modal-content').first().length ? $modal.find('.modal-content').first() : $modal;
    }
    return $(document.body);
};

window.crmSelect2Ajax = function ($el, url, opts) {
    opts = opts || {};
    if (!$el || !$el.length) return;
    $el.each(function () {
        var $one = $(this);
        if ($one.hasClass('select2-hidden-accessible')) {
            $one.select2('destroy');
        }
        var extraData = opts.data || {};
        $one.select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: !!opts.allowClear,
            placeholder: opts.placeholder || $one.data('placeholder') || 'Tìm kiếm...',
            dropdownParent: window.crmSelect2Parent($one, opts.dropdownParent),
            minimumInputLength: opts.minimumInputLength || 0,
            ajax: {
                url: url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return Object.assign({
                        q: params.term || '',
                        page: params.page || 1
                    }, typeof extraData === 'function' ? extraData() : extraData);
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: (data && data.results) ? data.results : [],
                        pagination: { more: !!(data && data.pagination && data.pagination.more) }
                    };
                },
                cache: true
            },
            language: {
                noResults: function () { return 'Không tìm thấy'; },
                searching: function () { return 'Đang tìm...'; },
                inputTooShort: function () { return 'Nhập để tìm...'; },
                loadingMore: function () { return 'Đang tải thêm...'; }
            }
        });
    });
};

window.crmSelect2Local = function ($el, opts) {
    opts = opts || {};
    if (!$el || !$el.length) return;
    $el.each(function () {
        var $one = $(this);
        if ($one.hasClass('select2-hidden-accessible')) {
            $one.select2('destroy');
        }
        $one.select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: !!opts.allowClear,
            placeholder: opts.placeholder || $one.data('placeholder') || 'Chọn...',
            dropdownParent: window.crmSelect2Parent($one, opts.dropdownParent),
            language: {
                noResults: function () { return 'Không tìm thấy'; },
                searching: function () { return 'Đang tìm...'; }
            }
        });
    });
};
</script>
@endpush
@endonce
