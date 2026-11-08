@extends('layouts.app')

@section('title', $material->title . ' | CryptoLab')

@section('meta_description', Str::limit(trim(preg_replace('/\s+/', ' ', (string) preg_replace('/<[^>]*>/', ' ', $material->content))), 155))

@section('topbar_title', $material->title)

@section('topbar_subtitle', $material->algorithm?->description ?? 'Materi pembelajaran kriptografi terpadu.')

@push('styles')
{{-- KaTeX CSS untuk merender formula LaTeX --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
@endpush

@section('content')

<div class="py-1">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('materials.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Daftar Materi
            </a>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item">
                        <span class="text-muted-custom">{{ $material->algorithm?->type ?? 'Umum' }}</span>
                    </li>
                    <li class="breadcrumb-item active text-main" aria-current="page">
                        {{ $material->title }}
                    </li>
                </ol>
            </nav>

            @if ($material->algorithm?->security_status === 'historical')
                <span class="badge text-bg-secondary"><i class="bi bi-hourglass-split me-1"></i>Historical</span>
            @elseif ($material->algorithm?->security_status === 'recommended')
                <span class="badge text-bg-success"><i class="bi bi-shield-check me-1"></i>Direkomendasikan</span>
            @endif
        </div>

        @if ($material->algorithm)
            <a href="{{ route('lab.index', ['algorithm' => $material->algorithm->slug]) }}"
               class="btn btn-neon btn-sm shadow-sm">
                <i class="bi bi-flask me-1"></i> Uji di Laboratorium
            </a>
        @endif

    </div>

    <div class="panel-card p-4 p-md-5" id="materialContent">

        {{-- Konten dibangun oleh seeder internal (HTML terkurasi), dirender apa adanya --}}
        @if (\Illuminate\Support\Str::contains($material->content, 'crypto-material'))
            {!! $material->content !!}
        @else
            <article class="crypto-material">
                {!! $material->content !!}
            </article>
        @endif

    </div>

</div>

@endsection

@push('scripts')
{{-- KaTeX JS & auto-render extension --}}
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>

<script>
(function () {
    function jalankanKatex() {
        if (typeof renderMathInElement !== 'function') {
            return false;
        }

        const target = document.getElementById('materialContent') || document.body;

        renderMathInElement(target, {
            delimiters: [
                { left: '$$', right: '$$', display: true },
                { left: '\\[', right: '\\]', display: true },
                { left: '$', right: '$', display: false },
                { left: '\\(', right: '\\)', display: false }
            ],
            ignoredTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code', 'option'],
            throwOnError: false
        });

        pasangTombolSalinKode();
        return true;
    }

    function pasangTombolSalinKode() {
        document.querySelectorAll('#materialContent pre').forEach(function (pre) {
            if (pre.querySelector('.btn-copy-code')) return;
            pre.style.position = 'relative';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2 py-0 px-2 btn-copy-code';
            btn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Salin';
            btn.style.fontSize = '0.75rem';

            btn.addEventListener('click', async function () {
                const code = pre.querySelector('code') ? pre.querySelector('code').innerText : pre.innerText;
                try {
                    await navigator.clipboard.writeText(code.trim());
                    btn.innerHTML = '<i class="bi bi-check-lg text-success me-1"></i>Tersalin!';
                    setTimeout(function () {
                        btn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Salin';
                    }, 1600);
                } catch (e) {}
            });

            pre.appendChild(btn);
        });
    }

    if (!jalankanKatex()) {
        const interval = setInterval(function () {
            if (jalankanKatex()) {
                clearInterval(interval);
            }
        }, 50);

        setTimeout(function () {
            clearInterval(interval);
        }, 4000);
    }
})();
</script>
@endpush
