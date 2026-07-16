# CMS-Redaktion (Decap CMS)

Die Inhalte (Termine, Vorstand, Korporationen, Sponsoren, Aktuelles) liegen als
Markdown-Dateien in `src/content/`. Gepflegt werden sie über die Weboberfläche
unter **`/admin/`**.

## Warum überhaupt ein Login-Vermittler?

Decap speichert Inhalte direkt im GitHub-Repo. Damit sich Redakteure mit ihrem
GitHub-Konto anmelden können, braucht es einen winzigen Vermittler, der das
GitHub-Login-Handshake serverseitig abschließt (das Client-Secret darf nicht in
den Browser). Bei uns ist das **eine selbst gehostete PHP-Datei**
([php/oauth.php](../php/oauth.php)) auf dem eigenen IONOS-Webspace — keine
externen Dienste, kein Cloudflare, kein Netlify. Sie speichert nichts, sie
reicht nur das Token durch (~100 Zeilen, auditierbar).

## Lokal testen (ohne Login, jederzeit)

```bash
npx astro dev          # Terminal 1
npx decap-server       # Terminal 2
```

Dann http://localhost:4321/website2026/admin/ öffnen — dank `local_backend: true`
ist kein Login nötig; Änderungen landen direkt im Arbeitsverzeichnis.

## Produktiv einrichten (einmalig, mit dem IONOS-Umzug)

1. **GitHub OAuth App anlegen** (Org `Karneval-in-mendig` → Settings →
   Developer settings → OAuth Apps → New OAuth App):
   - Homepage URL: `https://karneval-in-mendig.de`
   - Authorization callback URL: `https://karneval-in-mendig.de/api/oauth.php?action=callback`
2. Client-ID und Client-Secret in die `php/config.php` **auf dem Server**
   eintragen (`github_client_id`, `github_client_secret`). Das Secret niemals
   ins Repo committen — `config.php` ist gitignored.
3. In `public/admin/config.yml` die zwei Zeilen `base_url` und `auth_endpoint`
   einkommentieren.
4. Redakteure brauchen ein GitHub-Konto mit Schreibrecht auf das Repo
   (in der Org ein Team „Redaktion“ mit Write-Rolle anlegen).

**Admin-Wechsel später:** Der Nachfolger braucht nur Owner-Rechte in der
GitHub-Org und den IONOS-Zugang — mehr Konten gibt es nicht.

## Rollen pro Korporation (später)

Aktuell haben alle Redakteure Zugriff auf alle Inhalte. Wenn später
Korporationen ihre Bereiche selbst pflegen sollen: GitHub-Branch-Protection +
`publish_mode: editorial_workflow` (Änderungen werden Pull Requests, der
Vorstand merged). Das ist in Decap eine Zwei-Zeilen-Änderung.
