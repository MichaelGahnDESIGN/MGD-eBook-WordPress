# Plugin-Empfehlung („Weitere Plugins“)

Unter **eBook → Info und Updates** zeigt MGD eBook die anderen Plugins von Michael Gahn DESIGN. Die Liste kommt seit Version 0.4.0 aus dem gemeinsamen Baukasten (`includes/Kit`), wie bei den anderen MGD-Plugins.

Seit 0.4.2 sieht die Liste aus wie die alte „MGD Plugin-Kollektion“: Bilder, Kategorien, Suche, Filter, Details-Fenster und der Knopf **Installieren** (nur für Administratoren, das Plugin wird nie automatisch aktiviert). Die alte Seite `class-mgd-wordpress-plugin-catalog.php` ist entfernt.

⚠️ Der Rest dieser Seite beschreibt den alten Stand bis Version 0.3.0. Einige alte Dateien (`includes/plugin-catalog/`, `assets/plugin-catalog/`) liegen noch im Plugin und kommen in einer späteren Version raus. Die mitgelieferte Ersatzliste `includes/plugin-catalog/plugins.json` nutzt der Baukasten weiter (Feld `slug` = Katalog-Kennung).

## Was kann die Seite?

- Karten mit Icon, Beschreibung und Status (installiert, nicht installiert, Update verfügbar).
- Suche und Filter nach Kategorie, Status und Preis.
- Ein Klick auf **Installieren** lädt das Plugin aus dem GitHub-Release. **Es wird nie automatisch aktiviert.**
- Nur Administratoren dürfen installieren. Jede Installation ist durch einen Sicherheits-Code geschützt.

## Woher kommt die Liste?

1. Zuerst fragt das Plugin `https://theme.michael-gahn.de/api/v1/recommendations/wordpress.json` nach der aktuellen Liste (gleicher Feed wie im Plugin-Kern der Themes).
2. Antwortet die Adresse nicht, nutzt es die mitgelieferte Liste in `mgd-ebook/includes/plugin-catalog/plugins.json`.
3. Die Liste wird bis zu 15 Minuten zwischengespeichert. Der Knopf **Katalog aktualisieren** holt sie sofort neu.

## Ein neues Plugin in die Liste aufnehmen

Eintrag in `mgd-ebook/includes/plugin-catalog/plugins.json` ergänzen (Name, Beschreibung, Kategorie, Icon, Repository-Name). Das Icon als SVG nach `mgd-ebook/assets/plugin-catalog/icons/` legen. Der Repository-Name muss mit `MGD-` oder `MGD_` beginnen.

⚠️ Die Dateien in `includes/plugin-catalog/` und `assets/plugin-catalog/` sind in allen MGD-Plugins gleich. Änderungen daran bitte in allen Plugins gleich machen.
