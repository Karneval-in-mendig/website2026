import { defineConfig } from 'astro/config';

// Phase 1 (Staging): GitHub Pages unter /website2026/
// Phase 2 (Live auf IONOS): SITE=https://karneval-in-mendig.de BASE=/ setzen
const site = process.env.SITE ?? 'https://karneval-in-mendig.github.io';
const base = process.env.BASE ?? '/website2026/';

export default defineConfig({
  site,
  base,
  trailingSlash: 'ignore',
});
