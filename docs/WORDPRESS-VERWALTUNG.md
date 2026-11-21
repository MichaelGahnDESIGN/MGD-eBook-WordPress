# Plugin-Verwaltung und Updates

## Wo finde ich was?

- **Plugins → Installierte Plugins:** Bei MGD eBook stehen Links zu Einstellungen, Details, Dokumentation, Support und GitHub.
- **Dashboard → Aktualisierungen:** Dort gibt es den Knopf **MGD-Updates jetzt prüfen**. Er löscht nur den Zwischenspeicher der Update-Prüfung und fragt theme.michael-gahn.de neu. Er installiert nichts und ändert keine Einstellungen.
- **eBook:** Hochladen und Verwalten der EPUB-Dateien. Ganz unten (nur für Administratoren): Schalter **Installation bei Michael Gahn DESIGN melden**.
- **eBook → Weitere Plugins:** Pluginempfehlung.

## Wie funktionieren die Updates?

1. Das Plugin liest die Update-Datei `https://theme.michael-gahn.de/plugin-updates/mgd-ebook.json` (Version, Größe, Prüfsumme). Sie wird bis zu 6 Stunden zwischengespeichert.
2. Ist die Version neuer als die installierte, zeigt WordPress ein ganz normales Update an.
3. Du klickst auf **Jetzt aktualisieren**. Das Plugin lädt die ZIP-Datei aus dem GitHub-Release und prüft Größe und Prüfsumme (SHA-256). Passt etwas nicht, wird **nichts** installiert.
4. Automatische Updates sind für dieses Plugin aus.

Wie eine neue Version entsteht, steht in [RELEASE.md](RELEASE.md).

Die Update-Prüfung schickt die Adresse (IP) des Servers an theme.michael-gahn.de und GitHub.

## Installation melden (Free-Lizenz)

Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.

- Ab Werk ist der Schalter an. Ausschalten: **eBook** öffnen, unten den Haken entfernen, **Speichern**. Der Eintrag auf theme.michael-gahn.de wird dann gelöscht.
- Die Meldung läuft nur im Backend, wenn ein Administrator angemeldet ist. Besucher der Website merken nichts.
- Ist theme.michael-gahn.de nicht erreichbar, funktioniert das Plugin trotzdem ganz normal.

## Rückfall bei Problemen

1. Sichere vorher die Datenbank (wie bei jedem Plugin-Update).
2. Gibt es nach einem Update Probleme: das vorherige ZIP aus den GitHub-Releases noch einmal installieren (WordPress fragt, ob es ersetzt werden soll).
3. Hochgeladene EPUB-Dateien und Cover bleiben dabei erhalten. Sie liegen im normalen Upload-Ordner von WordPress (`wp-content/uploads`).
