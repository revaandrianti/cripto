@php
    $modules = [
        'block-cipher' => ['bi-box', 'Block Cipher'],
        'stream-cipher' => ['bi-lightning', 'Stream Cipher'],
        'fungsi-utama' => ['bi-shield-check', 'Fungsi Utama'],
        'dasar-matematika' => ['bi-calculator', 'Dasar Matematika'],
    ];

    $currentCategory = request()->route('category');
@endphp

<aside class="sidebar" id="sidebar">

    <a href="{{ route('dashboard') }}" class="brand">

        <div class="brand-icon">
            <i class="bi bi-shield-lock-fill"></i>
        </div>

        Crypto<span class="text-neon">Lab</span>

    </a>

    <div class="sidebar-label">
        Main menu
    </div>

    <a href="{{ route('dashboard') }}"
       class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">

        <i class="bi bi-grid"></i>
        Dashboard

    </a>

    <div class="sidebar-label">
        Cryptography
    </div>

    @foreach ($modules as $slug => [$icon, $label])

        <a href="{{ route('materials.category', $slug) }}"
           class="sidebar-link {{ ($currentCategory === $slug) ? 'active' : '' }}">

            <i class="bi {{ $icon }}"></i>
            {{ $label }}

        </a>

    @endforeach

    <div class="sidebar-label">
        Management
    </div>

    <a href="{{ route('riwayat') }}"
       class="sidebar-link {{ request()->routeIs('riwayat') ? 'active' : '' }}">

        <i class="bi bi-clock-history"></i>
        Riwayat Aktivitas

    </a>

    <a href="{{ route('akun') }}"
       class="sidebar-link {{ request()->routeIs('akun') ? 'active' : '' }}">

        <i class="bi bi-people"></i>
        Daftar Akun

    </a>

    <a href="{{ route('materials.index') }}"
       class="sidebar-link {{ request()->routeIs('materials.index') ? 'active' : '' }}">

        <i class="bi bi-book"></i>
        Semua Materi

    </a>

    <a href="{{ route('lab.index') }}"
       class="sidebar-link {{ request()->routeIs('lab.*') ? 'active' : '' }}">

        <i class="bi bi-clipboard-check"></i>
        Pengujian Algoritma

    </a>

    <div class="sidebar-label">
        Account
    </div>

    <form action="{{ route('logout') }}" method="POST">

        @csrf

        <button type="submit" class="sidebar-link w-100">

            <i class="bi bi-box-arrow-left"></i>
            Logout

        </button>

    </form>

</aside>
