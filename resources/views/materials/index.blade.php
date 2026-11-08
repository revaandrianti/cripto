@extends('layouts.app')

@section('title', ($activeCategory ? $activeCategory->name . ' | ' : '') . 'Materi Kriptografi | CryptoLab')

@section('topbar_title', $activeCategory ? $activeCategory->name : 'Materi Kriptografi')

@section('topbar_subtitle', $activeCategory
    ? $activeCategory->description
    : 'Algoritma dan konsep keamanan informasi dari empat modul inti.')

@section('content')

@php
    $chips = [
        'block-cipher' => ['bi-box', 'Block Cipher'],
        'stream-cipher' => ['bi-lightning-charge', 'Stream Cipher'],
        'fungsi-utama' => ['bi-shield-check', 'Fungsi Utama'],
        'dasar-matematika' => ['bi-calculator', 'Dasar Matematika'],
    ];

    $excerpt = fn (string $html) => Str::limit(
        trim(preg_replace('/\s+/', ' ', (string) preg_replace('/<[^>]*>/', ' ', $html))),
        130,
    );
@endphp

<div class="py-1">

    {{-- Filter modul chips --}}
    <div class="filter-chips mb-4" role="tablist" aria-label="Filter modul kriptografi">

        <a href="{{ route('materials.index') }}"
           class="filter-chip {{ !$activeCategory ? 'active' : '' }}">
            <i class="bi bi-grid-fill me-1"></i> Semua
        </a>

        @foreach ($chips as $slug => [$icon, $label])

            <a href="{{ route('materials.category', $slug) }}"
               class="filter-chip {{ ($activeCategory?->slug === $slug) ? 'active' : '' }}">
                <i class="bi {{ $icon }} me-1"></i> {{ $label }}
            </a>

        @endforeach

    </div>

    {{-- Search & Counter Bar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">

        <div class="search-box-wrapper" style="max-width: 360px;">
            <i class="bi bi-search search-icon"></i>
            <input
                type="search"
                id="searchMaterial"
                class="form-control search-input-seamless"
                placeholder="Cari materi atau algoritma..."
                aria-label="Cari materi"
                autocomplete="off"
            >
            <button class="btn-clear-search d-none" type="button" id="clearSearch" title="Hapus pencarian">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-secondary px-3 py-2">
                <i class="bi bi-book me-1"></i>
                <span id="materialCount">{{ $materials->count() }}</span> materi
                @if ($activeCategory)
                    di {{ $activeCategory->name }}
                @endif
            </span>
        </div>

    </div>

    {{-- Grid Kartu Materi --}}
    <div class="row g-3" id="materialList">

        @forelse($materials as $material)

            @php
                $tipe = $material->algorithm?->type ?? 'Kriptografi';
                $badgeClass = match(strtolower($tipe)) {
                    'matematika', 'dasar matematika' => 'text-bg-info text-info-emphasis',
                    'block cipher' => 'text-bg-success',
                    'stream cipher' => 'text-bg-warning',
                    'fungsi utama', 'hash', 'mac' => 'text-bg-primary',
                    default => 'text-bg-secondary'
                };
            @endphp

            <div class="col-md-6 col-xl-4 material-card">
                <div class="card shadow-sm h-100 position-relative">
                    <div class="card-body p-4 d-flex flex-column">

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge {{ $badgeClass }} align-self-start">
                                {{ $tipe }}
                            </span>
                            @if ($material->algorithm?->security_status === 'recommended')
                                <small class="text-neon" title="Algoritma direkomendasikan"><i class="bi bi-shield-check"></i></small>
                            @endif
                        </div>

                        <h5 class="card-title fw-bold text-main mb-2">
                            {{ $material->title }}
                        </h5>

                        <p class="material-excerpt mb-3 flex-grow-1">
                            {{ $excerpt($material->content) }}
                        </p>

                        <a href="{{ route('materials.show', $material->slug) }}"
                           class="btn btn-outline-primary w-100 d-flex justify-content-between align-items-center stretched-link mt-auto">
                            <span>Pelajari materi</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>
                </div>
            </div>

        @empty

            <div class="col-12 text-center text-muted-custom py-5">
                <i class="bi bi-journal-x fs-1 d-block mb-3 text-muted-custom"></i>
                <h6 class="fw-semibold mb-1">Materi Tidak Ditemukan</h6>
                <p class="small text-muted-custom mb-3">Tidak ada materi di kategori ini. Silakan pilih modul lain.</p>
                <a href="{{ route('materials.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Semua Materi
                </a>
            </div>

        @endforelse

    </div>

    {{-- State saat pencarian lokal kosong --}}
    <div id="noSearchResults" class="text-center text-muted-custom py-5 d-none">
        <i class="bi bi-search fs-2 d-block mb-2 text-muted-custom"></i>
        <h6 class="fw-semibold mb-1">Tidak ada materi yang cocok</h6>
        <p class="small text-muted-custom mb-3">Coba gunakan kata kunci lain seperti nama algoritma atau konsep.</p>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnResetSearch">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Pencarian
        </button>
    </div>

</div>

@push('scripts')
<script>
(function () {
    const searchInput = document.getElementById('searchMaterial');
    const clearBtn = document.getElementById('clearSearch');
    const resetBtn = document.getElementById('btnResetSearch');
    const noResults = document.getElementById('noSearchResults');
    const countEl = document.getElementById('materialCount');
    const cards = document.querySelectorAll('.material-card');
    const totalCount = cards.length;

    function filterMaterials() {
        const keyword = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        if (clearBtn) {
            clearBtn.classList.toggle('d-none', keyword === '');
        }

        cards.forEach(card => {
            const matches = keyword === '' || card.innerText.toLowerCase().includes(keyword);
            card.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        if (countEl) {
            countEl.textContent = visibleCount;
        }

        if (noResults) {
            noResults.classList.toggle('d-none', visibleCount > 0 || totalCount === 0);
        }
    }

    searchInput?.addEventListener('input', filterMaterials);

    function resetSearch() {
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
            filterMaterials();
        }
    }

    clearBtn?.addEventListener('click', resetSearch);
    resetBtn?.addEventListener('click', resetSearch);
})();
</script>
@endpush

@endsection
