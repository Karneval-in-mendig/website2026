<?php
/**
 * Selbst gehosteter GitHub-OAuth-Vermittler für Decap CMS (/admin).
 *
 * Läuft als einzelne Datei auf dem IONOS-Webspace unter /api/oauth.php.
 * Decap öffnet ein Popup auf ?provider=github (Schritt 1), GitHub leitet
 * nach dem Login auf ?action=callback zurück (Schritt 2), das Skript tauscht
 * den Code gegen ein Token und übergibt es per postMessage an das CMS.
 * Es werden keine Tokens gespeichert – der Server vermittelt nur.
 *
 * Einrichtung: siehe docs/cms-setup.md (GitHub OAuth App + zwei Werte in
 * php/config.php: github_client_id, github_client_secret).
 */

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

$clientId     = $config['github_client_id'] ?? '';
$clientSecret = $config['github_client_secret'] ?? '';
$secret       = $config['secret'];

if ($clientId === '' || $clientSecret === '') {
    http_response_code(500);
    exit('OAuth ist nicht konfiguriert (github_client_id/github_client_secret in config.php fehlen).');
}

$action = $_GET['action'] ?? 'auth';

// Eigene URL dieses Skripts (für die Callback-Redirect-URI)
$self = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . strtok($_SERVER['REQUEST_URI'] ?? '', '?');

if ($action === 'auth') {
    // Schritt 1: Zu GitHub weiterleiten. State ist HMAC-signiert (kein Session-Zwang).
    $zeit = time();
    $state = $zeit . '.' . hash_hmac('sha256', "decap-oauth|$zeit", $secret);
    $url = 'https://github.com/login/oauth/authorize?' . http_build_query([
        'client_id'    => $clientId,
        'redirect_uri' => $self . '?action=callback',
        'scope'        => 'repo',
        'state'        => $state,
    ]);
    header('Location: ' . $url);
    exit;
}

if ($action === 'callback') {
    $code  = (string) ($_GET['code'] ?? '');
    $state = (string) ($_GET['state'] ?? '');

    [$zeit, $sig] = array_pad(explode('.', $state, 2), 2, '');
    $gueltig = $code !== ''
        && hash_equals(hash_hmac('sha256', "decap-oauth|$zeit", $secret), $sig)
        && (int) $zeit > time() - 600; // State max. 10 Minuten alt

    $ergebnis = ['token' => '', 'fehler' => ''];
    if (!$gueltig) {
        $ergebnis['fehler'] = 'Ungültiger oder abgelaufener Anmeldeversuch. Bitte erneut einloggen.';
    } else {
        // Code gegen Token tauschen
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/x-www-form-urlencoded\r\nUser-Agent: kgn-decap-oauth\r\n",
            'content' => http_build_query([
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'code'          => $code,
                'redirect_uri'  => $self . '?action=callback',
            ]),
            'timeout' => 15,
        ]]);
        $antwort = @file_get_contents('https://github.com/login/oauth/access_token', false, $ctx);
        $daten = json_decode((string) $antwort, true);
        if (!empty($daten['access_token'])) {
            $ergebnis['token'] = (string) $daten['access_token'];
        } else {
            $ergebnis['fehler'] = 'GitHub hat kein Token ausgestellt: ' . ($daten['error_description'] ?? 'unbekannter Fehler');
        }
    }

    // Decap-Handshake: erst "authorizing", nach Antwort des Openers das Ergebnis senden.
    $nachricht = $ergebnis['token'] !== ''
        ? 'authorization:github:success:' . json_encode(['token' => $ergebnis['token'], 'provider' => 'github'])
        : 'authorization:github:error:' . json_encode(['error' => $ergebnis['fehler']]);

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!doctype html><html lang="de"><meta charset="utf-8"><title>Anmeldung…</title><body>
<p>Anmeldung wird abgeschlossen…</p>
<script>
  (function () {
    var nachricht = <?php echo json_encode($nachricht); ?>;
    function empfaenger(e) {
      window.removeEventListener('message', empfaenger, false);
      e.source.postMessage(nachricht, e.origin);
    }
    window.addEventListener('message', empfaenger, false);
    window.opener.postMessage('authorizing:github', '*');
  })();
</script>
</body></html>
    <?php
    exit;
}

http_response_code(400);
exit('Unbekannte Aktion.');
