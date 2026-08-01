# Die Linksammlung im Frontend

## Das Modul einbinden

1. Unter *Layout → Module* ein neues Modul vom Typ **Linksammlung** anlegen und
   ihm einen Namen geben. Mehr ist nicht einzustellen — die Linksammlung hat
   keine modulbezogenen Optionen, sie zeigt immer den gesamten Bestand.
2. Eine Seite anlegen, auf der die Linksammlung erscheinen soll.
3. Das Modul entweder über ein Inhaltselement vom Typ **Modul** in einen Artikel
   dieser Seite einfügen oder es im Seitenlayout einem Bereich zuweisen.

Die Linksammlung braucht **eine eigene Seite**. Sie belegt alle zusätzlichen
Adressbestandteile für sich und ändert den Seitentitel, sobald eine Kategorie
geöffnet ist. Ein zweites Modul mit eigener Navigation auf derselben Seite
würde sich damit ins Gehege kommen.

## Die Adressen der Ansichten

Alle Ansichten laufen über dieselbe Seite; welche erscheint, steuern
zusätzliche Bestandteile in der Adresse. Liegt die Linksammlung etwa unter
`/linksammlung.html`, dann führen dorthin:

| Adresse | Ansicht |
|---|---|
| `/linksammlung.html` | Oberste Kategorieebene |
| `/linksammlung/category/12.html` | Kategorie 12 mit ihren Unterkategorien und Links |
| `/linksammlung/link/345.html` | Einzelansicht des Links 345 samt Meldeformular |
| `/linksammlung/view/toplinks.html` | Die 100 meistgeklickten Links |
| `/linksammlung/view/newlinks.html` | Zuletzt aufgenommene Links |
| `/linksammlung/view/search.html` | Suchmaske |
| `/linksammlung/view/sendlink.html` | Formular für einen neuen Link |

Diese Adressen erzeugt das Modul selbst; von Hand einzutragen ist nichts. Ist
eine Kategorie nicht vorhanden oder nicht veröffentlicht, leitet das Modul auf
die oberste Ebene zurück.

Zwei weitere Adressen gehören nicht zur Seite, sondern sind eigene Routen der
Erweiterung:

| Adresse | Zweck |
|---|---|
| `/linkscollection/go/<ID>` | Zählt den Klick und leitet zum Ziel weiter |
| `/linkscollection/suche` | Liefert die Treffer der Suche als HTML-Ausschnitt |

## Aufbau der Ausgabe

Jede Ansicht beginnt mit demselben Menü: *Startseite*, *Toplinks*,
*Neue Links*, *Suchen*, *Neuen Link melden*. Darunter folgt der Inhalt, ganz
unten steht die Anzahl der Kategorien und Links.

In der Kategorieansicht kommt zwischen Menü und Inhalt eine Breadcrumb-
Navigation dazu, die den Weg von der obersten Ebene bis zur geöffneten
Kategorie zeigt.

## Templates

| Template | Wofür |
|---|---|
| `mod_linkscollection` | Rahmen der Kategorieansicht: Menü, Breadcrumb, Unterkategorien, Links, Fußzeile |
| `mod_linkscollection_linkrow` | Eine Liste von Links; wird in mehrere Ansichten eingebettet |
| `mod_linkscollection_kategorierow` | Eine Liste von Kategorien in den Suchtreffern |
| `mod_linkscollection_toplinks` | Ansicht *Toplinks* |
| `mod_linkscollection_newlinks` | Ansicht *Neue Links* und Formular für neue Links |
| `mod_linkscollection_link` | Einzelansicht eines Links mit dem Meldeformular |
| `mod_linkscollection_search` | Suchmaske |
| `be_linkscollection` | Platzhalter des Moduls im Backend |
| `be_linklist` | Rahmen der Backend-Linkliste |

Zum Anpassen die gewünschte Datei aus
`vendor/schachbulle/contao-linkscollection-bundle/src/Resources/contao/templates/`
in das Verzeichnis `templates/` der Installation kopieren und dort ändern.
Contao bevorzugt anschließend die eigene Fassung.

Die Beschriftungen des Menüs stehen nicht in den Templates, sondern in der
Klasse `Schachbulle\ContaoLinkscollectionBundle\Module\Linksammlung`. Wer sie
ändern möchte, überschreibt am besten das Template und baut das Menü dort
selbst auf — `$this->menu` liefert Titel und Adresse jedes Eintrags.

## Stile

Das Modul bindet `bundles/contaolinkscollection/css/style.css` selbst ein. Die
Datei bringt nur das Nötigste mit: die Einrückung der Listen, das Favicon als
Hintergrundbild jeder Zeile und die Auszeichnung populärer sowie ins Webarchiv
umgeleiteter Links. Alles Weitere übernimmt das Theme der Webseite.

Die wichtigsten Klassen:

| Klasse | Wo |
|---|---|
| `.menu` | Menüleiste über jeder Ansicht |
| `.breadcrumb` | Kategoriepfad |
| `.categories`, `.links` | Die beiden Listen der Kategorieansicht |
| `.favicon-img` | Eine Linkzeile; das Favicon liegt als Hintergrundbild darin |
| `.popular` | Als populär gekennzeichneter Link |
| `.webarchiv` | Link, der ins Webarchiv umgeleitet wird |
| `.counter` | Fußzeile mit den Gesamtzahlen |

## Sprachfähnchen

Hinter jedem Link steht das Fähnchen des Landes, dessen Kürzel im Feld
*Sprache* hinterlegt ist. Die Grafiken stammen aus dem Paket
`components/flag-icon-css`, das Composer als Abhängigkeit mitinstalliert.

Weil dieses Paket kein Contao-Bundle ist, landen seine Dateien unter `vendor/`
und sind von außen zunächst nicht erreichbar. Die Erweiterung legt deshalb beim
ersten Aufruf eine Verknüpfung im Web-Verzeichnis an:

```
public/bundles/flag-icon-css  ->  vendor/components/flag-icon-css
```

(unter Contao 4 heißt das Verzeichnis `web/` statt `public/`).

Schlägt das fehl — etwa unter Windows oder wenn der Webserver keine
symbolischen Verknüpfungen anlegen darf —, bleibt die Linksammlung
funktionsfähig, nur die Fähnchen fehlen. Die Verknüpfung lässt sich dann von
Hand anlegen:

```bash
ln -s ../../vendor/components/flag-icon-css public/bundles/flag-icon-css
```

## Suche

Die Suchmaske schickt den Suchbegriff per JavaScript an die Route
`/linkscollection/suche` und setzt die Treffer unter das Formular. Gesucht wird
in den Kategorietiteln sowie in Titel, Adresse und Beschreibung der Links;
der Begriff muss mindestens drei Zeichen lang sein.

Ist JavaScript abgeschaltet, wird dasselbe Formular ganz normal abgeschickt;
die Trefferliste erscheint dann als schlichte Seite ohne das Layout der
Webseite. jQuery wird nicht mehr benötigt.

## Die beiden Meldeformulare

Über *Neuen Link melden* können Besucher einen Link vorschlagen, über die
Einzelansicht eines Links ein Problem damit melden. Beide Formulare verlangen
Name und E-Mail-Adresse und stellen eine Sicherheitsfrage gegen automatisierte
Einträge.

* Ein **vorgeschlagener Link** wird unveröffentlicht angelegt und erscheint im
  Backend im Prüfbericht unter „Unveröffentlichte Links“.
* Eine **Problemmeldung** wird beim betroffenen Link im Feld *Probleme und
  Änderungswünsche* vermerkt, die jüngste Meldung steht oben. Der Link selbst
  bleibt unverändert — über die Korrektur entscheidet die Redaktion.

In beiden Fällen geht zusätzlich eine E-Mail an die Administratoradresse der
Webseite. Ist im Seitenstartpunkt beziehungsweise in den Systemeinstellungen
keine hinterlegt, unterbleibt der Versand; gespeichert wird die Meldung
trotzdem.
