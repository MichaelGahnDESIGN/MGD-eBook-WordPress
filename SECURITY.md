# Sicherheit

Bitte melde Sicherheitsprobleme **nicht** öffentlich als Issue. Schreibe stattdessen vertraulich an Michael Gahn DESIGN (Kontakt über michael-gahn.de). Bitte keine Zugangsdaten, keine echten Kundendaten und keine echten Webadressen in die Meldung packen.

## Was das Plugin tut

- Nur Benutzer mit dem Recht „Dateien hochladen“ sehen den Menüpunkt **eBook**.
- Jede Aktion (Hochladen, Löschen) ist durch einen Sicherheits-Code (Nonce) und eine Rechteprüfung geschützt.
- Eine .epub-Datei wird vor dem Speichern geprüft: gültiges ZIP, richtige EPUB-Kennung, lesbare Paketdatei. Sie wird **nicht entpackt**. Nur Titel, Autor, Beschreibung und das Cover werden gelesen.
- Gegen kaputte oder bösartige Dateien gibt es Grenzen für Größe und Anzahl der Einträge. XML mit Entitäten wird abgelehnt.
- Das Cover wird neu berechnet (verkleinert und neu gespeichert). So bleibt nichts Fremdes im Bild hängen. SVG-Cover werden nicht verwendet.
- Texte aus der EPUB-Datei werden bei der Ausgabe immer maskiert.
- Das Plugin lädt im Frontend nichts von fremden Servern nach.
- Updates: Version und Prüfsumme kommen von theme.michael-gahn.de, die ZIP-Datei aus dem GitHub-Release. Vor dem Einspielen werden Größe und SHA-256 geprüft. Weiterleitungen sind nur zu den Download-Servern von GitHub erlaubt.
- Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen. Nur im Backend, nur für Administratoren, nie im Frontend.
- Beim Ändern des Covers sind nur echte Bilder (JPEG, PNG, GIF, WebP) erlaubt, kein SVG. Der Ausschnitt wird auf dem Server neu berechnet und gespeichert. Die Anfrage ist durch Sicherheits-Code und Rechteprüfung geschützt.
