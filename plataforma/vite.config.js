import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

/**
 * Sem fonte externa, por decisão: 05 NF-ACE-01 exige fonte do sistema.
 * Uma fonte hospedada fora são bytes a baixar antes de a tela existir, numa
 * conexão que pode ser a franquia do mês.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
