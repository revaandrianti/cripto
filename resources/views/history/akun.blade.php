@extends('layouts.app')

@section('title', 'Daftar Akun | CryptoLab')

@section('topbar_title', 'Daftar Akun')

@section('topbar_subtitle', 'Siapa saja yang sudah memiliki akun di CryptoLab.')

@section('content')

<div class="py-1">

    <div class="panel-card mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h6 class="fw-bold mb-0">Pengguna Terdaftar</h6>

            <span class="badge text-bg-secondary">{{ $pengguna->total() }} akun</span>

        </div>

        <div class="table-responsive">

            <table class="table table-dark-custom align-middle">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Bergabung</th>
                        <th>Aktivitas</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($pengguna as $user)

                        <tr>

                            <td class="text-muted-custom">
                                {{ $pengguna->firstItem() + $loop->index }}
                            </td>

                            <td>

                                <div class="d-flex align-items-center gap-2">

                                    <div class="user-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}</div>

                                    <span class="fw-semibold">
                                        {{ $user->name }}
                                        @if ($user->id === auth()->id())
                                            <small class="text-neon">(Anda)</small>
                                        @endif
                                    </span>

                                </div>

                            </td>

                            <td class="small">
                                @if ($user->id === auth()->id())
                                    {{ $user->email }}
                                @else
                                    {{ \Illuminate\Support\Str::mask($user->email, '*', 2, max(1, (int) strpos($user->email, '@') - 2)) }}
                                @endif
                            </td>

                            <td class="small text-nowrap">
                                {{ \Illuminate\Support\Carbon::parse($user->created_at)->format('d M Y H:i') }}
                            </td>

                            <td>
                                <span class="badge {{ $user->aktivitas > 0 ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $user->aktivitas }} operasi
                                </span>
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5" class="text-center text-muted-custom py-4">

                                <i class="bi bi-people fs-3 d-block mb-2"></i>

                                Belum ada pengguna terdaftar.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($pengguna->hasPages())
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top border-secondary border-opacity-10 gap-2">
                <div class="small text-muted-custom">
                    Menampilkan <strong>{{ $pengguna->firstItem() }}</strong> - <strong>{{ $pengguna->lastItem() }}</strong> dari <strong>{{ $pengguna->total() }}</strong> pengguna
                </div>
                <div>
                    {{ $pengguna->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif

    </div>

    <div class="text-center text-muted-custom small py-3">

        © {{ date('Y') }} CryptoLab.
        Integrated Cryptography Learning System.

    </div>

</div>

@endsection
