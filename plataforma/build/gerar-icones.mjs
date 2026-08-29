/**
 * Gera os ícones PNG do aplicativo instalável a partir dos SVG.
 *
 * O Chrome exige PNG de 192 e 512 para considerar a página instalável. Sem
 * eles, o convite "adicionar à tela inicial" não aparece — e a decisão A1
 * (05 §15) depende exatamente de a instalação funcionar num aparelho real.
 *
 * Rode com `npm run icones` depois de mexer nos SVG.
 */
import { readFile, writeFile } from 'node:fs/promises';
import sharp from 'sharp';

const saidas = [
    { origem: 'public/icone.svg', destino: 'public/icone-192.png', tamanho: 192 },
    { origem: 'public/icone.svg', destino: 'public/icone-512.png', tamanho: 512 },
    { origem: 'public/icone-mascara.svg', destino: 'public/icone-mascara-512.png', tamanho: 512 },
    // O Android usa este para o atalho na tela inicial em densidades altas.
    { origem: 'public/icone.svg', destino: 'public/apple-touch-icon.png', tamanho: 180 },
];

for (const { origem, destino, tamanho } of saidas) {
    const svg = await readFile(origem);
    const png = await sharp(svg, { density: 384 }).resize(tamanho, tamanho).png().toBuffer();

    await writeFile(destino, png);
    console.log(`${destino} (${tamanho}px)`);
}
