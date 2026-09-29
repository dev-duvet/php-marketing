import vercel from '@astrojs/vercel';
import { defineConfig } from 'astro/config';

export default defineConfig({
  output: 'server',
  adapter: vercel({ maxDuration: 30 }),
  // Keep authored whitespace; Astro 7's default 'jsx' mode strips spaces between inline elements.
  compressHTML: false,
  security: { checkOrigin: true },
  devToolbar: { enabled: false },
  server: { port: 4321, host: 'localhost' },
  vite: {
    ssr: { external: ['node:sqlite'] },
  },
});
