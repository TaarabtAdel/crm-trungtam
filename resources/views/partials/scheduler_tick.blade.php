{{-- Nhắc lịch ngầm khi có user đăng nhập (admin hoặc bàn làm việc /home). --}}
<script>
(function () {
    var url = @json(route('admin.scheduler.tick'));
    var storageKey = 'crm_scheduler_tick_at';
    var minIntervalMs = 60 * 1000;
    var inFlight = false;

    function csrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    function shouldTick() {
        try {
            var last = parseInt(localStorage.getItem(storageKey) || '0', 10);
            return !last || (Date.now() - last) >= minIntervalMs;
        } catch (e) {
            return true;
        }
    }

    function markTick() {
        try {
            localStorage.setItem(storageKey, String(Date.now()));
        } catch (e) {}
    }

    function runTick() {
        if (inFlight || !shouldTick()) {
            return;
        }
        inFlight = true;
        var token = csrfToken();
        var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var timeoutId = controller ? setTimeout(function () { controller.abort(); }, 55000) : null;

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: '_token=' + encodeURIComponent(token),
            signal: controller ? controller.signal : undefined
        }).then(function (res) {
            if (res.ok) {
                markTick();
            } else {
                try { localStorage.removeItem(storageKey); } catch (e) {}
            }
        }).catch(function () {
            try { localStorage.removeItem(storageKey); } catch (e) {}
        }).finally(function () {
            if (timeoutId) clearTimeout(timeoutId);
            inFlight = false;
        });
    }

    setTimeout(runTick, 3000);
    setInterval(runTick, minIntervalMs);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            runTick();
        }
    });
})();
</script>
