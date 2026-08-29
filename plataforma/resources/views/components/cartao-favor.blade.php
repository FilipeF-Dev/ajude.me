@props(['favor'])

@php
    $ajudante = $favor->proficienciaUsuario->usuario;
    $distancia = isset($favor->distancia_metros) && $favor->distancia_metros !== null
        ? ($favor->distancia_metros < 1000
            ? round($favor->distancia_metros).' m'
            : number_format($favor->distancia_metros / 1000, 1, ',', '.').' km')
        : null;
@endphp

<a href="{{ route('favor', $favor) }}" wire:navigate
   class="cartao flex flex-col gap-3.5 p-4">
    <div class="flex items-start gap-3.5">
        <x-avatar :nome="$ajudante->nome" />
        <div class="flex min-w-0 flex-grow flex-col gap-1">
            <span class="text-[16px] font-semibold leading-tight tracking-[-0.01em]">{{ $favor->titulo }}</span>
            <span class="text-[14px] text-tinta-suave">
                {{ $ajudante->nome }}@if ($distancia) · {{ $distancia }} @endif
            </span>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <x-descritor :favor="$favor" />
        <span class="text-[13px] font-medium text-tinta-suave">
            {{ $favor->modalidade === \App\Models\Favor::MODALIDADE_PRESENCIAL ? 'Presencial' : 'Remoto' }}
        </span>
    </div>
</a>
