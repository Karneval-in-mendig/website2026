<?php
/**
 * Mitgliedsantrag – Schritt 2: Double-Opt-In-Bestätigung.
 *
 * Der Klick auf den signierten Link dokumentiert die Mandatserteilung
 * (Zeitstempel, IP, User-Agent, Datenhash) revisionssicher im Protokoll,
 * vergibt die Mandatsreferenz und mailt den vollständigen Antrag an die
 * Geschäftsstelle sowie eine Kopie an das Neumitglied.
 */

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

function seite(string $titel, string $html): never
{
    echo '<!doctype html><html lang="de"><meta charset="utf-8"><title>' . htmlspecialchars($titel) . '</title>'
        . '<body style="font-family:system-ui;max-width:40rem;margin:4rem auto;padding:0 1rem">'
        . $html . '</body></html>';
    exit;
}

$id  = (string) ($_GET['id'] ?? '');
$t   = (int) ($_GET['t'] ?? 0);
$sig = (string) ($_GET['sig'] ?? '');

if (!preg_match('/^[0-9]{8}-[0-9]{6}-[0-9a-f]{8}$/', $id)) {
    http_response_code(400);
    seite('Ungültiger Link', '<h1>Ungültiger Link</h1><p>Der Bestätigungslink ist unvollständig.</p>');
}
if (!hash_equals(hash_hmac('sha256', "$id|$t", $config['secret']), $sig)) {
    http_response_code(403);
    seite('Ungültiger Link', '<h1>Ungültiger Link</h1><p>Der Bestätigungslink ist ungültig.</p>');
}
if ($t < time()) {
    http_response_code(410);
    seite('Link abgelaufen', '<h1>Link abgelaufen</h1><p>Der Bestätigungslink ist abgelaufen (7 Tage). '
        . 'Bitte stelle den Mitgliedsantrag erneut.</p>');
}

$dir = rtrim($config['daten_dir'], '/');
$offen = "$dir/offen/$id.json";
$fertig = "$dir/bestaetigt/$id.json";

if (!is_file($offen)) {
    if (is_file($fertig)) {
        seite('Bereits bestätigt', '<h1>Schon erledigt ✅</h1><p>Dieser Antrag wurde bereits bestätigt. '
            . 'Du brauchst nichts weiter zu tun.</p>');
    }
    http_response_code(404);
    seite('Nicht gefunden', '<h1>Antrag nicht gefunden</h1><p>Bitte stelle den Antrag erneut.</p>');
}

$datensatz = json_decode((string) file_get_contents($offen), true);
$daten = $datensatz['daten'];

// --- Mandatserteilung dokumentieren -------------------------------------------
$mandatsreferenz = $config['mandat_praefix'] . '-' . date('Y') . '-' . strtoupper(substr($id, -8));
$datensatz['bestaetigung'] = [
    'zeitpunkt'       => date('c'),
    'ip'              => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent'      => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
    'mandatsreferenz' => $mandatsreferenz,
    'glaeubiger_id'   => $config['glaeubiger_id'],
    'daten_hash'      => hash('sha256', json_encode($daten)),
];

file_put_contents($fertig, json_encode($datensatz, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
unlink($offen);

// Revisionssicheres Protokoll (append-only)
$protokoll = sprintf(
    "%s\t%s\t%s\t%s\t%s\n",
    date('c'),
    $id,
    $mandatsreferenz,
    $_SERVER['REMOTE_ADDR'] ?? '',
    $datensatz['bestaetigung']['daten_hash']
);
file_put_contents("$dir/protokoll/mandate.log", $protokoll, FILE_APPEND | LOCK_EX);

// --- Antrag an Geschäftsstelle + Kopie an Mitglied -----------------------------
$zusammenfassung =
    "MITGLIEDSANTRAG – KG Niedermendig 1897 e.V.\n"
    . "============================================\n\n"
    . "Antrags-Nr.:     $id\n"
    . "Eingegangen:     {$datensatz['eingang']}\n"
    . "Bestätigt am:    {$datensatz['bestaetigung']['zeitpunkt']} (Double-Opt-In)\n\n"
    . "PERSÖNLICHE ANGABEN\n"
    . "Name:            {$daten['vorname']} {$daten['name']}\n"
    . "Geschlecht:      {$daten['geschlecht']}\n"
    . "Geburtsdatum:    {$daten['geburtsdatum']}\n"
    . "Anschrift:       {$daten['strasse']}, {$daten['plz']} {$daten['ort']}\n"
    . "Telefon:         {$daten['telefon']}\n"
    . "E-Mail:          {$daten['email']}\n\n"
    . "SEPA-LASTSCHRIFTMANDAT (wiederkehrende Zahlungen)\n"
    . "Gläubiger-ID:    {$config['glaeubiger_id']}\n"
    . "Mandatsreferenz: $mandatsreferenz\n"
    . "Kontoinhaber:    {$daten['kontoinhaber']}\n"
    . "Kreditinstitut:  {$daten['kreditinstitut']}\n"
    . "IBAN:            {$daten['iban']}\n"
    . "BIC:             {$daten['bic']}\n\n"
    . "Mandatstext:\n{$datensatz['mandatstext']}\n\n"
    . "EINWILLIGUNGEN\n"
    . "Fotoveröffentlichung: " . ($daten['fotos_ok'] ? 'JA' : 'NEIN') . "\n"
    . "Beitritt/Satzung/Datenschutz: JA\n\n"
    . "NACHWEIS DER MANDATSERTEILUNG\n"
    . "Antrag abgesendet: {$datensatz['eingang']} (IP {$datensatz['eingang_ip']})\n"
    . "Per E-Mail-Link bestätigt: {$datensatz['bestaetigung']['zeitpunkt']} (IP {$datensatz['bestaetigung']['ip']})\n"
    . "Datenhash (SHA-256): {$datensatz['bestaetigung']['daten_hash']}\n";

$header = 'From: ' . $config['mail_from'] . "\r\n"
    . "Content-Type: text/plain; charset=utf-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n";

mail(
    $config['mail_verein'],
    '=?UTF-8?B?' . base64_encode("Neuer Mitgliedsantrag: {$daten['vorname']} {$daten['name']}") . '?=',
    $zusammenfassung,
    $header . 'Reply-To: ' . $daten['email'] . "\r\n"
);
mail(
    $daten['email'],
    '=?UTF-8?B?' . base64_encode('Dein Mitgliedsantrag ist eingegangen – KG Niedermendig 1897 e.V.') . '?=',
    "Hallo {$daten['vorname']},\n\ndein Mitgliedsantrag ist nun wirksam bei uns eingegangen. "
    . "Zur Dokumentation findest du unten alle Angaben inklusive des SEPA-Mandats.\n"
    . "Bitte bewahre diese E-Mail auf.\n\n"
    . "Mit dreimol Mennech Ahoi!\nDeine KG Niedermendig 1897 e.V.\n\n"
    . "--------------------------------------------\n\n"
    . $zusammenfassung,
    $header
);

seite('Willkommen in der KGN!', '<h1>Willkommen in der KGN! 🎉</h1>'
    . '<p>Dein Mitgliedsantrag ist bestätigt und wirksam eingereicht. Eine Kopie aller Angaben '
    . 'inklusive des SEPA-Mandats (Mandatsreferenz <strong>' . htmlspecialchars($mandatsreferenz) . '</strong>) '
    . 'haben wir Dir per E-Mail geschickt.</p>'
    . '<p>Die Geschäftsstelle meldet sich bei Dir. Mit dreimol Mennech Ahoi!</p>'
    . '<p><a href="' . htmlspecialchars(rtrim($config['base_url'], '/')) . '">Zurück zur Website</a></p>');
