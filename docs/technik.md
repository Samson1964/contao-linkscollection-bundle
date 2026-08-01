# Technische Hintergründe

## Aufbau der Erweiterung

```text
src/
├── Controller/
│   ├── WeiterleitungController.php   Route /linkscollection/go/<ID>
│   └── SucheController.php           Route /linkscollection/suche
├── Klassen/
│   ├── CreateStatistics.php          Zähler der Kategorien neu berechnen
│   ├── FaviconDownloader.php         Favicon einer fremden Seite finden und laden
│   ├── Filter.php                    Erweiterter Filter der Backend-Linkliste
│   ├── Formular.php                  Formularbauer für die beiden Frontend-Formulare
│   ├── Linkscollection.php           Gemeinsame Helfer und die Backend-Ansichten
│   └── Protokoll.php                 Einträge im Contao-Systemlog
├── Module/
│   └── Linksammlung.php              Frontend-Modul mit allen Ansichten
└── Resources/
    ├── config/                       services.yml, routes.yml
    ├── contao/                       DCA, Sprachdateien, Templates
    └── public/                       CSS, JavaScript, Bilder, Favicon-Ablage
```

## Routen

Beide Routen sind in `src/Resources/config/routes.yml` beschrieben und werden
über das Contao-Manager-Plugin der Erweiterung eingebunden.

| Name | Adresse | Aufgabe |
|---|---|---|
| `linkscollection_go` | `/linkscollection/go/{id}` | Erhöht den Zugriffszähler und leitet zum Ziel weiter (HTTP 303). Existiert der Link nicht oder ist er unveröffentlicht, kommt ein 404 |
| `linkscollection_suche` | `/linkscollection/suche` | Liefert die Suchtreffer als HTML-Ausschnitt |

Der Zähler wird nur erhöht, wenn die aufrufende IP-Adresse eine andere ist als
beim letzten Klick. Das ist keine exakte Zählung, hält aber mehrfaches Klicken
derselben Person aus der Statistik heraus.

## Die Linkprüfung

Geprüft wird an zwei Stellen:

1. **Beim Speichern eines Links im Backend.** Der `onsubmit_callback` ruft die
   Adresse auf und schreibt Statuscode, Statustext, Sprache, CMS und Favicon
   fort.
2. **Beim Anzeigen einer Linkzeile im Backend**, wenn die letzte Prüfung länger
   her ist als in *Anzahl Tage Prüfung* eingestellt.

Damit der Aufbau einer Liste nicht in hunderte Zugriffe ins Internet ausartet,
begrenzt `linkscollection_maxlinkcheck` (Vorgabe 5) die Anzahl der Prüfungen je
Seitenaufruf. In der Gesamtliste aller Links ist die Prüfung ganz abgeschaltet.

Der Abruf läuft über cURL, folgt Weiterleitungen, prüft das TLS-Zertifikat
nicht (viele kleinere Seiten haben keins oder ein selbst ausgestelltes) und
bricht nach 30 Sekunden ab.

Aus dem geladenen Quelltext werden zwei Angaben abgeleitet:

* die **Sprache** aus `<meta http-equiv="Content-Language">`, dem
  `lang`-Attribut des `<html>`-Elements oder `<meta name="language">`,
* das **CMS** aus dem `generator`-Meta-Feld und typischen Spuren im Quelltext
  (etwa `wp-content` für WordPress).

Beides wertet reguläre Ausdrücke aus, keinen HTML-Parser. Fremde Seiten sind
oft fehlerhaft ausgezeichnet; ein Parser war dafür in der Vergangenheit sowohl
langsamer als auch zerbrechlicher.

## Favicons

Gefundene Favicons landen als `<ID>.<Endung>` unter
`src/Resources/public/favicons/` innerhalb der Erweiterung und werden im
Frontend als `bundles/contaolinkscollection/favicons/<ID>.<Endung>`
ausgeliefert. Findet sich für einen Link keine Datei, erscheint das
mitgelieferte Standardsymbol.

Zwei Dinge sind dabei zu wissen:

* Die Ablage liegt innerhalb des Composer-Pakets. **Ein Update der Erweiterung
  löscht die gesammelten Favicons.** Sie werden nach und nach neu geladen,
  sobald die Links im Backend wieder geprüft werden.
* Contao verlinkt die öffentlichen Dateien einer Erweiterung normalerweise mit
  einer symbolischen Verknüpfung nach `public/bundles/`. Wo das nicht möglich
  ist und Contao die Dateien stattdessen kopiert (typischerweise unter
  Windows), erreichen später hinzugekommene Favicons das Web-Verzeichnis nicht.
  In dem Fall hilft ein erneutes `vendor/bin/contao-console contao:symlinks`
  beziehungsweise das Anlegen der Verknüpfung von Hand.

## Datenbanktabellen

### tl_linkscollection (Kategorien)

Baumstruktur über `pid`. Neben Titel, Alias und den Veröffentlichungsangaben
stehen dort vier Zähler, die die Statistik füllt: `links_self` (Links
unmittelbar in der Kategorie), `links_all` (einschließlich Unterkategorien),
`links_new` (davon neu) und `links_checkquote` (Anteil erreichbarer Links in
Prozent).

### tl_linkscollection_links (Links)

Hängt über `pid` an einer Kategorie. Erwähnenswert:

| Spalte | Inhalt |
|---|---|
| `initdate` | Zeitpunkt der Aufnahme; entscheidet, ob ein Link als neu gilt |
| `webarchiv` | Schaltet die Weiterleitung ins Webarchiv ein |
| `statecode`, `statetext`, `statedate` | Ergebnis der letzten Prüfung |
| `cms`, `cmsdate` | Erkanntes Content-Management-System |
| `hits`, `ip`, `ipdate` | Zugriffszähler und letzter Besucher |
| `history` | Frühere Adressen (Mehrspalten-Assistent) |
| `problem`, `problemdate`, `problemcount` | Meldungen von Besuchern |
| `warnings` | Meldungen in strukturierter Form (Mehrspalten-Assistent) |

## Umstieg von Version 1.x

Version 2.0.0 setzt **PHP 8.1** und **Contao 4.13** voraus. Die Datenbank
bleibt unverändert; ein `contao:migrate` nach dem Update genügt.

Drei Dinge ändern sich nach außen:

1. **Die Dateien `go.php`, `search.php` und `rebuild.php` sind entfallen.**
   Sie lagen im Web-Verzeichnis und haben sich über `system/initialize.php` in
   Contao eingeklinkt — einen Weg, den Contao 5 nicht mehr kennt. Die ersten
   beiden sind jetzt Routen (siehe oben), `rebuild.php` war eine ältere Kopie
   der Statistikberechnung und ist ersatzlos entfallen.

   Wer eigene Templates benutzt, muss dort `bundles/contaolinkscollection/go.php?id=…`
   durch die vom Modul gelieferte Adresse ersetzen — in den mitgelieferten
   Templates steht sie in `$l['url']`.

2. **Die Abhängigkeit `codefog/contao-haste` ist entfallen.** Die beiden
   Frontend-Formulare bauen jetzt auf den Widget-Klassen des Contao-Kerns auf,
   die Ein-/Ausschalter im Backend auf der seit Contao 4.13 eingebauten
   `toggle`-Operation. Wird Haste von keiner anderen Erweiterung mehr
   gebraucht, kann es entfernt werden.

3. **Die Suche kommt ohne jQuery aus.** Aus `js/ajax.js` wurde `js/suche.js`.
   Wer das Such-Template überschrieben hat, muss die Einbindung anpassen.

Die globalen Konstanten `NEWICON`, `DEFAULT_FAVICON` und `DATE_NEWLINK` gibt es
nicht mehr. Die erste ist zur Klassenkonstante
`Schachbulle\ContaoLinkscollectionBundle\Klassen\Linkscollection::NEUICON`
geworden, die beiden anderen wurden nirgends benutzt.

## Bekannte Baustellen

* `PageModel::getFrontendUrl()` ist seit Contao 5.3 als überholt markiert und
  fällt in Contao 6 weg. Ein Ersatz (`contao.routing.content_url_generator`)
  steht erst ab Contao 5 zur Verfügung — solange Contao 4.13 unterstützt wird,
  bleibt es beim bisherigen Aufruf.
* Der erweiterte Filter der Backend-Linkliste ist vorhanden, aber nicht
  eingeblendet: Der `button_callback` in `tl_linkscollection_links` liefert
  bewusst nichts zurück. Die Auswertung in `Klassen\Filter` funktioniert und
  lässt sich über die Adresszeile ansprechen.
* Die Beschriftungen des Frontend-Menüs stehen fest im Modul und nicht in den
  Sprachdateien.
