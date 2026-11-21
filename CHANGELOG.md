# Änderungen

## 0.4.2 – Neue Ansicht „Weitere Plugins“

- **Weitere Plugins** (unter **eBook → Info und Updates**) sieht jetzt aus wie die MGD Plugin-Kollektion: Bilder, Kategorien, Suche, Filter und ein Fenster mit Details.
- Neu: Knopf **Installieren** bei kostenlosen Plugins (nur für Administratoren). Die Datei wird vor der Installation geprüft. Das Plugin wird nie automatisch aktiviert.
- Die alte Seite „Weitere Plugins“ ist ganz entfernt.

## 0.4.1 – Cover wird wieder erkannt

- Behoben: Auf Websites mit einem Bild-Plugin, das Bilder automatisch in AVIF oder WebP umwandelt (zum Beispiel „Modern Image Formats“ oder EWWW), stand bei jedem eBook „kein Cover in der Datei“. Das Cover wurde zwar gespeichert, aber unter einem anderen Dateinamen. Jetzt bleibt das Cover PNG oder JPEG und wird gefunden. Das gilt auch für **Cover ändern**.
- Bereits hochgeladene eBooks: einmal **Cover ändern → Original aus der EPUB wiederherstellen** klicken, dann ist das Cover da.

## 0.4.0 – Gemeinsamer Baukasten

- MGD eBook nutzt jetzt den gemeinsamen Baukasten der MGD-Plugins. Updates, die Meldung der Installation und die Empfehlungen laufen darüber.
- Neue Seite **eBook → Info und Updates** (nur für Administratoren): Version, Update-Stand, Weitere Plugins und der Schalter „Installation bei Michael Gahn DESIGN melden“ auf einen Blick. Der Schalter steht nicht mehr unten auf der eBook-Seite.
- Beim Löschen des Plugins meldet es sich bei theme.michael-gahn.de ab. Deine eBooks bleiben erhalten.
- Die alte Seite „Weitere Plugins“ und der alte Updater sind abgeschaltet. Die gespeicherte Installations-ID bleibt erhalten.

## 0.3.0 – Gleicher Stand wie die anderen MGD-Plugins

- Neu: Schalter **Installation bei Michael Gahn DESIGN melden** auf der Seite **eBook** (nur für Administratoren). Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.
- Updates kommen jetzt über theme.michael-gahn.de. Die ZIP-Datei wird vor dem Einspielen gegen Größe und Prüfsumme (SHA-256) geprüft. Automatische Updates sind aus, du klickst selbst.
- **Weitere Plugins** nutzt den neuen Empfehlungs-Feed (`/api/v1/recommendations`) und fragt alle 15 Minuten neu statt alle 6 Stunden.
- Beim Löschen des Plugins wird der Melde-Status entfernt. eBooks bleiben erhalten.

## 0.2.0 – Cover ändern

- Neuer Knopf **Cover ändern** bei jedem eBook.
- Bild aus der Mediathek wählen (dort kann man auch neue Bilder hochladen).
- Kleiner Editor: Bild mit Maus oder Pfeiltasten verschieben, mit Regler oder Plus/Minus die Größe einstellen. Der Rahmen zeigt den späteren Ausschnitt.
- Das vorhandene Cover lässt sich ebenfalls neu ausschneiden.
- **Original aus der EPUB wiederherstellen** holt das ursprüngliche Cover zurück.
- Das neue Cover erscheint sofort in der Liste, ohne die Seite neu zu laden.

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
