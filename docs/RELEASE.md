# Neue Version veröffentlichen

So wird aus dem Code eine Version, die sich auf allen Websites über das normale WordPress-Update installieren lässt.

## Schritt für Schritt

1. **Versionsnummer erhöhen** an zwei Stellen in `mgd-ebook/mgd-ebook.php`:
   - im Kopf: `Version: 0.1.1`
   - weiter unten: `define( 'MGD_EBOOK_VERSION', '0.1.1' );` und `'version' => '0.1.1'`
2. **Änderungen** oben in `CHANGELOG.md` eintragen.
3. **Prüfen:**
   ```bash
   find mgd-ebook -name '*.php' -exec php -l {} \;
   php tests/epub-parser.php
   ```
4. **ZIP bauen:**
   ```bash
   python3 scripts/build-package.py
   ```
   Die Datei liegt danach in `dist/` und heißt `mgd-ebook-0.1.1.zip`.
5. **Alles committen und pushen.**
6. **Release auf GitHub anlegen** (Tag mit `v` davor):
   ```bash
   gh release create v0.1.1 dist/mgd-ebook-0.1.1.zip --title "MGD eBook 0.1.1" --notes "Siehe CHANGELOG.md"
   ```
7. Auf den Websites unter **Dashboard → Aktualisierungen → MGD-Updates jetzt prüfen** klicken. Das Update erscheint.

## Wichtig

- ⚠️ Der Dateiname muss genau `mgd-ebook-<Version>.zip` heißen. Sonst findet der Updater die Datei nicht.
- ⚠️ Der Tag muss die Form `v0.1.1` haben (drei Zahlen). Vorab-Versionen (Pre-Release) werden ignoriert.
- ⚠️ Das Repository muss **öffentlich** sein. Der Updater fragt GitHub ohne Anmeldung.
