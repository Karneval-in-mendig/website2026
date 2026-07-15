# Website KG Niedermendig 1897 e.V.

Neue Website für [karneval-in-mendig.de](https://www.karneval-in-mendig.de) —
Astro (statisch) + Decap CMS, Staging auf GitHub Pages, Livegang später auf IONOS.

## Entwicklung

```bash
npm install
npx astro dev            # http://localhost:4321/website2026/
npx decap-server         # optional: CMS lokal unter /website2026/admin/
```

## Struktur

| Pfad | Inhalt |
|---|---|
| `src/content/` | Alle pflegbaren Inhalte (Termine, Vorstand, Korporationen, Sponsoren, News) als Markdown |
| `src/pages/` | Seiten (Astro) |
| `public/admin/` | Decap CMS ([Setup-Doku](docs/cms-setup.md)) |
| `php/` | Formular-Endpoints für IONOS (Mitgliedsantrag mit SEPA-Double-Opt-In, Kontakt) |
| `docs/sepa-online-mandat.md` | Rechtslage Online-SEPA-Mandat + Bank-Checkliste |

## Deployment

- **Phase 1 (jetzt):** Push auf `main` → GitHub Action baut und deployt auf GitHub Pages
  (Staging, `noindex`).
- **Phase 2 (Livegang):** SFTP-Deploy nach IONOS (siehe Kommentar in
  `.github/workflows/deploy.yml`), `SITE`/`BASE` umstellen, `php/` nach `/api/`
  deployen, `php/config.php` aus `php/config.example.php` erzeugen,
  `noindex`-Meta in `src/layouts/Base.astro` entfernen, Redirects alter
  WordPress-URLs einrichten.
