<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'CryptoLab')</title>

    @include('layouts.partials.seo')

    <script>
        // Terapkan tema sebelum render agar tidak terjadi kedipan.
        (function () {
            var theme = 'dark';
            try {
                theme = localStorage.getItem('cryptolab-theme') || 'dark';
            } catch (e) {}
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet"
          href="{{ asset('css/cryptolab.css') }}">

    @stack('styles')
</head>
<body>

    <div class="app-wrapper">

        @include('layouts.partials.sidebar')

        <main class="main-content">

            @include('layouts.partials.topbar')

            @yield('content')

        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')

</body>
</html>
