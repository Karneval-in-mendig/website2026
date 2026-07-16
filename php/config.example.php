<?php
// Konfiguration Mitgliedsantrag – Kopie als config.php ablegen (config.php ist
// per .gitignore ausgeschlossen und liegt nur auf dem Server).
return [
    // Absender/Empfänger
    'mail_from'        => 'noreply@karneval-in-mendig.de',
    'mail_verein'      => 'geschaeftsstelle@karneval-in-mendig.de',

    // Basis-URL der Website (für den Bestätigungslink)
    'base_url'         => 'https://karneval-in-mendig.de',

    // Datenverzeichnis AUSSERHALB des Docroot! Anträge + Mandatsprotokoll
    // landen hier. Auf IONOS z. B. /homepages/xx/dXXXX/kgn-antraege
    'daten_dir'        => __DIR__ . '/../kgn-antraege',

    // Geheimer Schlüssel für die Signatur der Bestätigungslinks.
    // Einmalig erzeugen: php -r "echo bin2hex(random_bytes(32));"
    'secret'           => 'HIER-64-HEX-ZEICHEN-EINSETZEN',

    // Mandatsreferenz-Präfix (mit der Bank abstimmen, siehe docs/sepa-online-mandat.md)
    'mandat_praefix'   => 'KGN-M',

    // Gläubiger-Identifikationsnummer des Vereins (bei der Bundesbank beantragt)
    'glaeubiger_id'    => 'DEXXZZZ00000000000',

    // Mindest-Ausfüllzeit in Sekunden (Spam-Schutz Time-Trap)
    'min_dauer'        => 15,

    // GitHub OAuth App für den CMS-Login unter /admin (siehe docs/cms-setup.md)
    'github_client_id'     => '',
    'github_client_secret' => '',
];
