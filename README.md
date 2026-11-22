# MGD eBook

**EPUB-Dateien in WordPress hochladen, verlinken und mit Cover und Vorschau-Fenster anzeigen.**

Ein Plugin von [Michael Gahn DESIGN](https://michael-gahn.de). Läuft mit WordPress 6.5 oder neuer und PHP 8.1 oder neuer. Lizenz: GPL-2.0-or-later.

---

## Was ist das?

Eine **EPUB-Datei** ist ein E-Book. Sie funktioniert auf Tolino, Kobo, Apple Books und in vielen Lese-Apps.

WordPress erlaubt normalerweise keine EPUB-Dateien. Dieses Plugin ändert das:

1. Du bekommst im WordPress-Menü einen neuen Punkt **eBook**.
2. Dort lädst du EPUB-Dateien hoch.
3. Zu jeder Datei bekommst du:
   - einen **Link** (zum Verlinken, zum Beispiel bei einem Button im Divi Editor),
   - einen **Shortcode** (zeigt das Cover; ein Klick öffnet ein Fenster mit Infos und einem Download-Button).

> **Was ist ein Shortcode?** Ein kurzer Text in eckigen Klammern, zum Beispiel `[mgd_ebook id="12"]`. Du fügst ihn in eine Seite ein. WordPress ersetzt ihn dann durch das Cover.

## Installieren (Schritt für Schritt)

1. Lade die neueste ZIP-Datei herunter: [Releases](https://github.com/MichaelGahnDESIGN/MGD-eBook-WordPress/releases). Die Datei heißt `mgd-ebook-0.6.0.zip` (die Zahl ändert sich).
2. Öffne in WordPress: **Plugins → Installieren → Plugin hochladen**.
3. Wähle die ZIP-Datei aus und klicke auf **Jetzt installieren**.
4. Klicke auf **Plugin aktivieren**.
5. Links im Menü erscheint jetzt **eBook**.

**Updates:** Neue Versionen erscheinen ganz normal unter **Dashboard → Aktualisierungen**, genau wie bei den anderen MGD-Plugins. WordPress fragt dafür bei theme.michael-gahn.de nach der neuesten Version und prüft die Datei vor dem Einspielen (Prüfsumme). Das Update startest du selbst mit einem Klick, es läuft nie automatisch.

## Benutzen

### EPUB hochladen

1. Klicke im Menü auf **eBook**.
2. Klicke bei „EPUB hochladen“ auf **Dateien auswählen**. Du kannst mehrere Dateien auf einmal wählen.
3. Klicke auf **Hochladen**.
4. Oben erscheint eine Meldung. Grün heißt: hat geklappt. Rot heißt: diese Datei wurde abgelehnt, mit Grund.

Das Plugin liest beim Hochladen automatisch **Titel, Autor, Beschreibung und Cover** aus der Datei.

### Nur verlinken (zum Beispiel im Divi Editor)

1. Gehe zu **eBook** und suche das Buch in der Liste.
2. Klicke bei „Download-Link mit Zähler“ auf **Kopieren**. Dann wird jeder Download gezählt. (Der „Direkte Link zur Datei“ geht auch, zählt aber nicht mit.)
3. Öffne die Seite im Divi Editor. Wähle einen Button oder einen Text aus.
4. Füge den Link als Ziel (URL) ein.

Besucher laden die Datei dann direkt herunter.

### Cover mit Vorschau-Fenster

1. Gehe zu **eBook** und klicke beim Buch auf **Shortcode kopieren**.
2. Füge den Shortcode im Divi Editor in ein **Text-Modul** ein (oder in ein **Code-Modul**).
3. Speichere die Seite.

Besucher sehen das Cover. Ein Klick darauf öffnet ein Fenster mit Titel, Autor, Beschreibung und dem Download-Button.

**Beispiele:**

| Du schreibst | Das passiert |
| --- | --- |
| `[mgd_ebook id="12"]` | Cover in Standardgröße (220 Pixel breit) mit Titel darunter |
| `[mgd_ebook id="12" width="300"]` | Cover ist 300 Pixel breit |
| `[mgd_ebook id="12" caption="no"]` | Kein Titel unter dem Cover |
| `[mgd_ebook id="12" button="Jetzt laden"]` | Der Download-Button heißt „Jetzt laden“ |

Die Zahl bei `id` steht schon im kopierten Shortcode. Du musst sie nicht selbst suchen.

### Cover ändern

Das Cover aus der EPUB-Datei passt nicht oder fehlt? Du kannst ein eigenes Bild nehmen:

1. Gehe zu **eBook** und klicke beim Buch auf **Cover ändern**.
2. Klicke auf **Bild aus Mediathek wählen …**. Dort kannst du ein vorhandenes Bild auswählen oder ein neues hochladen.
3. Verschiebe das Bild im Rahmen mit der Maus (oder den Pfeiltasten). Mit dem Regler **Größe** (oder den Tasten **+** und **-**) vergrößerst du es. Der Rahmen zeigt genau den Ausschnitt, der später als Cover erscheint.
4. Klicke auf **Cover speichern**.

Das bisherige Cover kannst du im selben Fenster nochmal neu ausschneiden. Mit **Original aus der EPUB wiederherstellen** holst du das ursprüngliche Cover zurück.

Tipp: Bücher-Cover sind meist im Format 2 zu 3 (hoch). Der Rahmen hat dieses Format.

### Mit dem Divi Builder (Divi 5)

1. Seite im Divi Builder öffnen.
2. Modul hinzufügen und **MGD eBook** oder **MGD Bücherregal** suchen.
3. Bei **MGD eBook** das Buch aus der Liste wählen. Bei **MGD Bücherregal** eine Reihe wählen oder die IDs eintragen.
4. Breite, Bildunterschrift und Knopftext nach Wunsch einstellen. Abstände, Rahmen und Animation gehen wie bei jedem Divi-Modul.

Die Vorschau im Builder zeigt sofort das echte Cover. Klicks auf das Cover öffnen im Builder kein Fenster, das geht erst auf der fertigen Seite.

### Farbe anpassen

Die Knöpfe sind ab Werk dunkelrot. Eine andere Farbe geht mit einer Zeile CSS (zum Beispiel in Divi unter Theme-Optionen → Benutzerdefiniertes CSS):

```css
.mgd-ebook, .mgd-ebook-regal { --mgd-ebook-accent: #2a5b4f; }
```

### Bücherregal (mehrere Bücher nebeneinander)

| Du schreibst | Das passiert |
| --- | --- |
| `[mgd_ebook_regal ids="12,15,9"]` | Drei Bücher nebeneinander, genau in dieser Reihenfolge |
| `[mgd_ebook_regal reihe="Spuren & Stimmen aus Allverund"]` | Alle Bücher dieser Reihe, sortiert nach Band |
| `[mgd_ebook_regal reihe="…" width="150"]` | Kleinere Cover |

Die Reihe und den Band trägst du beim Buch unter **Angaben, Anzeige und Leseprobe** ein. Auf dem Handy stehen immer zwei Bücher nebeneinander.

### Angaben ändern und auswählen, was angezeigt wird

1. Klicke beim Buch auf **Angaben, Anzeige und Leseprobe**.
2. Trage ein, was du ändern möchtest, zum Beispiel den richtigen Titel. Leere Felder nehmen den Wert aus der EPUB-Datei (er steht grau im Feld).
3. Bei **Im Vorschau-Fenster anzeigen** hakst du an, was Besucher sehen sollen.
4. Klicke auf **Speichern**.

### Leseprobe

1. Klicke beim Buch auf **Angaben, Anzeige und Leseprobe**.
2. Haken bei **Leseprobe im Vorschau-Fenster zeigen** setzen und die Zahl der Seiten wählen (1 bis 10).
3. **Speichern**. Das Plugin liest die Seiten einmal aus der Datei.

Besucher sehen dann im Vorschau-Fenster **Leseprobe lesen**. Gezeigt werden Text und Bilder der ersten Seiten. Bei Büchern mit festem Seitenlayout (zum Beispiel aus Affinity) sieht das nicht genau aus wie im E-Book-Reader, aber alles ist lesbar.

### Downloads zählen

- Bei jedem Buch steht, wie oft es heruntergeladen wurde.
- Unter **Downloads pro Monat** siehst du die letzten 12 Monate.
- Es werden nur Zahlen gespeichert: keine IP-Adressen, keine Cookies. Suchmaschinen-Roboter und Link-Vorschauen (zum Beispiel WhatsApp) zählen nicht mit.

### Neue Version eines Buchs hochladen

1. Klicke beim Buch auf **Datei ersetzen**.
2. Neue EPUB-Datei auswählen und **Datei ersetzen** klicken.

Links, Shortcode, Zähler und deine Angaben bleiben gleich. Das Cover wird aus der neuen Datei gelesen.

### Wenn kein Cover in der Datei steckt

Dann zeigt das Plugin eine dunkelgrüne Karte mit dem Buchtitel. Du kannst jederzeit ein eigenes Cover festlegen (siehe oben). Alles andere funktioniert gleich.

### Löschen

In der Liste auf **Löschen** klicken und die Rückfrage bestätigen. Die Datei und das Cover werden dann **endgültig** entfernt. Links und Shortcodes dazu funktionieren danach nicht mehr.

## Datenschutz

- Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.
- So schaltest du das ab: Menü **eBook → Info und Updates** öffnen, den Haken bei „Installation bei Michael Gahn DESIGN melden“ entfernen und **Speichern** klicken. Der Eintrag auf theme.michael-gahn.de wird dann gelöscht. Das Plugin funktioniert genauso weiter.
- Den Schalter sehen nur Administratoren.
- Für Updates und die Liste „Weitere Plugins“ fragt WordPress bei theme.michael-gahn.de und GitHub an. Dabei sehen die Server die Adresse (IP) deines Servers.
- Beim Löschen des Plugins wird nur der Melde-Status entfernt. Deine eBooks bleiben in der Mediathek.

## Weitere Plugins

Unter **eBook → Info und Updates** zeigt das Plugin andere MGD-Plugins als Karten (Pluginempfehlung), mit Bildern, Kategorien, Suche und Filtern. Mit **Details** siehst du mehr. Mit **Installieren** wird ein kostenloses Plugin installiert, aber **nie automatisch aktiviert**. Den Knopf sehen nur Administratoren.

## Wenn etwas nicht klappt

| Problem | Lösung |
| --- | --- |
| „Die Datei ist größer als das Upload-Limit“ | Der Server erlaubt nur kleine Dateien. Der Hoster kann das Limit erhöhen (`upload_max_filesize`). Das aktuelle Limit steht unter dem Uploader. |
| „Das ist keine gültige EPUB-Datei“ | Die Datei ist beschädigt oder wurde nur umbenannt. Bitte neu aus dem Schreibprogramm exportieren. |
| „Es fehlt die PHP-Erweiterung zip“ | Beim Hoster die PHP-Erweiterung „zip“ einschalten lassen. |
| Alle eBooks haben denselben Titel | Das Plugin nimmt den Titel, der **in der EPUB-Datei** gespeichert ist, nicht den Dateinamen. Manche Programme (zum Beispiel Affinity) übernehmen beim Export den Titel vom letzten Buch. Am schnellsten: beim Buch **Angaben, Anzeige und Leseprobe** öffnen und den richtigen Titel eintragen. Oder im Programm bei den Metadaten den Titel ändern, neu exportieren und mit **Datei ersetzen** hochladen. |
| Das Cover fehlt, obwohl die Datei eins hat | Manche Programme speichern das Cover ungewöhnlich. Das Buch funktioniert trotzdem, es zeigt dann die Platzhalter-Karte. |
| Der Download startet nicht, sondern zeigt Text | Der Server kennt die Endung `.epub` nicht. Der Hoster soll den Dateityp `application/epub+zip` für `.epub` eintragen. |

## Für Entwickler

```bash
find mgd-ebook -name '*.php' -exec php -l {} \;   # PHP-Syntax prüfen
node --check mgd-ebook/assets/ebook.js              # JavaScript prüfen
php tests/epub-parser.php                           # Parser-Test
php tests/updater.php                               # Test Update-Weg und Prüfsumme
php tests/install.php                               # Test Installation melden (Free-Lizenz)
php tests/catalog-feed.php                          # Test Empfehlungs-Feed
php tests/kit-wiring.php                            # Test Baukasten-Einbau
php tests/cover-save.php                            # Test Cover speichern (auch mit Bild-Plugins)
php tests/title-downloads.php                       # Test Angaben, Anzeige, Zähler
php tests/shelf.php                                 # Test Bücherregal
php tests/schema.php                                # Test Google-Infos + Divi-Module
python3 scripts/build-package.py                    # ZIP bauen (landet in dist/)
```

Zum Testen in einem echten WordPress gibt es `tests/compose.yaml` (Docker). Mehr dazu in [docs/ENTWICKLUNG.md](docs/ENTWICKLUNG.md).

Weitere Dokumente:

- [Plugin-Verwaltung und Updates](docs/WORDPRESS-VERWALTUNG.md)
- [Plugin-Empfehlung (Weitere Plugins)](docs/PLUGIN-KATALOG.md)
- [Neue Version veröffentlichen](docs/RELEASE.md)
- [Sicherheit](SECURITY.md)
- [Änderungen](CHANGELOG.md)

## Was später dazukommen soll

Dieses Plugin ist der Startpunkt. Weitere Funktionen sind geplant, zum Beispiel eine Leseprobe im Vorschau-Fenster. Die Struktur ist dafür vorbereitet.
