{{-- ============================================================
   FAVICON & ICONS
   ============================================================ --}}
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=2">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=2">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=2">

{{-- ============================================================
   METADATA SEO DASAR
   ============================================================ --}}
<meta name="description" content="@yield('meta_description', 'CryptoLab adalah platform pembelajaran dan laboratorium kriptografi interaktif terpadu. Dilengkapi 60+ algoritma, pengujian real-time, visualisasi jejak per karakter, dan skema Hybrid Trio Encrypt-then-MAC.')">
<meta name="keywords" content="kriptografi, cipher, block cipher, stream cipher, aes, des, rsa, ecc, chacha20, hybrid trio, hashing, sha256, enkripsi, dekripsi, matematika diskrit, cryptolab">
<meta name="author" content="CryptoLab Development Team">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#080d18">
<link rel="canonical" href="{{ url()->current() }}">

{{-- ============================================================
   OPEN GRAPH (Facebook, WhatsApp, LinkedIn, Discord)
   ============================================================ --}}
<meta property="og:locale" content="id_ID">
<meta property="og:type" content="website">
<meta property="og:site_name" content="CryptoLab">
<meta property="og:title" content="@yield('title', 'CryptoLab | Integrated Cryptography Learning System')">
<meta property="og:description" content="@yield('meta_description', 'Platform pembelajaran dan laboratorium kriptografi interaktif terpadu dengan 60+ algoritma, pengujian real-time, visualisasi jejak per karakter, dan arsitektur Hybrid Trio.')">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ asset('images/og-image.png') }}">
<meta property="og:image:secure_url" content="{{ asset('images/og-image.png') }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="CryptoLab - Integrated Cryptography Learning System">

{{-- ============================================================
   TWITTER CARD
   ============================================================ --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="@yield('title', 'CryptoLab | Integrated Cryptography Learning System')">
<meta name="twitter:description" content="@yield('meta_description', 'Platform pembelajaran dan laboratorium kriptografi interaktif terpadu dengan 60+ algoritma, pengujian real-time, visualisasi jejak per karakter, dan arsitektur Hybrid Trio.')">
<meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

