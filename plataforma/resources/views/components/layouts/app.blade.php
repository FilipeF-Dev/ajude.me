<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF7F2">
    <title>{{ $titulo ?? 'Ajude.me' }}</title>

    {{-- Web responsiva instalável (decisão A1, 05 §15). Sem loja, sem ciclo
         de revisão, atualização imediata. --}}
    <link rel="manifest" href="{{ route('manifesto') }}">
    <link rel="icon" href="{{ asset('icone.svg') }}" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh">
    <main class="mx-auto w-full max-w-[520px] pb-[104px]">
        {{ $slot }}
    </main>

    @auth
        <x-barra-inferior />
    @endauth

    @livewireScripts
</body>
</html>
