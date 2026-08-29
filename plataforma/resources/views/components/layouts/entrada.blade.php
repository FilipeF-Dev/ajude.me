<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF7F2">
    <title>{{ $titulo ?? 'Ajude.me' }}</title>

    <link rel="manifest" href="{{ route('manifesto') }}">
    <link rel="icon" href="{{ asset('icone.svg') }}" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- Sem barra inferior: quem ainda não entrou não tem carteira nem conversas
     para onde navegar. --}}
<body class="min-h-dvh">
    <main class="mx-auto flex min-h-dvh w-full max-w-[520px] flex-col">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
