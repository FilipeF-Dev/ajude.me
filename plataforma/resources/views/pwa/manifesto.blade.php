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
        // O Chrome exige PNG de 192 e 512 para considerar a página
        // instalável. Sem eles o convite de instalação não aparece — e a
        // decisão A1 depende de a instalação funcionar no aparelho real.
        'icons' => [
            [
                'src' => asset('icone-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => asset('icone-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => asset('icone-mascara-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
            [
                'src' => asset('icone.svg'),
                'sizes' => 'any',
                'type' => 'image/svg+xml',
                'purpose' => 'any',
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
