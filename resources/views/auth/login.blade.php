@extends('layouts.guest')

@section('title', 'Login | CryptoLab')

@section('content')

<div class="auth-wrapper">

    <div class="auth-card">

        <div class="text-center mb-4">

            <div class="auth-logo">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <h3 class="fw-bold">
                Crypto<span class="text-neon">Lab</span>
            </h3>

            <p class="text-muted-custom small">
                Integrated Cryptography Learning System
            </p>

        </div>

        <h5 class="fw-bold mb-1">Selamat Datang!</h5>

        <p class="text-muted-custom small mb-4">
            Silakan login untuk mengakses CryptoLab.
        </p>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.process') }}" method="POST">

            @csrf

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
                       placeholder="Masukkan password"
                       required>

            </div>

            <div class="form-check mb-4">

                <input class="form-check-input"
                       type="checkbox"
                       name="remember"
                       value="1"
                       id="remember">

                <label class="form-check-label text-muted-custom small"
                       for="remember">

                    Ingat saya

                </label>

            </div>

            <button type="submit"
                    class="btn btn-neon w-100">

                <i class="bi bi-box-arrow-in-right me-2"></i>
                Login ke CryptoLab

            </button>

        </form>

        <div class="text-center mt-4">

            <span class="text-muted-custom small">
                Belum mempunyai akun?
            </span>

            <a href="{{ route('register') }}"
               class="text-neon small fw-bold">

                Daftar sekarang

            </a>

        </div>

        <div class="text-center mt-4">

            <small class="text-muted-custom">
                © {{ date('Y') }} CryptoLab
            </small>

        </div>

    </div>

</div>

@endsection