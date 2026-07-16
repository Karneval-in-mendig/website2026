# CMS-Login aktivieren (15 Minuten, kein Website-Umzug nötig)

*Anleitung zum Weiterleiten an den Vorstandskollegen mit IONOS-Zugang.
Ziel: Der Redaktions-Login unter https://karneval-in-mendig.github.io/website2026/admin/
funktioniert im Internet — die alte Website bleibt völlig unberührt.*

## Was passiert hier?

Das neue CMS speichert Inhalte im GitHub-Repo des Vereins. Für den Login mit
GitHub-Konten braucht es ein kleines Vermittler-Skript auf einem eigenen Server.
Das legen wir einfach auf den vorhandenen IONOS-Webspace neben die alte
WordPress-Site — in einen neuen Ordner `/api/`, der WordPress nicht stört.

## Schritt 1: GitHub OAuth App anlegen (macht wer Owner der GitHub-Org ist)

GitHub → Organisation `Karneval-in-mendig` → Settings → Developer settings →
OAuth Apps → **New OAuth App**:

| Feld | Wert |
|---|---|
| Application name | `KGN Redaktion` |
| Homepage URL | `https://karneval-in-mendig.de` |
| Authorization callback URL | `https://karneval-in-mendig.de/api/oauth.php?action=callback` |

Danach **Generate a new client secret** klicken. Client-ID und Secret für
Schritt 2 bereithalten (Secret wird nur einmal angezeigt).

## Schritt 2: Zwei Dateien auf den IONOS-Webspace legen (macht wer IONOS-Zugang hat)

1. Per SFTP/Dateimanager im Webroot der Domain einen Ordner **`api`** anlegen.
2. Die Datei [`php/oauth.php`](../php/oauth.php) aus dem Repo unverändert als
   `api/oauth.php` hochladen.
3. Daneben eine Datei `api/config.php` anlegen (NICHT aus dem Repo, sie enthält
   das Secret) mit diesem Inhalt:

```php
<?php
return [
    'secret'               => '<64 Zufalls-Hex-Zeichen, z. B. von https://www.random.org/strings/ oder: php -r "echo bin2hex(random_bytes(32));">',
    'github_client_id'     => '<Client-ID aus Schritt 1>',
    'github_client_secret' => '<Client-Secret aus Schritt 1>',
];
```

*(Die übrigen config-Einträge aus `php/config.example.php` braucht es erst für
den Mitgliedsantrag beim richtigen Umzug — für den CMS-Login reichen diese drei.)*

## Schritt 3: Testen

1. https://karneval-in-mendig.de/api/oauth.php aufrufen → es muss zur
   GitHub-Login-Seite weiterleiten (nicht 404, nicht WordPress).
2. https://karneval-in-mendig.github.io/website2026/admin/ → **Login mit GitHub**
   → GitHub-Konto autorisieren → das Redaktions-Backend öffnet sich.

## Wer darf sich einloggen?

Jedes GitHub-Konto mit Schreibrecht auf das Repo `Karneval-in-mendig/website2026`.
Empfehlung: In der Org ein Team „Redaktion" mit Write-Rolle anlegen und die
Vorstandskollegen (GitHub-Konten) dort aufnehmen.

## Sicherheit

- `oauth.php` speichert nichts und hat keinen Zugriff auf WordPress oder Daten —
  es reicht nur das GitHub-Login-Token an das CMS-Fenster durch (~100 Zeilen,
  im Repo einsehbar).
- Das Client-Secret liegt ausschließlich in `api/config.php` auf dem Server,
  niemals im Repo.
