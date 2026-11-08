@php
    $topbarTitle = trim($__env->yieldContent('topbar_title')) ?: 'CryptoLab';
    $topbarSubtitle = trim($__env->yieldContent('topbar_subtitle'));
    $userName = auth()->user()->name ?? 'Pengguna';
@endphp

<div class="topbar">

    <div class="d-flex align-items-center gap-3">

        <button class="btn btn-outline-secondary mobile-toggle"
                onclick="document.getElementById('sidebar').classList.toggle('show')"
                aria-label="Buka menu navigasi">

            <i class="bi bi-list"></i>

        </button>

        <div>

            <h4 class="text-main">{{ $topbarTitle }}</h4>

            @if ($topbarSubtitle !== '')
                <p class="text-muted-custom small mb-0">{{ $topbarSubtitle }}</p>
            @endif

        </div>

    </div>

    <div class="d-flex align-items-center gap-2">

        <button type="button" class="theme-toggle" id="themeToggle" aria-label="Ganti tema">
            <i class="bi bi-moon-stars" id="themeIcon"></i>
        </button>

        <div class="text-end d-none d-sm-block me-1">
            <div class="fw-semibold small">{{ $userName }}</div>
        </div>

        <div class="user-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($userName, 0, 1)) }}</div>

    </div>

</div>

<script>
    (function () {
        var html = document.documentElement;
        var icon = document.getElementById('themeIcon');

        function render() {
            var light = html.getAttribute('data-bs-theme') === 'light';
            icon.className = light ? 'bi bi-sun' : 'bi bi-moon-stars';
        }

        render();

        document.getElementById('themeToggle')
            .addEventListener('click', function () {
                var next = html.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
                html.setAttribute('data-bs-theme', next);
                try {
                    localStorage.setItem('cryptolab-theme', next);
                } catch (e) {}
                render();
            });
    })();
</script>
