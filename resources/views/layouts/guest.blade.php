<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', config('app.name','MisPuntos'))</title>

  {{-- ===== PWA / Manifest ===== --}}
  <link rel="manifest" href="{{ asset('manifest.json') }}">
  <meta name="theme-color" content="#0d6efd">
  <meta name="mobile-web-app-capable" content="yes">

  {{-- iOS (Safari) --}}
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="MisPuntos">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/icon-180.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">

  {{-- Bootstrap CSS + Icons (CDN primero) --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  {{-- Tu CSS/JS (Vite después) --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
  {{ $slot }}

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  {{-- ===== Service Worker ===== --}}
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register('{{ asset('sw.js') }}');
      });
    }
  </script>
</body>
</html>
