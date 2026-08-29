@props(['rota', 'rotulo', 'ativo' => false])

<a href="{{ route($rota) }}"
   @if ($ativo) aria-current="page" @endif
   class="flex flex-1 flex-col items-center gap-1 py-2 {{ $ativo ? 'text-acento' : 'text-tinta-fraca' }}">
    <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        {{ $slot }}
    </svg>
    <span class="text-[11px] {{ $ativo ? 'font-semibold' : 'font-medium' }}">{{ $rotulo }}</span>
</a>
