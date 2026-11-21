# Plugin-Empfehlung („Weitere Plugins“)

Unter **eBook → Weitere Plugins** zeigt MGD eBook die anderen Plugins von Michael Gahn DESIGN. Das ist dieselbe Seite wie in den anderen MGD-Plugins.

## Was kann die Seite?

- Karten mit Icon, Beschreibung und Status (installiert, nicht installiert, Update verfügbar).
- Suche und Filter nach Kategorie, Status und Preis.
- Ein Klick auf **Installieren** lädt das Plugin aus dem GitHub-Release. **Es wird nie automatisch aktiviert.**
- Nur Administratoren dürfen installieren. Jede Installation ist durch einen Sicherheits-Code geschützt.

## Woher kommt die Liste?

1. Zuerst fragt das Plugin `theme.michael-gahn.de` nach der aktuellen Liste.
2. Antwortet die Adresse nicht, nutzt es die mitgelieferte Liste in `mgd-ebook/includes/plugin-catalog/plugins.json`.
3. Die Liste wird bis zu 6 Stunden zwischengespeichert. Der Knopf **Katalog aktualisieren** holt sie sofort neu.

## Ein neues Plugin in die Liste aufnehmen

Eintrag in `mgd-ebook/includes/plugin-catalog/plugins.json` ergänzen (Name, Beschreibung, Kategorie, Icon, Repository-Name). Das Icon als SVG nach `mgd-ebook/assets/plugin-catalog/icons/` legen. Der Repository-Name muss mit `MGD-` oder `MGD_` beginnen.

⚠️ Die Dateien in `includes/plugin-catalog/`, `includes/class-mgd-wordpress-plugin-catalog.php` und `assets/plugin-catalog/` sind in allen MGD-Plugins gleich. Änderungen daran bitte in allen Plugins gleich machen.
