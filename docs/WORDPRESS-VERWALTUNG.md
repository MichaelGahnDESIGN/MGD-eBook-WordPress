# Plugin-Verwaltung und Updates

## Wo finde ich was?

- **Plugins → Installierte Plugins:** Bei MGD eBook stehen Links zu Einstellungen, Details, Dokumentation, Support und GitHub.
- **Dashboard → Aktualisierungen:** Dort gibt es den Knopf **MGD-Updates jetzt prüfen**. Er löscht nur den Zwischenspeicher der Update-Prüfung und fragt GitHub neu. Er installiert nichts und ändert keine Einstellungen.
- **eBook:** Hochladen und Verwalten der EPUB-Dateien.
- **eBook → Weitere Plugins:** Pluginempfehlung.

## Wie funktionieren die Updates?

Das Plugin schaut bei GitHub nach der neuesten Version (Release). Ist sie neuer als die installierte, zeigt WordPress ein ganz normales Update an. Du klickst auf **Jetzt aktualisieren**, fertig.

Damit das klappt, muss im GitHub-Repository ein Release existieren, das eine ZIP-Datei namens `mgd-ebook-<Version>.zip` enthält. Wie man das macht, steht in [RELEASE.md](RELEASE.md).

Die Update-Prüfung schickt die Adresse des Servers an GitHub. Sie ist bei MGD-Plugins immer gleich aufgebaut.

## Rückfall bei Problemen

1. Sichere vorher die Datenbank (wie bei jedem Plugin-Update).
2. Gibt es nach einem Update Probleme: das vorherige ZIP aus den GitHub-Releases noch einmal installieren (WordPress fragt, ob es ersetzt werden soll).
3. Hochgeladene EPUB-Dateien und Cover bleiben dabei erhalten. Sie liegen im normalen Upload-Ordner von WordPress (`wp-content/uploads`).
