# SEPA-Lastschriftmandat vollständig online – Rechtslage & Bank-Checkliste

*Internes Dokument für den Vorstand der KG Niedermendig 1897 e.V. – Stand: Juli 2026.
Keine Rechtsberatung; bei Zweifeln kurz mit der Bank bzw. einem Vereinsrechts-Anwalt gegenchecken.*

## 1. Warum der Antrag bisher ausgedruckt werden musste

Das klassische SEPA-Basislastschriftmandat sieht im Standardformular eine
Unterschrift vor. Viele Vereine drucken deshalb "sicherheitshalber". Eine
**gesetzliche Pflicht zur eigenhändigen Unterschrift auf Papier gibt es aber
nicht** – entscheidend sind (a) das SEPA-Regelwerk des European Payments
Council (EPC), (b) die Inkassovereinbarung mit der eigenen Bank und (c) ggf.
die eigene Satzung.

## 2. Warum die Online-Erteilung zulässig ist

1. **SEPA-Regelwerk (EPC Core Rulebook):** Das Mandat muss *nachweisbar*
   erteilt sein ("mandate must be signed by the debtor"), wobei das Rulebook
   ausdrücklich elektronische Wege zulässt (e-Mandates, elektronische
   Signaturen und andere Formen, deren Rechtsgültigkeit sich nach nationalem
   Recht richtet). Verlangt wird ein dauerhafter Datenträger und die
   Nachweisbarkeit der Zustimmung des Zahlungspflichtigen.
2. **Deutsches Recht:** § 675j BGB verlangt die *Zustimmung* des Zahlers zum
   Zahlungsvorgang – eine bestimmte Form schreibt das Gesetz nicht vor. Die
   Schriftform wäre nur nötig, wenn Satzung oder Beitragsordnung sie
   ausdrücklich fordern (→ prüfen; ggf. bei der nächsten JHV anpassen).
3. **Praxis:** Onlinehandel und Vereinssoftware (z. B. WISO MeinVerein,
   campflow, ClubDesk) holen SEPA-Mandate seit Jahren per Checkbox/Klick ein.
   Entscheidend ist die **Dokumentation**, nicht das Papier.

### Das (kalkulierbare) Restrisiko

Kann der Verein auf Verlangen der Bank kein unterschriebenes Mandat vorlegen,
gilt eine Lastschrift im Streitfall als "nicht autorisiert" – der Zahler kann
dann **bis zu 13 Monate** (statt 8 Wochen) erstatten lassen. Konsequenz: Der
Verein trägt die Rücklastschriftkosten. Bei Mitgliedsbeiträgen von Neumitgliedern,
die den Antrag selbst gestellt und per E-Mail-Link bestätigt haben, ist dieses
Szenario praktisch bedeutungslos – wer widerruft, tritt ohnehin aus.

## 3. Wie unsere Website die Nachweisbarkeit herstellt (Double-Opt-In)

Unser Verfahren dokumentiert die Mandatserteilung mehrstufig:

1. Formular mit vollständigem Mandatstext, Pflicht-Checkbox "Ich erteile das
   SEPA-Lastschriftmandat", serverseitiger IBAN-Prüfung.
2. **Bestätigungs-E-Mail** mit kryptografisch signiertem Link (7 Tage gültig).
   Erst der Klick macht Antrag + Mandat wirksam → beweist Kontrolle über die
   angegebene E-Mail-Adresse ("Double-Opt-In", analog zur etablierten
   Newsletter-Rechtsprechung zum Einwilligungsnachweis).
3. Protokollierung: Zeitstempel und IP von Absendung *und* Bestätigung,
   SHA-256-Hash der Antragsdaten, eindeutige **Mandatsreferenz**
   (`KGN-M-JJJJ-XXXXXXXX`), append-only Logdatei + archivierter Datensatz
   außerhalb des Webroots.
4. Das Neumitglied erhält automatisch eine Kopie aller Angaben inkl.
   Mandatstext, Gläubiger-ID und Mandatsreferenz (= Pre-Notification-fähige
   Dokumentation auf dauerhaftem Datenträger).

## 4. Checkliste fürs Gespräch mit Volksbank RheinAhrEifel / KSK Mayen

Vor dem Livegang **einmalig mit beiden Banken klären** (Inkassovereinbarung!):

- [ ] Akzeptiert die Bank elektronisch (per Double-Opt-In) erteilte Mandate,
      oder verlangt die Inkassovereinbarung ausdrücklich Schriftform?
      → Falls Schriftform: Vereinbarung anpassen lassen oder Bank wechseln lassen ist
      meist nicht nötig – viele Volksbanken/Sparkassen akzeptieren die
      dokumentierte elektronische Erteilung, solange der Verein das Risiko
      nicht autorisierter Rücklastschriften trägt (tut er ohnehin).
- [ ] **Gläubiger-Identifikationsnummer** vorhanden? (Beantragung kostenlos bei
      der Deutschen Bundesbank, glaeubiger-id.bundesbank.de) → in
      `php/config.php` eintragen.
- [ ] **Mandatsreferenz-Schema** mitteilen: `KGN-M-JJJJ-XXXXXXXX` (max. 35
      Zeichen, eindeutig – erfüllt die SEPA-Vorgaben).
- [ ] **Pre-Notification**: Frist mit der Bank abstimmen (Standard 14 Tage vor
      Einzug, verkürzbar; Hinweis kann in die Beitrittsbestätigung/Jahresinfo).
- [ ] Vorlagefristen und Einreichungsweg der Lastschriftdatei (SEPA-XML aus der
      Vereinsverwaltung) wie bisher.
- [ ] Optional: der Bank das Verfahren einmal kurz schriftlich beschreiben
      (dieses Dokument genügt) und die Rückmeldung archivieren.

## 5. Satzung / Beitragsordnung prüfen

- Verlangt die Satzung für den Beitritt Schriftform? Wenn ja, deckt
  § 127 Abs. 2 BGB i. d. R. auch die "telekommunikative Übermittlung"
  (E-Mail) ab, sofern die Satzung nichts Strengeres bestimmt. Zur absoluten
  Klarheit empfiehlt sich bei der nächsten Satzungsänderung ein Zusatz:
  *"Der Aufnahmeantrag kann auch elektronisch über das Online-Formular des
  Vereins gestellt werden."*

## Quellen

- [EPC SEPA Direct Debit Core Rulebook](https://www.europeanpaymentscouncil.eu/what-we-do/sepa-direct-debit) – Mandatsanforderungen, e-Mandate-Optionen
- [vereinsantrag.de: Sind Online-Formulare eigentlich rechtsgültig?](https://vereinsantrag.de/blogs/sind-online-formulare-eigentlich-rechtsgueltig)
- [campflow-Ratgeber: SEPA-Lastschrift im Verein](https://www.campflow.de/ratgeber/sepa-lastschrift-mandat-im-verein-anleitung-vorlagen-musterformular)
- [WISO MeinVerein: SEPA-Lastschrift im Verein](https://www.meinverein.de/blog/vereinsbuchhaltung-finanzierung/sepa-lastschrift/)
- [ClubDesk: SEPA-Lastschrift-Mandat für Vereine](https://www.clubdesk.de/de/rechnungen-und-vereinsbuchhaltung/sepa-lastschrift-mandat-verein)
- [Deutsche Bundesbank: Gläubiger-Identifikationsnummer](https://www.bundesbank.de/de/aufgaben/unbarer-zahlungsverkehr/serviceangebot/glaeubiger-identifikationsnummer)
