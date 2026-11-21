# Entwicklung und Tests

## Aufbau

| Datei | Aufgabe |
| --- | --- |
| `mgd-ebook/mgd-ebook.php` | Startdatei, Plugin-Kopf, Updater-Anmeldung |
| `includes/class-mgd-ebook-epub.php` | Liest eine EPUB-Datei (Prüfung, Titel, Autor, Cover) |
| `includes/class-mgd-ebook-library.php` | Erlaubt .epub, speichert Daten und Cover, Liste |
| `includes/class-mgd-ebook-admin.php` | Menü, Uploader, Löschen |
| `includes/class-mgd-ebook-renderer.php` | Shortcode `[mgd_ebook]` |
| `templates/` | HTML für Backend-Seite und Shortcode |
| `assets/` | CSS und JavaScript |
| `includes/class-mgd-plugin-admin-tools.php` | Gemeinsamer GitHub-Updater (in allen MGD-Plugins gleich) |
| `includes/plugin-catalog/`, `assets/plugin-catalog/` | Pluginempfehlung (in allen MGD-Plugins gleich) |

## Wie werden Daten gespeichert?

Jede EPUB-Datei ist ein normaler **Anhang** in der WordPress-Mediathek (Typ `application/epub+zip`). Zusatzdaten stehen in zwei Metafeldern:

- `_mgd_ebook`: Titel, Autor, Beschreibung, Sprache
- `_mgd_ebook_cover`: Pfad zum Cover unter `uploads/mgd-ebook-covers/` (aus der EPUB oder vom Benutzer zugeschnitten)

## Cover-Editor

Der Editor ist reines JavaScript in `assets/ebook-admin.js` (keine Bibliothek). Er schickt den Ausschnitt als Brüche (0 bis 1, relativ zur Bildgröße) per AJAX (`mgd_ebook_cover`) an den Server. Der Server schneidet mit dem WordPress-Bildeditor zu (`MGD_Ebook_Library::set_cover_from_image`). Die Mediathek kommt von `wp_enqueue_media()`. Der Editor ist bewusst keine `<dialog>`, weil das WordPress-Mediathek-Fenster sonst hinter ihm läge.

Darum erscheinen EPUB-Dateien auch in der normalen Mediathek, und der Divi Editor findet sie dort.

## Tests

```bash
php tests/epub-parser.php
```

Der Test baut künstliche EPUB-Dateien (gültig, ohne Cover, defekt, mit Pfad-Tricks, mit XML-Entitäten, mit riesigem Cover) und prüft den Parser. Er braucht kein WordPress.

### Echtes WordPress zum Ausprobieren

```bash
docker compose -f tests/compose.yaml up -d
docker compose -f tests/compose.yaml exec -T cli wp --path=/var/www/html core install --url=http://localhost:8089 --title=Test --admin_user=admin --admin_password=ein-test-passwort --admin_email=test@example.com --skip-email
docker compose -f tests/compose.yaml exec -T cli wp --path=/var/www/html plugin activate mgd-ebook
```

Dann `http://localhost:8089/wp-admin/` öffnen. Aufräumen: `docker compose -f tests/compose.yaml down`.
