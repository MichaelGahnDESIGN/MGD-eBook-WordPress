# Änderungen

## 0.7.0 – eBooks verkaufen mit WooCommerce

- Pro Buch einstellbar: **kostenlos** (wie bisher) oder **verkaufen**. Beides geht gleichzeitig auf derselben Website.
- Beim Verkaufen legt das Plugin auf Wunsch ein WooCommerce-Produkt mit Preis an oder nutzt ein vorhandenes einfaches Produkt.
- Die EPUB-Datei wird dann geschützt abgelegt. Laden kann sie nur, wer gekauft hat (Download über WooCommerce). Der direkte Link und der Zähl-Link geben die Datei nicht mehr heraus.
- Im Vorschau-Fenster stehen dann Preis, **In den Warenkorb** und **Zum Produkt**. Die Leseprobe bleibt.
- Käufer-Downloads zählen im Download-Zähler mit.
- Zurück auf „Kostenlos“: Die Datei ist wieder öffentlich, das Produkt wird auf Entwurf gesetzt. Gelöscht wird nichts.
- Google-Infos zeigen bei verkauften Büchern den echten Preis.
- Behoben: Mit Divi öffnete sich das Vorschau-Fenster bei manchen Links nicht, weil Divi den Klick abgefangen hat.
- Ohne WooCommerce ändert sich nichts, alle Bücher bleiben kostenlos.

## 0.6.0 – Divi-Module, Google-Infos und neues Design

- **Divi 5:** Zwei neue Module im Divi Builder: **MGD eBook** (ein Buch aus einer Liste mit allen eBooks auswählen) und **MGD Bücherregal** (eine Reihe wählen oder IDs eintragen). Im Builder siehst du sofort die echte Vorschau. Abstände, Rahmen, Hintergrund und Animation stellst du wie bei jedem Divi-Modul ein. Ohne Divi 5 passiert nichts, die Shortcodes gehen weiter.
- **Google-Infos:** Für jedes angezeigte eBook steht unsichtbar eine Buch-Beschreibung nach schema.org in der Seite (Titel, Autor, Cover, Reihe, Band und weitere Angaben, die du zum Anzeigen eingeschaltet hast). Suchmaschinen erkennen so, dass es ein Buch ist.
- **Neues Design:** Cover sehen aus wie echte Bücher (Buchrücken, Seitenkante, Schatten, leichtes Kippen beim Darüberfahren). Im Bücherregal stehen die Bücher auf einem Regalbrett. Das Vorschau-Fenster zeigt die Reihe über dem Titel, eine aufgeräumte Angaben-Tabelle und Knöpfe mit Symbol. Die Leseprobe öffnet sich mit eigenem Knopf und sieht aus wie Buchseiten mit Seitenzahl.
- Wer am Gerät „Bewegung reduzieren“ eingestellt hat, bekommt keine Animationen. Die Farbe der Knöpfe lässt sich per CSS-Variable `--mgd-ebook-accent` an die Website anpassen.
- Verwaltung: aufklappbare Bereiche und Download-Zähler übersichtlicher gestaltet.

## 0.5.0 – Angaben, Leseprobe, Bücherregal und Download-Zähler

- **Angaben bearbeiten:** Titel, Autor, Beschreibung, Reihe, Band, Verlag, Erscheinungsjahr, ISBN und Sprache lassen sich pro Buch ändern. Leere Felder nehmen den Wert aus der EPUB-Datei.
- **Anzeige wählen:** Pro Buch einstellbar, welche Angaben im Vorschau-Fenster stehen (zum Beispiel Beschreibung an, ISBN aus).
- **Leseprobe:** Pro Buch an oder aus, mit 1 bis 10 Seiten. Text und Bilder der ersten Seiten erscheinen im Vorschau-Fenster unter „Leseprobe lesen“. Eine reine Cover-Seite am Anfang wird übersprungen.
- **Bücherregal:** `[mgd_ebook_regal ids="12,15,9"]` zeigt mehrere Bücher nebeneinander, `[mgd_ebook_regal reihe="…"]` alle Bücher einer Reihe nach Band sortiert.
- **Download-Zähler:** Jedes Buch hat einen „Download-Link mit Zähler“. In der Liste steht, wie oft es heruntergeladen wurde, dazu eine Übersicht pro Monat. Gespeichert werden nur Zahlen, keine IP-Adressen, keine Cookies. Roboter und Link-Vorschauen zählen nicht mit. Der Download-Knopf im Vorschau-Fenster zählt automatisch.
- **Datei ersetzen:** Neue Version eines Buchs hochladen. Link, Shortcode, Zähler und deine Angaben bleiben gleich.
- **Hinweis bei großen Dateien:** Ab 20 MB gibt es einen Tipp, wie die Datei kleiner wird.
- Leseerkennung: Verlag, Jahr, ISBN und Reihe werden jetzt auch aus der EPUB-Datei gelesen, wenn sie drinstehen.
- Baukasten auf den neuen Stand gebracht.

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
