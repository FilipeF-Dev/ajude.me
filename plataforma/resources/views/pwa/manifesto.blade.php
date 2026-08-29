{{-- Manifesto do aplicativo instalável (decisão A1, 05 §15). --}}
@php
    $manifesto = [
        'name' => 'Ajude.me',
        'short_name' => 'Ajude.me',
        'description' => 'Troca de favores entre pessoas. Sua habilidade é a moeda.',
        'lang' => 'pt-BR',
        'dir' => 'ltr',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'background_color' => '#FAF7F2',
        'theme_color' => '#FAF7F2',
        'categories' => ['social', 'lifestyle'],
        'icons' => [
            [
                'src' => asset('icone.svg'),
                'sizes' => 'any',
                'type' => 'image/svg+xml',
                'purpose' => 'any',
            ],
            [
                'src' => asset('icone-mascara.svg'),
                'sizes' => 'any',
                'type' => 'image/svg+xml',
                'purpose' => 'maskable',
            ],
        ],
        'shortcuts' => [
            [
                'name' => 'Sua carteira',
                'url' => '/carteira',
            ],
            [
                'name' => 'Oferecer ajuda',
                'url' => '/oferecer',
            ],
        ],
    ];
@endphp
{!! json_encode($manifesto, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
