@extends('layouts.app')

@section('title', 'Dashboard | CryptoLab')

@section('topbar_title', 'Dashboard')
@section('topbar_subtitle', 'Ringkasan CryptoLab')

@section('content')


        <!-- STATISTICS -->

        <div class="row g-3 mb-4">

            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-grid"></i>
                    </div>

                    <p class="text-muted-custom small mb-1">
                        Kategori
                    </p>

                    <h3 class="fw-bold mb-1">
                        {{ $categories->count() }}
                    </h3>

                    <small class="text-muted-custom">
                        <i class="bi bi-layers me-1"></i>Modul utama
                    </small>

                </div>

            </div>


            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-cpu"></i>
                    </div>

                    <p class="text-muted-custom small mb-1">
                        Algoritma
                    </p>

                    <h3 class="fw-bold mb-1">
                        {{ $algorithmCount }}
                    </h3>

                    <small class="text-neon">
                        <i class="bi bi-shield-check me-1"></i>Metode aktif
                    </small>

                </div>

            </div>


            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-book"></i>
                    </div>

                    <p class="text-muted-custom small mb-1">
                        Materi
                    </p>

                    <h3 class="fw-bold mb-1">
                        {{ $materialCount }}
                    </h3>

                    <small class="text-muted-custom">
                        <i class="bi bi-journal-text me-1"></i>Artikel teori
                    </small>

                </div>

            </div>


            <div class="col-6 col-xl-3">

                <div class="stat-card stat-success">

                    <div class="stat-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>

                    <p class="text-muted-custom small mb-1">
                        Aktivitas Saya
                    </p>

                    <h3 class="fw-bold mb-1">
                        {{ $historyCount }}
                    </h3>

                    <small class="text-neon">
                        <i class="bi bi-arrow-repeat me-1"></i>Riwayat pengujian
                    </small>

                </div>

            </div>

        </div>


        <!-- CATEGORY -->

        <div class="mb-3">

            <h5 class="fw-bold">
                Modul Kriptografi
            </h5>

            <p class="text-muted-custom small">
                Pilih kategori untuk mempelajari algoritma.
            </p>

        </div>


        <div class="row g-3 mb-4">

            @php
                $icons = [
                    'block-cipher' => 'bi-box',
                    'stream-cipher' => 'bi-lightning',
                    'fungsi-utama' => 'bi-shield-check',
                    'dasar-matematika' => 'bi-calculator'
                ];

                $anchors = [
                    'block-cipher' => 'block-cipher',
                    'stream-cipher' => 'stream-cipher',
                    'fungsi-utama' => 'fungsi-utama',
                    'dasar-matematika' => 'dasar-matematika'
                ];
            @endphp

            @foreach($categories as $category)

                <div class="col-md-6 col-xl-3"
                     id="{{ $anchors[$category->slug] ?? '' }}">

                    <a href="{{ route('materials.category', $category->slug) }}"
                       class="text-decoration-none">

                        <div class="category-card">

                            <div class="category-icon">

                                <i class="bi {{ $icons[$category->slug] ?? 'bi-shield' }}"></i>

                            </div>

                            <h6 class="card-title-custom">
                                {{ $category->name }}
                            </h6>

                            <p class="card-description">
                                {{ $category->description }}
                            </p>

                            <div class="d-flex justify-content-between align-items-center">

                                <span class="text-neon small">
                                    Pelajari materi
                                </span>

                                <i class="bi bi-arrow-right category-arrow"></i>

                            </div>

                        </div>

                    </a>

                </div>

            @endforeach

        </div>


        <!-- RECENT ACTIVITIES -->

        <div class="panel-card mb-4" id="aktivitas">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h6 class="fw-bold mb-0">
                    Aktivitas Terbaru
                </h6>

                <a href="{{ route('riwayat') }}" class="small text-decoration-none text-neon">
                    Lihat semua <i class="bi bi-arrow-right"></i>
                </a>

            </div>

            <div class="table-responsive">

                <table class="table table-dark-custom align-middle">

                    <thead>

                        <tr>
                            <th>Algoritma</th>
                            <th>Operasi</th>
                            <th>Status</th>
                            <th>Waktu</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($recentActivities as $activity)

                            @php
                                $dt = \Illuminate\Support\Carbon::parse($activity->created_at);
                            @endphp

                            <tr>

                                <td>
                                    <span class="fw-semibold">{{ $activity->algorithm_name ?? 'Matematika' }}</span>
                                </td>

                                <td>
                                    <span class="badge badge-op">
                                        {{ $activity->operation }}
                                    </span>
                                </td>

                                <td>
                                    @if ($activity->result_status === 'success')
                                        <span class="badge badge-status-success">
                                            <i class="bi bi-check2 me-1"></i>berhasil
                                        </span>
                                    @else
                                        <span class="badge badge-status-danger">
                                            <i class="bi bi-x-lg me-1"></i>gagal
                                        </span>
                                    @endif
                                </td>

                                <td class="small text-nowrap">
                                    <span class="fw-semibold text-main">{{ $dt->format('d M Y') }}</span>
                                    <span class="text-muted-custom font-monospace ms-1" style="font-size: 0.78rem;">{{ $dt->format('H:i') }}</span>
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="4"
                                    class="text-center text-muted-custom py-4">

                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>

                                    Belum ada aktivitas.
                                    Silakan gunakan modul kriptografi.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        <!-- FOOTER -->

        <div class="text-center text-muted-custom small py-3">

            © {{ date('Y') }} CryptoLab.
            Integrated Cryptography Learning System.

        </div>

    

@endsection

