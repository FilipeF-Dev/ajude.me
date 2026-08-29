@props(['nome', 'tamanho' => 46])

@php
    $inicial = mb_strtoupper(mb_substr(trim($nome), 0, 1));
@endphp

<span class="flex shrink-0 items-center justify-center rounded-full bg-neutro-forte font-semibold text-tinta-media"
      style="width: {{ $tamanho }}px; height: {{ $tamanho }}px; font-size: {{ round($tamanho * 0.37) }}px;"
      aria-hidden="true">{{ $inicial }}</span>
