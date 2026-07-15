<?php
/**
 * Mitgliedsantrag – Schritt 1: Antrag entgegennehmen, Double-Opt-In-Mail senden.
 *
 * Ablauf:
 *   POST /api/antrag.php  → validieren → Antrag als JSON (außerhalb Docroot)
 *   speichern → E-Mail mit signiertem Bestätigungslink an Antragsteller.
 *   Erst der Klick auf den Link (bestaetigen.php) macht Antrag + SEPA-Mandat
 *   wirksam und erzeugt das Mandats-PDF/Protokoll.
 */

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

function abbruch(int $code, string $meldung): never
{
    http_response_code($code);
    echo '<!doctype html><html lang="de"><meta charset="utf-8"><title>Mitgliedsantrag</title>'
        . '<body style="font-family:system-ui;max-width:40rem;margin:4rem auto;padding:0 1rem">'
        . '<h1>Das hat leider nicht geklappt</h1><p>' . htmlspecialchars($meldung) . '</p>'
        . '<p><a href="javascript:history.back()">Zurück zum Formular</a></p></body></html>';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    abbruch(405, 'Bitte das Formular auf der Website verwenden.');
}

// --- Spam-Schutz -------------------------------------------------------------
if (!empty($_POST['website'])) {           // Honeypot
    abbruch(400, 'Ungültige Anfrage.');
}
$ts = (int) ($_POST['ts'] ?? 0);
if ($ts > 0 && (microtime(true) * 1000 - $ts) < $config['min_dauer'] * 1000) {
    abbruch(400, 'Das Formular wurde zu schnell abgeschickt. Bitte erneut versuchen.');
}

// --- Validierung -------------------------------------------------------------
function feld(string $name, bool $pflicht = true, int $max = 200): string
{
    $wert = trim((string) ($_POST[$name] ?? ''));
    if ($pflicht && $wert === '') {
        abbruch(422, "Bitte das Feld „{$name}“ ausfüllen.");
    }
    if (mb_strlen($wert) > $max) {
        abbruch(422, "Das Feld „{$name}“ ist zu lang.");
    }
    return $wert;
}

function ibanGueltig(string $iban): bool
{
    $s = strtoupper((string) preg_replace('/\s+/', '', $iban));
    if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $s)) {
        return false;
    }
    $umgestellt = substr($s, 4) . substr($s, 0, 4);
    $ziffern = '';
    foreach (str_split($umgestellt) as $zeichen) {
        $ziffern .= ctype_alpha($zeichen) ? (string) (ord($zeichen) - 55) : $zeichen;
    }
    // MOD 97-10 stückweise, um nicht von der bcmath-Extension abzuhängen
    $rest = 0;
    foreach (str_split($ziffern, 7) as $block) {
        $rest = (int) (($rest . $block) % 97);
    }
    return $rest === 1;
}

$daten = [
    'vorname'        => feld('vorname'),
    'name'           => feld('name'),
    'geschlecht'     => feld('geschlecht', false, 20),
    'geburtsdatum'   => feld('geburtsdatum'),
    'strasse'        => feld('strasse'),
    'plz'            => feld('plz', true, 5),
    'ort'            => feld('ort'),
    'telefon'        => feld('telefon', false),
    'email'          => feld('email'),
    'kontoinhaber'   => feld('kontoinhaber'),
    'kreditinstitut' => feld('kreditinstitut'),
    'iban'           => strtoupper((string) preg_replace('/\s+/', '', feld('iban', true, 40))),
    'bic'            => strtoupper(feld('bic', false, 11)),
    'fotos_ok'       => isset($_POST['fotos_ok']),
];

if (!filter_var($daten['email'], FILTER_VALIDATE_EMAIL)) {
    abbruch(422, 'Bitte eine gültige E-Mail-Adresse angeben.');
}
if (!preg_match('/^\d{5}$/', $daten['plz'])) {
    abbruch(422, 'Bitte eine gültige Postleitzahl angeben.');
}
if (!ibanGueltig($daten['iban'])) {
    abbruch(422, 'Die IBAN ist ungültig – bitte prüfen.');
}
if (empty($_POST['sepa_ok']) || empty($_POST['beitritt_ok'])) {
    abbruch(422, 'Bitte SEPA-Mandat und Beitrittserklärung bestätigen.');
}
$geburt = DateTimeImmutable::createFromFormat('Y-m-d', $daten['geburtsdatum']);
if (!$geburt || $geburt > new DateTimeImmutable('now')) {
    abbruch(422, 'Bitte ein gültiges Geburtsdatum angeben.');
}

// --- Antrag speichern ---------------------------------------------------------
$dir = rtrim($config['daten_dir'], '/');
foreach (["$dir/offen", "$dir/bestaetigt", "$dir/protokoll"] as $d) {
    if (!is_dir($d) && !mkdir($d, 0700, true)) {
        abbruch(500, 'Serverfehler beim Speichern. Bitte später erneut versuchen.');
    }
}

$antragId = date('Ymd-His') . '-' . bin2hex(random_bytes(4));
$datensatz = [
    'id'           => $antragId,
    'daten'        => $daten,
    'eingang'      => date('c'),
    'eingang_ip'   => $_SERVER['REMOTE_ADDR'] ?? '',
    'eingang_ua'   => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
    'mandatstext'  => 'Ich ermächtige die KG Niedermendig 1897 e.V., Zahlungen von meinem Konto '
        . 'mittels Lastschrift einzuziehen. Zugleich weise ich mein Kreditinstitut an, die von der '
        . 'KG Niedermendig 1897 e.V. auf mein Konto gezogenen Lastschriften einzulösen. Das Mandat '
        . 'gilt für wiederkehrende Zahlungen (Mitgliedsbeiträge) und auch bei künftigen, von der '
        . 'Mitgliederversammlung beschlossenen Beitragsanpassungen. Hinweis: Ich kann innerhalb von '
        . 'acht Wochen, beginnend mit dem Belastungsdatum, die Erstattung des belasteten Betrages '
        . 'verlangen. Es gelten dabei die mit meinem Kreditinstitut vereinbarten Bedingungen.',
];

$datei = "$dir/offen/$antragId.json";
if (file_put_contents($datei, json_encode($datensatz, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
    abbruch(500, 'Serverfehler beim Speichern. Bitte später erneut versuchen.');
}

// --- Bestätigungslink (HMAC-signiert, 7 Tage gültig) --------------------------
$ablauf = time() + 7 * 24 * 3600;
$sig = hash_hmac('sha256', "$antragId|$ablauf", $config['secret']);
$link = rtrim($config['base_url'], '/')
    . "/api/bestaetigen.php?id=" . urlencode($antragId) . "&t=$ablauf&sig=$sig";

$betreff = 'Bitte bestätigen: Dein Mitgliedsantrag bei der KG Niedermendig 1897 e.V.';
$text = "Hallo {$daten['vorname']} {$daten['name']},\n\n"
    . "vielen Dank für Deinen Mitgliedsantrag bei der KG Niedermendig 1897 e.V.!\n\n"
    . "WICHTIG: Der Antrag – einschließlich des SEPA-Lastschriftmandats für den\n"
    . "Mitgliedsbeitrag (IBAN endet auf ..." . substr($daten['iban'], -4) . ") – wird erst wirksam,\n"
    . "wenn Du ihn über folgenden Link bestätigst:\n\n"
    . "$link\n\n"
    . "Der Link ist 7 Tage gültig. Wenn Du den Antrag nicht gestellt hast,\n"
    . "ignoriere diese E-Mail einfach – es passiert dann nichts.\n\n"
    . "Mit dreimol Mennech Ahoi!\n"
    . "Deine KG Niedermendig 1897 e.V.\n";

$header = 'From: ' . $config['mail_from'] . "\r\n"
    . "Content-Type: text/plain; charset=utf-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n";

if (!mail($daten['email'], '=?UTF-8?B?' . base64_encode($betreff) . '?=', $text, $header)) {
    abbruch(500, 'Die Bestätigungs-E-Mail konnte nicht versendet werden. Bitte später erneut versuchen.');
}

// --- Erfolgsseite --------------------------------------------------------------
echo '<!doctype html><html lang="de"><meta charset="utf-8"><title>Fast geschafft!</title>'
    . '<body style="font-family:system-ui;max-width:40rem;margin:4rem auto;padding:0 1rem">'
    . '<h1>Fast geschafft! 🎉</h1>'
    . '<p>Wir haben Dir eine E-Mail an <strong>' . htmlspecialchars($daten['email']) . '</strong> geschickt.</p>'
    . '<p>Bitte klicke auf den Bestätigungslink in der E-Mail, um Deinen Mitgliedsantrag '
    . 'und das SEPA-Lastschriftmandat wirksam einzureichen. Erst danach ist der Antrag bei uns eingegangen.</p>'
    . '<p><a href="' . htmlspecialchars(rtrim($config['base_url'], '/')) . '">Zurück zur Website</a></p></body></html>';
