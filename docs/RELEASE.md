# Neue Version veröffentlichen

So wird aus dem Code eine Version, die sich auf allen Websites über das normale WordPress-Update installieren lässt.

## Schritt für Schritt

1. **Versionsnummer erhöhen** an drei Stellen in `mgd-ebook/mgd-ebook.php`:
   - im Kopf: `Version: 0.2.0`
   - weiter unten: `define( 'MGD_EBOOK_VERSION', '0.2.0' );` und `'version' => '0.2.0'`
2. **Änderungen** oben in `CHANGELOG.md` eintragen.
3. **Prüfen:**
   ```bash
   find mgd-ebook -name '*.php' -exec php -l {} \;
   php tests/epub-parser.php
   php tests/updater.php
   php tests/install.php
   php tests/catalog-feed.php
   ```
4. **ZIP bauen:**
   ```bash
   python3 scripts/build-package.py
   ```
   Die Datei liegt danach in `dist/` und heißt `mgd-ebook-0.2.0.zip`. Daneben liegt `mgd-ebook-0.2.0.zip.sha256` (Prüfsumme, nur mit Dateinamen).
5. **Alles committen und pushen.**
6. **Release auf GitHub anlegen** (Tag mit `v` davor):
   ```bash
   gh release create v0.2.0 dist/mgd-ebook-0.2.0.zip dist/mgd-ebook-0.2.0.zip.sha256 --title "MGD eBook 0.2.0" --notes "Siehe CHANGELOG.md"
   ```
7. **Update-Datei erzeugen** (im Repository MichaThemeV5-Pro):
   ```bash
   php bin/update-simple-file.php --zip=dist/mgd-ebook-0.2.0.zip --product=mgd-ebook --root-dir=mgd-ebook --download-url=https://github.com/MichaelGahnDESIGN/MGD-eBook-WordPress/releases/download/v0.2.0/mgd-ebook-0.2.0.zip --out=mgd-ebook.json
   ```
   Die Datei kommt nach `public/plugin-updates/mgd-ebook.json` auf theme.michael-gahn.de. Erst dann sehen die Websites das Update.
8. Auf den Websites unter **Dashboard → Aktualisierungen → MGD-Updates jetzt prüfen** klicken. Das Update erscheint.

## Wichtig

- ⚠️ Der Dateiname muss genau `mgd-ebook-<Version>.zip` heißen und der Tag `v<Version>` (z. B. `v0.2.0`). Der Updater baut die Download-Adresse daraus und lehnt alles andere ab.
- ⚠️ Ohne neue Update-Datei auf theme.michael-gahn.de gibt es kein Update, auch wenn das Release auf GitHub schon da ist.
- ⚠️ Das Repository muss **öffentlich** sein. Die ZIP-Datei wird ohne Anmeldung von GitHub geladen.
