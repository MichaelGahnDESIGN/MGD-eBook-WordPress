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

1. Lade die neueste ZIP-Datei herunter: [Releases](https://github.com/MichaelGahnDESIGN/MGD-eBook-WordPress/releases). Die Datei heißt `mgd-ebook-0.3.0.zip` (die Zahl ändert sich).
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
2. Klicke bei „Link zur Datei“ auf **Link kopieren**.
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

### Wenn kein Cover in der Datei steckt

Dann zeigt das Plugin eine dunkelgrüne Karte mit dem Buchtitel. Du kannst jederzeit ein eigenes Cover festlegen (siehe oben). Alles andere funktioniert gleich.

### Löschen

In der Liste auf **Löschen** klicken und die Rückfrage bestätigen. Die Datei und das Cover werden dann **endgültig** entfernt. Links und Shortcodes dazu funktionieren danach nicht mehr.

## Datenschutz

- Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.
- So schaltest du das ab: Menü **eBook** öffnen, unten bei **Installation melden** den Haken bei „Installation bei Michael Gahn DESIGN melden“ entfernen und **Speichern** klicken. Der Eintrag auf theme.michael-gahn.de wird dann gelöscht. Das Plugin funktioniert genauso weiter.
- Den Schalter sehen nur Administratoren.
- Für Updates und die Liste „Weitere Plugins“ fragt WordPress bei theme.michael-gahn.de und GitHub an. Dabei sehen die Server die Adresse (IP) deines Servers.
- Beim Löschen des Plugins wird nur der Melde-Status entfernt. Deine eBooks bleiben in der Mediathek.

## Weitere Plugins

Unter **eBook → Weitere Plugins** zeigt das Plugin andere MGD-Plugins zur Auswahl (Pluginempfehlung). Dort kannst du sie ansehen und bei Bedarf installieren. Nichts wird automatisch aktiviert.

## Wenn etwas nicht klappt

| Problem | Lösung |
| --- | --- |
| „Die Datei ist größer als das Upload-Limit“ | Der Server erlaubt nur kleine Dateien. Der Hoster kann das Limit erhöhen (`upload_max_filesize`). Das aktuelle Limit steht unter dem Uploader. |
| „Das ist keine gültige EPUB-Datei“ | Die Datei ist beschädigt oder wurde nur umbenannt. Bitte neu aus dem Schreibprogramm exportieren. |
| „Es fehlt die PHP-Erweiterung zip“ | Beim Hoster die PHP-Erweiterung „zip“ einschalten lassen. |
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
