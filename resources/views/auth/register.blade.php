@extends('layouts.guest')

@section('title', 'Register | CryptoLab')

@section('content')

<div class="auth-wrapper">

    <div class="auth-card">

        <div class="text-center mb-4">

            <div class="auth-logo">
                <i class="bi bi-shield-check"></i>
            </div>

            <h3 class="fw-bold">
                Crypto<span class="text-neon">Lab</span>
            </h3>

            <p class="text-muted-custom small">
                Create Your Account
            </p>

        </div>

        <h5 class="fw-bold mb-1">Buat Akun Baru</h5>

        <p class="text-muted-custom small mb-4">
            Daftar untuk mulai mempelajari kriptografi.
        </p>

        @if($errors->any())
            <div class="alert alert-danger">

                <ul class="mb-0 ps-3">

                    @foreach($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>
        @endif

        <form action="{{ route('register.process') }}"
              method="POST">

            @csrf

            <div class="mb-3">

                <label class="form-label">Nama Lengkap</label>

                <input type="text"
                       name="name"
                       class="form-control"
                       placeholder="Masukkan nama lengkap"
                       value="{{ old('name') }}"
                       required>

            </div>

            <div class="mb-3">

                <label class="form-label">Email Address</label>

                <input type="email"
                       name="email"
                       class="form-control"
                       placeholder="nama@email.com"
                       value="{{ old('email') }}"
                       required>

            </div>

            <div class="mb-3">

                <label class="form-label">Password</label>

                <input type="password"
                       name="password"
                       class="form-control"
                       placeholder="Minimal 8 karakter"
                       required>

            </div>

            <div class="mb-4">

                <label class="form-label">
                    Konfirmasi Password
                </label>

                <input type="password"
                       name="password_confirmation"
                       class="form-control"
                       placeholder="Ulangi password"
                       required>

            </div>

            <button type="submit"
                    class="btn btn-neon w-100">

                <i class="bi bi-person-plus me-2"></i>
                Buat Akun

            </button>

        </form>

        <div class="text-center mt-4">

            <span class="text-muted-custom small">
                Sudah mempunyai akun?
            </span>

            <a href="{{ route('login') }}"
               class="text-neon small fw-bold">

                Login

            </a>

        </div>

    </div>

</div>

@endsection