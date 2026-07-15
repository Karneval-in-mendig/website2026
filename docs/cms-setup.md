# CMS-Redaktion (Decap CMS)

Die Inhalte (Termine, Vorstand, Korporationen, Sponsoren, Aktuelles) liegen als
Markdown-Dateien in `src/content/`. Gepflegt werden sie bequem über die
Weboberfläche unter **`/admin/`**.

## Lokal testen (ohne Login)

```bash
npx astro dev          # Terminal 1
npx decap-server       # Terminal 2
```

Dann http://localhost:4321/website2026/admin/ öffnen — dank `local_backend: true`
ist kein Login nötig; Änderungen landen direkt im Arbeitsverzeichnis.

## Produktiv einrichten (einmalig)

Decap braucht einen kleinen OAuth-Proxy, damit Redakteure sich mit ihrem
GitHub-Konto anmelden können (GitHub Pages kann das nicht selbst):

1. **GitHub OAuth-App anlegen** (Org `Karneval-in-mendig` → Settings → Developer
   settings → OAuth Apps):
   - Homepage: `https://karneval-in-mendig.github.io/website2026/`
   - Callback: `https://<worker-url>/callback`
2. **OAuth-Proxy deployen** — fertige, quelloffene Vorlage:
   [decap-proxy für Cloudflare Workers](https://github.com/sterlingwes/decap-proxy)
   (kostenloser Cloudflare-Account reicht). Client-ID/Secret der OAuth-App als
   Secrets hinterlegen.
3. In `public/admin/config.yml` die Zeile `base_url:` einkommentieren und auf die
   Worker-URL setzen.
4. Redakteure brauchen ein GitHub-Konto mit Schreibrecht auf das Repo
   (Org-Team „Redaktion“ mit Write-Rolle genügt).

## Rollen pro Korporation (später)

Aktuell haben alle Redakteure Zugriff auf alle Inhalte. Wenn später Korporationen
ihre Bereiche selbst pflegen sollen, ist der saubere Weg: GitHub-Branch-Protection
+ `publish_mode: editorial_workflow` (Änderungen werden Pull Requests, der
Vorstand merged). Das ist in Decap eine Zwei-Zeilen-Änderung.
