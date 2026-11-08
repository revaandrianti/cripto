@extends('layouts.app')

@section('title', 'Riwayat Aktivitas | CryptoLab')

@section('topbar_title', 'Riwayat Aktivitas')

@section('topbar_subtitle', 'Semua operasi kriptografi yang pernah Anda jalankan, lengkap dengan pesan dan alasan kegagalan.')

@section('content')

<div class="py-1">

    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-4">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="bi bi-clock-history"></i>
                </div>

                <p class="text-muted-custom small mb-1">Total</p>

                <h3 class="fw-bold mb-1">{{ $ringkasan['semua'] }}</h3>

                <small class="text-muted-custom"><i class="bi bi-cpu me-1"></i>Operasi tercatat</small>

            </div>

        </div>

        <div class="col-12 col-sm-4">

            <div class="stat-card stat-success">

                <div class="stat-icon">
                    <i class="bi bi-check2-circle"></i>
                </div>

                <p class="text-muted-custom small mb-1">Berhasil</p>

                <h3 class="fw-bold mb-1">{{ $ringkasan['success'] }}</h3>

                <small class="text-neon"><i class="bi bi-shield-check me-1"></i>Operasi normal</small>

            </div>

        </div>

        <div class="col-12 col-sm-4">

            <div class="stat-card stat-danger">

                <div class="stat-icon">
                    <i class="bi bi-x-octagon"></i>
                </div>

                <p class="text-muted-custom small mb-1">Gagal</p>

                <h3 class="fw-bold mb-1">{{ $ringkasan['failed'] }}</h3>

                <small class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Perlu diperhatikan</small>

            </div>

        </div>

    </div>

    <div class="panel-card mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-list-columns-reverse text-neon"></i>
                <h6 class="fw-bold mb-0">Detail Aktivitas</h6>
            </div>

            <div class="filter-chips" role="group" aria-label="Filter status">

                @php
                    $filterIcons = [
                        'semua' => 'bi-collection',
                        'success' => 'bi-check2-circle',
                        'failed' => 'bi-x-octagon',
                    ];
                @endphp

                @foreach (['semua' => 'Semua', 'success' => 'Berhasil', 'failed' => 'Gagal'] as $kunci => $label)

                    <a href="{{ route('riwayat', ['status' => $kunci]) }}"
                       class="filter-chip {{ $status === $kunci ? 'active' : '' }}">
                        <i class="bi {{ $filterIcons[$kunci] }} me-1"></i>
                        {{ $label }}
                    </a>

                @endforeach

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-dark-custom align-middle">

                <thead>

                    <tr>
                        <th style="min-width: 140px;">Waktu</th>
                        <th>Algoritma</th>
                        <th>Operasi</th>
                        <th>Status</th>
                        <th>Pesan / Ciphertext</th>
                        <th>Keterangan</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($aktivitas as $activity)

                        @php
                            $dt = \Illuminate\Support\Carbon::parse($activity->created_at);
                            $msgRaw = (string) ($activity->message_preview ?? '');
                            $msgLen = strlen($msgRaw);
                            $msgShort = $msgLen > 24 ? substr($msgRaw, 0, 10) . '…' . substr($msgRaw, -8) : $msgRaw;
                        @endphp

                        <tr>

                            <td class="small">
                                <div class="fw-semibold text-main">{{ $dt->format('d M Y') }}</div>
                                <div class="text-muted-custom font-monospace" style="font-size: 0.78rem;">
                                    <i class="bi bi-clock me-1"></i>{{ $dt->format('H:i:s') }}
                                </div>
                            </td>

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

                            <td>
                                @if ($msgRaw !== '')
                                    <div class="code-preview-box" title="{{ $msgRaw }}">
                                        <span class="text-truncate user-select-all" style="max-width: 170px;">{{ $msgShort }}</span>
                                        <button type="button" class="btn-copy-mini" data-copy="{{ $msgRaw }}" title="Salin seluruh teks">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-muted-custom">—</span>
                                @endif
                            </td>

                            <td class="small">
                                @if ($activity->result_status === 'failed' && $activity->error_message)
                                    <span class="badge-status-danger px-2 py-1 rounded small d-inline-block text-break" style="max-width: 260px;">
                                        <i class="bi bi-info-circle me-1"></i>{{ $activity->error_message }}
                                    </span>
                                @else
                                    <span class="text-muted-custom">—</span>
                                @endif
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="text-center text-muted-custom py-5">

                                <i class="bi bi-inbox fs-2 d-block mb-2 text-muted-custom"></i>

                                <div class="fw-semibold mb-1">Belum ada riwayat aktivitas</div>
                                <div class="small">Uji algoritma kriptografi di laboratorium untuk melihat jejak komputasinya di sini.</div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($aktivitas->hasPages())
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top border-secondary border-opacity-10 gap-2">
                <div class="small text-muted-custom">
                    Menampilkan <strong>{{ $aktivitas->firstItem() }}</strong> - <strong>{{ $aktivitas->lastItem() }}</strong> dari <strong>{{ $aktivitas->total() }}</strong> operasi
                </div>
                <div>
                    {{ $aktivitas->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif

    </div>

    <div class="text-center text-muted-custom small py-3">

        © {{ date('Y') }} CryptoLab.
        Integrated Cryptography Learning System.

    </div>

</div>

@push('scripts')
<script>
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-copy-mini');
    if (!btn) return;

    const text = btn.getAttribute('data-copy');
    if (!text) return;

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-999999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }

        const icon = btn.querySelector('i');
        if (icon) {
            btn.classList.add('copied');
            icon.className = 'bi bi-check-lg text-success';
            setTimeout(() => {
                btn.classList.remove('copied');
                icon.className = 'bi bi-clipboard';
            }, 1500);
        }
    } catch (err) {
        console.error('Gagal menyalin:', err);
    }
});
</script>
@endpush

@endsection
