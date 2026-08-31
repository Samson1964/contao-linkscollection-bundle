# Die Linksammlung im Backend

Das Backend-Modul liegt unter *Inhalte → Linksammlung*. Es kennt zwei Ebenen:
die Kategorien und, eine Ebene darunter, die Links.

## Kategorien

Die Startansicht zeigt den Kategoriebaum. Hinter jedem Kategorietitel stehen
drei Angaben:

```
Schach allgemein  (12/57 - 83% Links in Ordnung)
                    │  │    └── Anteil der Links, die bei der letzten Prüfung
                    │  │        erreichbar waren
                    │  └── Links einschließlich aller Unterkategorien
                    └── Links unmittelbar in dieser Kategorie
```

Der Prozentwert ist farbig hinterlegt und wandert von Rot über Gelb nach Grün.
So fallen vernachlässigte Kategorien ins Auge. Die drei Zahlen werden **nicht**
laufend fortgeschrieben, sondern erst beim Aufruf von *Statistik* neu berechnet
(siehe unten).

Ein Klick auf den Kategorietitel schränkt den Baum auf diese Kategorie ein; die
Navigationsleiste darüber führt wieder heraus. Der gewählte Ausschnitt bleibt
gespeichert, bis er über *Alle* zurückgesetzt wird.

### Die Schaltflächen einer Kategorie

| Symbol | Bedeutung |
|---|---|
| Stift | Links dieser Kategorie anzeigen und bearbeiten |
| Kopfzeile | Einstellungen der Kategorie selbst bearbeiten |
| Schere | Kategorie an eine andere Stelle im Baum verschieben |
| Auge | Kategorie veröffentlichen oder verbergen |
| Lupe | Alle gespeicherten Werte der Kategorie ansehen |

Ein **Löschen-Symbol gibt es bewusst nicht**: Mit der Kategorie verschwänden
auch sämtliche Links darin. Nicht mehr benötigte Kategorien werden über das
Auge verborgen; endgültig entfernen lassen sie sich nur direkt in der
Datenbank.

### Felder einer Kategorie

* **Kategorie** — der angezeigte Name.
* **Alias** — wird beim Speichern automatisch aus dem Namen gebildet.
* **Zugriffsschutz** — beschränkt die Kategorie auf bestimmte Mitgliedergruppen.
* **Veröffentlichung** — nur veröffentlichte Kategorien erscheinen im Frontend
  und werden mitgezählt.

## Links

Innerhalb einer Kategorie zeigt jede Zeile den Link mit seinem Favicon, dem
Sprachfähnchen, dem HTTP-Status der letzten Prüfung und dem erkannten
Content-Management-System:

```
                                              Contao  200   vor 12 Tg.
[Favicon]  Deutscher Schachbund - 🇩🇪 https://www.schachbund.de
           Der Dachverband des deutschen Schachs
```

Der Statuscode ist grün (erreichbar), gelb (Weiterleitung) oder rot (Fehler
oder Host nicht erreichbar) hinterlegt. Der Zeitpunkt daneben nennt, wie lange
die letzte Prüfung her ist; wann die nächste ansteht, verrät der Mauszeiger.

### Felder eines Links

| Feld | Bedeutung |
|---|---|
| **Titel**, **URL** | Pflichtangaben; alles Übrige ist optional |
| **Sprache** | Steuert das Fähnchen im Frontend. Wird beim Speichern automatisch gefüllt, wenn die verlinkte Seite ihre Sprache angibt |
| **URL offline** | Schaltet die Weiterleitung ins Webarchiv ein (siehe unten) |
| **Populärer Link** | Sortiert den Link innerhalb der Kategorie nach oben |
| **Neues Fenster** | Öffnet das Ziel in einem neuen Browserfenster |
| **Beschreibung** | Erscheint unter dem Titel; HTML ist erlaubt |
| **Linkhistorie** | Frühere Adressen mit ihrem Gültigkeitsende — reine Gedächtnisstütze |
| **Meldungen** | Was Besucher über das Frontend-Formular gemeldet haben |
| **Wer hat den Link gemeldet?** | Name und E-Mail bei Vorschlägen aus dem Frontend |
| **Zugriffe** | Zähler der Klicks im Frontend |
| **Letzter Aufruf** | IP und Zeitpunkt des letzten Klicks; verhindert Mehrfachzählung |
| **Erreichbarkeit** | Ergebnis der letzten automatischen Prüfung |
| **Veröffentlichung** | Nur veröffentlichte Links erscheinen im Frontend |

**Beim Speichern ruft die Erweiterung die eingetragene Adresse auf.** Sie holt
dabei Statuscode, Statustext, Sprache, CMS und das Favicon. Das dauert einen
Moment und setzt voraus, dass der Server ins Internet darf.

### Links dauerhaft offline: das Webarchiv

Verschwindet eine Seite endgültig, muss der Link nicht gelöscht werden. Ein
Haken bei **URL offline** genügt: Besucher landen dann nicht mehr auf der toten
Adresse, sondern beim letzten Schnappschuss der Seite bei
[archive.org](https://web.archive.org). Der Adressvorsatz dafür steht in den
Einstellungen.

## Die vier zusätzlichen Ansichten

Über der Liste stehen vier Schaltflächen, die es in beiden Ebenen gibt.

### Linkliste

Zeigt **alle** Links der Sammlung auf einmal, unabhängig von ihrer Kategorie,
und bietet eine Volltextsuche über Titel, URL, Beschreibung und CMS. Der
Suchbegriff wird als regulärer Ausdruck ausgewertet; die Auswahl bleibt
gespeichert, bis sie geändert wird. Die Ansicht zeigt höchstens 500 Links und
prüft dabei bewusst **keine** Adressen nach — sonst würde jeder Aufruf hunderte
Zugriffe ins Internet auslösen.

### Prüfbericht

Fasst zusammen, was Aufmerksamkeit braucht:

* wie viele Kategorien und Links veröffentlicht sind,
* alle Links, zu denen Besucher Probleme gemeldet haben, mit Meldedatum und
  Anzahl der Meldungen,
* alle noch nicht veröffentlichten Links — das sind in der Regel die
  Vorschläge, die über das Frontend eingegangen sind.

Aus jeder Zeile führen zwei Symbole direkt zum Link beziehungsweise zu seiner
Kategorie.

### Statistik

Berechnet die Zähler aller Kategorien neu: Anzahl der Links, Anzahl der neuen
Links und den Anteil erreichbarer Links. Danach kehrt das Backend zur
Kategorieliste zurück.

Als erreichbar zählt ein Link, der bei der letzten Prüfung den Statuscode 200
geliefert hat **und** dessen Prüfung nicht älter als ein Jahr ist. Der Aufruf
lohnt nach größeren Änderungen und immer dann, wenn die Zahlen im Baum nicht
mehr zum Inhalt passen.

### Prüfen

Prüft auf Zuruf alle Links der gerade angezeigten Struktur neu, statt auf die
schleichende Prüfung beim Durchblättern zu warten (siehe „Beim Speichern ruft
die Erweiterung die eingetragene Adresse auf" oben — dasselbe passiert hier,
nur gesammelt für viele Links auf einmal). Was genau geprüft wird, hängt davon
ab, wo der Knopf gedrückt wird:

* **In der Linkliste einer Kategorie:** nur die Links dieser einen Kategorie.
* **Im Kategoriebaum, mit angeklicktem Knoten:** dieser Knoten und alle seine
  Unterkategorien.
* **Im Kategoriebaum, ohne angeklickten Knoten:** die gesamte Sammlung — bei
  vielen hundert Links entsprechend viele Zugriffe ins Internet. Die
  angezeigte Anzahl vor dem Start zeigt, worauf man sich einlässt.

Ein Klick fragt zur Sicherheit noch einmal nach, dann läuft die Prüfung im
Hintergrund im Browser weiter: Für jeden Link geht ein einzelner Abruf an den
Server, die Fortschrittsanzeige zählt mit. Antwortet eine Zielseite nicht oder
sehr langsam, bremst das nur diesen einen Link aus — die übrigen werden trotzdem
der Reihe nach fertig geprüft. Das Browserfenster kann während der Prüfung
geöffnet bleiben; die Ergebnisse (Statuscode, Sprache, CMS, Favicon) stehen erst
nach einem Neuladen der Seite in der Liste.

## Einstellungen

Unter *System → Einstellungen* gibt es den Abschnitt *Linksammlung*:

| Einstellung | Vorgabe | Bedeutung |
|---|---|---|
| Anzahl Tage Neuer Link | 60 | So lange gilt ein Link als neu und erscheint unter *Neue Links* |
| Anzahl Tage Prüfung | 365 | Nach dieser Zeit wird die Adresse eines Links beim nächsten Anzeigen im Backend erneut geprüft |
| Webarchivlink | `https://web.archive.org/web/*/` | Adressvorsatz für Links, die als offline markiert sind |

Wie viele Links höchstens in einem Seitenaufruf nachgeprüft werden, steht in
der `config.php` der Erweiterung (`linkscollection_maxlinkcheck`, Vorgabe 5).
Der Wert bremst den Seitenaufbau bewusst aus: Jede Prüfung ist ein Zugriff auf
eine fremde Webseite.

## Systemprotokoll

Die Erweiterung vermerkt unter *System → Systemlog*, wenn ein Link angelegt
oder geändert wurde, wenn ein Besucher einen Link vorgeschlagen oder ein
Problem gemeldet hat, bei jeder Weiterleitung und bei jeder Suche.
