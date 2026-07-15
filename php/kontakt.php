<?php
/** Kontaktformular-Endpoint: validiert und leitet die Nachricht an die Geschäftsstelle weiter. */

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

function antwort(int $code, string $titel, string $text): never
{
    http_response_code($code);
    echo '<!doctype html><html lang="de"><meta charset="utf-8"><title>' . htmlspecialchars($titel) . '</title>'
        . '<body style="font-family:system-ui;max-width:40rem;margin:4rem auto;padding:0 1rem">'
        . '<h1>' . htmlspecialchars($titel) . '</h1><p>' . $text . '</p></body></html>';
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !empty($_POST['website'])) {
    antwort(400, 'Ungültige Anfrage', 'Bitte das Formular auf der Website verwenden.');
}

$name      = trim((string) ($_POST['name'] ?? ''));
$email     = trim((string) ($_POST['email'] ?? ''));
$betreff   = trim((string) ($_POST['betreff'] ?? ''));
$nachricht = trim((string) ($_POST['nachricht'] ?? ''));

if ($name === '' || $nachricht === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($_POST['datenschutz'])) {
    antwort(422, 'Angaben unvollständig', 'Bitte alle Pflichtfelder korrekt ausfüllen und der Datenverarbeitung zustimmen.');
}
if (mb_strlen($nachricht) > 5000 || mb_strlen($betreff) > 200 || mb_strlen($name) > 200) {
    antwort(422, 'Eingabe zu lang', 'Bitte die Nachricht kürzen.');
}

$header = 'From: ' . $config['mail_from'] . "\r\n"
    . 'Reply-To: ' . $email . "\r\n"
    . "Content-Type: text/plain; charset=utf-8\r\n";

$ok = mail(
    $config['mail_verein'],
    '=?UTF-8?B?' . base64_encode('Kontaktformular: ' . ($betreff !== '' ? $betreff : 'Anfrage')) . '?=',
    "Neue Nachricht über das Kontaktformular:\n\nName: $name\nE-Mail: $email\nBetreff: $betreff\n\n$nachricht\n",
    $header
);

if (!$ok) {
    antwort(500, 'Fehler beim Senden', 'Bitte später erneut versuchen oder direkt an geschaeftsstelle@karneval-in-mendig.de schreiben.');
}

antwort(200, 'Vielen Dank!', 'Ihre Nachricht ist bei uns eingegangen – wir melden uns so schnell wie möglich. '
    . '<a href="' . htmlspecialchars(rtrim($config['base_url'], '/')) . '">Zurück zur Website</a>');
