# Go-Live-Guide: Von der Staging-Site zur Produktion auf IONOS

Kompletter Fahrplan von https://karneval-in-mendig.github.io/website2026/ (Staging)
zu https://karneval-in-mendig.de (Produktion). Die Detail-Dokus werden an den
passenden Stellen verlinkt. Reihenfolge ist so gewählt, dass die alte Website
bis zum letzten Schritt erreichbar bleibt und ein Rollback jederzeit möglich ist.

**Beteiligte:** ① Wer GitHub-Org-Owner ist · ② Wer IONOS-Zugang hat · ③ Vorstand/Bank.
Die Phasen 1–3 können parallel und Wochen vor dem eigentlichen Go-Live passieren.

---

## Phase 0: Was schon erledigt ist

- ✅ Website komplett (Astro, alle Seiten, Design, Dark Mode)
- ✅ CMS-Redaktion inkl. Demo-Modus (`/admin/demo/`) und lokalem Arbeiten
- ✅ Digitaler Mitgliedsantrag mit SEPA-Double-Opt-In, lokal durchgetestet
- ✅ Staging-Deploy per GitHub Actions auf GitHub Pages

## Phase 1: Organisatorisches (kann sofort starten)

- [ ] **Gläubiger-Identifikationsnummer** beantragen, falls noch nicht vorhanden
      (kostenlos, [Deutsche Bundesbank](https://www.bundesbank.de/de/aufgaben/unbarer-zahlungsverkehr/serviceangebot/glaeubiger-identifikationsnummer), dauert einige Tage — früh anstoßen!)
- [ ] **Bankgespräch** Volksbank RheinAhrEifel + KSK Mayen zur Akzeptanz elektronisch
      erteilter SEPA-Mandate → Checkliste in [sepa-online-mandat.md](sepa-online-mandat.md), Abschnitt 4
- [ ] **Satzung prüfen**: verlangt sie Schriftform für den Beitritt?
      → [sepa-online-mandat.md](sepa-online-mandat.md), Abschnitt 5
- [ ] **Inhalte komplettieren** (per CMS oder direkt im Repo):
  - [ ] Texte/Fotos Jecke Füüßje und Basaltbeißer, Korporationslogos
  - [ ] Vorstandsfotos (ersetzen die Platzhalter)
  - [ ] Satzung + Beitragsordnung als PDF (werden vom Mitgliedsantrag verlinkt)
  - [ ] Social-Media-Links vervollständigen (Insta KG, Zarte Zehe, Schloffmötschen)
  - [ ] Sponsoren-URLs ergänzen (Logos sind da, Links fehlen noch)
- [ ] **Datenschutzerklärung** von jemandem mit Rechtsblick prüfen lassen — der
      Entwurf in `src/pages/die-kg/datenschutz.astro` deckt den Online-Antrag
      bereits ab, der interne Hinweis oben im Text muss vor Go-Live raus
- [ ] **Impressum** prüfen (Adressen/Namen aktuell?)

## Phase 2: CMS-Login im Internet aktivieren (jederzeit möglich)

Funktioniert schon vor dem Umzug, weil das oauth.php neben der alten
WordPress-Site laufen kann → komplette Anleitung: [cms-login-aktivieren.md](cms-login-aktivieren.md)

- [ ] GitHub OAuth App anlegen (①)
- [ ] `api/oauth.php` + `api/config.php` auf den bestehenden Webspace legen (②)
- [ ] GitHub-Team „Redaktion“ anlegen, Redakteure einladen (①)
- [ ] Testen: Login unter `…/website2026/admin/` mit GitHub-Konto

## Phase 3: Produktions-Konfiguration auf IONOS vorbereiten (②)

Alles in einem Unterordner, ohne die alte Site anzufassen:

- [ ] **PHP-Version prüfen**: IONOS-Paket auf PHP ≥ 8.1 stellen (Kontrollpanel)
- [ ] **Datenverzeichnis außerhalb des Docroot** anlegen, z. B.
      `/homepages/…/kgn-antraege` — hier landen Anträge + Mandatsprotokoll
- [ ] **`api/config.php` vervollständigen** nach Vorlage
      [php/config.example.php](../php/config.example.php):
  - `mail_from`: Absender, **muss zur Domain gehören** (z. B.
        `noreply@karneval-in-mendig.de`) — sonst landet die Opt-In-Mail im Spam.
        Das Postfach/die Adresse vorher im IONOS-Mailcenter anlegen.
  - `mail_verein`: Empfänger der Anträge (z. B. `geschaeftsstelle@…`)
  - `base_url`: `https://karneval-in-mendig.de`
  - `daten_dir`: Pfad aus dem Schritt oben
  - `secret`: 64 Hex-Zeichen (`php -r "echo bin2hex(random_bytes(32));"`)
  - `glaeubiger_id`: aus Phase 1
  - `github_client_id`/`_secret`: aus Phase 2
- [ ] **SPF-Eintrag prüfen**: Wenn die Domain-DNS bei IONOS liegt und die Mails
      über IONOS `mail()` gehen, passt der Standard-SPF. Bei externem DNS:
      `include:_spf.perfora.net include:_spf.kundenserver.de` ergänzen.
- [ ] **Mail-Test**: kleine Testdatei hochladen, die `mail()` an eine private
      Adresse schickt — kommt sie an (auch bei GMX/Gmail, Spam-Ordner prüfen)?

## Phase 4: Produktions-Deploy einrichten

- [ ] **SFTP-Zugangsdaten** aus dem IONOS-Panel (Host, User, Passwort) als
      GitHub-Secrets hinterlegen: Repo → Settings → Secrets and variables →
      Actions → `IONOS_SFTP_HOST`, `IONOS_SFTP_USER`, `IONOS_SFTP_PASSWORD` (①+②)
- [ ] **Workflow umbauen** (`.github/workflows/deploy.yml`): Production-Job ergänzt
      den Pages-Job (Pages bleibt als Staging!). Kern:
  ```yaml
  - run: SITE=https://karneval-in-mendig.de BASE=/ npx astro build
  - uses: SamKirkland/FTP-Deploy-Action@v4.3.5
    with:
      server: ${{ secrets.IONOS_SFTP_HOST }}
      username: ${{ secrets.IONOS_SFTP_USER }}
      password: ${{ secrets.IONOS_SFTP_PASSWORD }}
      protocol: ftps
      local-dir: dist/
      server-dir: neue-website/     # erst in Unterordner, später Docroot
  ```
  Empfehlung: Produktion nur bei Git-Tag (`on: push: tags: ['v*']`) oder
  manuell (`workflow_dispatch`) deployen — Staging weiter bei jedem Push.
- [ ] **`noindex` entfernen**: in `src/layouts/Base.astro` die robots-Meta-Zeile
      löschen, sobald der Build für die echte Domain läuft (per Env steuerbar
      machen oder im Go-Live-Commit entfernen)
- [ ] **php/-Dateien deployen**: `antrag.php`, `bestaetigen.php`, `kontakt.php`,
      `oauth.php` nach `/api/` (einmalig manuell oder als zweiter
      FTP-Deploy-Step mit `local-dir: php/`, `server-dir: api/` —
      `config.php` liegt NUR auf dem Server und wird nie überschrieben)

## Phase 5: Generalprobe im Unterordner

Bevor die Domain umgestellt wird, alles unter
`https://karneval-in-mendig.de/neue-website/` (oder Subdomain `neu.…`) testen:

- [ ] Alle Seiten aufrufen, mobil + Desktop, hell + dunkel
- [ ] **Mitgliedsantrag komplett durchspielen** mit echter E-Mail-Adresse:
      absenden → Opt-In-Mail kommt an (nicht im Spam!) → Link klicken →
      Bestätigungsseite mit Mandatsreferenz → Antrag kommt bei
      `mail_verein` an → Datensatz liegt in `daten_dir/bestaetigt/`,
      Protokollzeile in `daten_dir/protokoll/mandate.log`
- [ ] Fehlerfälle: falsche IBAN, abgelaufener/manipulierter Link, Doppelklick
- [ ] Kontaktformular testen
- [ ] CMS: Termin anlegen → Commit erscheint im Repo → Deploy läuft →
      Änderung ist auf der Testseite sichtbar
- [ ] Lighthouse-Check (Performance/Accessibility/SEO)

## Phase 6: Der eigentliche Go-Live (②, ~1 Stunde, ruhigen Tag wählen)

1. [ ] **Backup der alten Website** (WordPress-Dateien + Datenbank-Export über
       IONOS-Panel) — Rollback-Versicherung
2. [ ] WordPress-Verzeichnis im Docroot in `alt-wordpress/` umbenennen
       (nicht löschen!)
3. [ ] Deploy-Ziel auf den Docroot umstellen (`server-dir: /`), Production-Deploy
       auslösen; `api/` liegt bereits daneben
4. [ ] **`.htaccess`** im Docroot anlegen:
   ```apache
   RewriteEngine On
   # HTTPS erzwingen
   RewriteCond %{HTTPS} !=on
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   # Alte WordPress-URLs auf neue Seiten umleiten
   Redirect 301 /die-kg/der-vorstand/ /die-kg/vorstand/
   Redirect 301 /die-kg/beitrittserkaerung/ /mitglied-werden/
   Redirect 301 /die-kg/sponsoren/ /die-kg/sponsoren/
   Redirect 301 /elferrat/ /korporationen/elferrat/
   Redirect 301 /herrenballett/ /korporationen/herrenballett/
   Redirect 301 /termine/ /termine/
   ```
   (Redirect-Ziele mit gleicher URL können entfallen; Liste beim Umzug final prüfen)
5. [ ] **OAuth App anpassen** (①): Homepage/Callback-URLs zeigen bereits auf
       karneval-in-mendig.de — nur prüfen
6. [ ] Kompletten Phase-5-Testlauf auf der echten Domain wiederholen
7. [ ] Google Search Console: Property anlegen, Sitemap einreichen (optional,
       hilft beim Neu-Indexieren)

## Phase 7: Nach dem Go-Live

- [ ] 2–4 Wochen beobachten: kommen Anträge/Kontaktmails an? 404-Fehler in den
      IONOS-Logs? Dann `alt-wordpress/` + Datenbank endgültig löschen
      (vorher Backup archivieren)
- [ ] Interner Bereich (Protokolle): bewusst NICHT im Startumfang — bei Bedarf
      als kleiner PHP-Login nachrüsten (Konzept steht, siehe README)
- [ ] **Betriebshandbuch für den Admin-Wechsel**: Es gibt genau drei Zugänge —
      GitHub-Org (Code, Redaktion, Deploys), IONOS (Hosting, Mail, api/config.php),
      Bank. Wer die übernimmt, übernimmt die Website.
- [ ] Eingegangene Anträge regelmäßig aus `daten_dir` in die Mitgliederverwaltung
      übernehmen; Mandatsprotokoll (`mandate.log`) niemals löschen
      (Nachweispflicht SEPA)

## Rollback-Plan

Sollte nach dem Go-Live etwas Grundsätzliches klemmen: `.htaccess` entfernen,
`alt-wordpress/` zurück in den Docroot benennen — die alte Site ist unverändert
wieder da. Die neue Site bleibt parallel auf GitHub Pages erreichbar.
