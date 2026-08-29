@props(['favor'])

{{--
    O descritor de dois eixos (02 §6, RF-BUS-09).

    Ocupa o lugar onde normalmente estaria o preço. A plataforma descreve o
    favor, não o precifica — e o nível em cor de acento força a leitura das
    duas dimensões: uma hora de quem está começando não é uma hora de quem é
    especialista.
--}}
<span class="descritor">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6B6055"
         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="9"/>
        <path d="M12 7v5l3 2"/>
    </svg>
    <span>{{ $favor->tempoPorExtenso() }}</span>
    <span class="text-[#C6BCB0]">·</span>
    <span class="nivel">{{ \App\Models\ProficienciaUsuario::niveis()[$favor->nivel()] }}</span>
</span>
