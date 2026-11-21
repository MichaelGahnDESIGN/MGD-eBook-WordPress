# Änderungen

## 0.2.0 – Gleicher Stand wie die anderen MGD-Plugins

- Neu: Schalter **Installation bei Michael Gahn DESIGN melden** auf der Seite **eBook** (nur für Administratoren). Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.
- Updates kommen jetzt über theme.michael-gahn.de. Die ZIP-Datei wird vor dem Einspielen gegen Größe und Prüfsumme (SHA-256) geprüft. Automatische Updates sind aus, du klickst selbst.
- **Weitere Plugins** nutzt den neuen Empfehlungs-Feed (`/api/v1/recommendations`) und fragt alle 15 Minuten neu statt alle 6 Stunden.
- Beim Löschen des Plugins wird der Melde-Status entfernt. eBooks bleiben erhalten.

## 0.1.1 – Übersichtlicheres Backend

- Neu gestaltete Seite **eBook**: Kopfbereich, Schritt 1 (hochladen) und Schritt 2 (kopieren).
- Uploader als Ablagefläche: Dateien hineinziehen oder klicken. Gewählte Dateien werden vor dem Hochladen aufgelistet.
- eBooks als Karten mit Cover, Titel, Autor und zwei Kopieren-Knöpfen (Link und Shortcode).
- Der Hochladen-Knopf wird während des Uploads gesperrt (kein doppeltes Hochladen).
- Hilfe ist einklappbar, damit die Seite ruhig bleibt.

## 0.1.0 – Erste Version

- Neuer Menüpunkt **eBook** im WordPress-Backend.
- EPUB-Dateien hochladen (auch mehrere auf einmal). Jede Datei wird vorher geprüft.
- Liste aller hochgeladenen eBooks mit Cover, Titel und Autor.
- Pro Datei: Link zur Datei (zum Verlinken, z. B. im Divi Editor) und Shortcode zum Kopieren.
- Shortcode `[mgd_ebook id="…"]`: zeigt das Cover, ein Klick öffnet ein Vorschau-Fenster mit Infos und Download-Button.
- EPUB-Dateien sind jetzt auch in der normalen Mediathek erlaubt.
- Updates direkt aus GitHub (wie bei den anderen MGD-Plugins).
- Unterpunkt **Weitere Plugins** mit der MGD Plugin-Empfehlung.
