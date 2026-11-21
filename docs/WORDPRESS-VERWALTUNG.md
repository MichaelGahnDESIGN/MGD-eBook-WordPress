# Plugin-Verwaltung und Updates

## Wo finde ich was?

- **Plugins → Installierte Plugins:** Bei MGD eBook stehen die Links **eBooks** und **Info und Updates**.
- **eBook:** Hochladen und Verwalten der EPUB-Dateien.
- **eBook → Info und Updates** (nur für Administratoren): eine Seite mit allem auf einen Blick. Dort stehen die Version, ob es ein Update gibt, die Liste „Weitere Plugins“ und der Schalter **Installation bei Michael Gahn DESIGN melden**.
- **Dashboard → Aktualisierungen:** Hier zeigt WordPress das Update an, wie bei jedem anderen Plugin.

## Wie funktionieren die Updates?

1. Das Plugin liest die Update-Datei `https://theme.michael-gahn.de/plugin-updates/mgd-ebook.json` (Version, Größe, Prüfsumme). Das Ergebnis wird bis zu 12 Stunden gespeichert.
2. Ist die Version neuer als die installierte, zeigt WordPress ein ganz normales Update an.
3. Du klickst auf **Jetzt aktualisieren**. Das Plugin lädt die ZIP-Datei aus dem GitHub-Release und prüft Größe und Prüfsumme (SHA-256). Passt etwas nicht, wird **nichts** installiert.
4. Automatische Updates sind für dieses Plugin aus.

Wie eine neue Version entsteht, steht in [RELEASE.md](RELEASE.md).

Die Update-Prüfung schickt die Adresse (IP) des Servers an theme.michael-gahn.de und GitHub.

## Installation melden (Free-Lizenz)

Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.

- Ab Werk ist der Schalter an. Ausschalten: **eBook → Info und Updates** öffnen, den Haken entfernen, **Speichern**. Der Eintrag auf theme.michael-gahn.de wird dann gelöscht.
- Wenn du das Plugin **löschst** (nicht nur deaktivierst), meldet es sich bei theme.michael-gahn.de ab. Der Eintrag wird gelöscht. Deine eBooks bleiben in der Mediathek.
- Die Meldung läuft nur im Backend, wenn ein Administrator angemeldet ist. Besucher der Website merken nichts.
- Ist theme.michael-gahn.de nicht erreichbar, funktioniert das Plugin trotzdem ganz normal.

## Rückfall bei Problemen

1. Sichere vorher die Datenbank (wie bei jedem Plugin-Update).
2. Gibt es nach einem Update Probleme: das vorherige ZIP aus den GitHub-Releases noch einmal installieren (WordPress fragt, ob es ersetzt werden soll).
3. Hochgeladene EPUB-Dateien und Cover bleiben dabei erhalten. Sie liegen im normalen Upload-Ordner von WordPress (`wp-content/uploads`).
