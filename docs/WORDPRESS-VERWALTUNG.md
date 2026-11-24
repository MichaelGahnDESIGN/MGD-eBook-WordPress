# Plugin-Verwaltung und Updates

## Wo finde ich was?

- **MGD Tools → eBook:** Hochladen und Verwalten der EPUB-Dateien. Alle MGD-Plugins hängen unter dem gemeinsamen Menüpunkt **MGD Tools**. Die Seite behält ihre Adresse `admin.php?page=mgd-ebook`, alte Lesezeichen gehen weiter.
- **MGD Tools → Übersicht und Updates** (nur für Administratoren): alle MGD-Plugins mit Version und Update-Stand, dazu der Schalter **Installation bei Michael Gahn DESIGN melden**.
- **MGD Tools → Weitere Plugins:** die Liste mit Bildern, Kategorien, Suche und Filtern.
- **MGD Tools → Lizenz:** ein Schlüssel für alle MGD-Plugins. MGD eBook braucht heute keine Lizenz.
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

- Ab Werk ist der Schalter an. Ausschalten: **MGD Tools → Übersicht und Updates** öffnen, den Haken entfernen, **Speichern**. Der Eintrag auf theme.michael-gahn.de wird dann gelöscht.
- Wenn du das Plugin **löschst** (nicht nur deaktivierst), meldet es sich bei theme.michael-gahn.de ab. Der Eintrag wird gelöscht. Deine eBooks bleiben in der Mediathek.
- Die Meldung läuft nur im Backend, wenn ein Administrator angemeldet ist. Besucher der Website merken nichts.
- Ist theme.michael-gahn.de nicht erreichbar, funktioniert das Plugin trotzdem ganz normal.

## Rückfall bei Problemen

1. Sichere vorher die Datenbank (wie bei jedem Plugin-Update).
2. Gibt es nach einem Update Probleme: das vorherige ZIP aus den GitHub-Releases noch einmal installieren (WordPress fragt, ob es ersetzt werden soll).
3. Hochgeladene EPUB-Dateien und Cover bleiben dabei erhalten. Sie liegen im normalen Upload-Ordner von WordPress (`wp-content/uploads`).
