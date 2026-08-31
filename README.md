# Linksammlung für Contao

Eine Linksammlung mit beliebig vielen Kategorien und Unterkategorien — im
Backend gepflegt, im Frontend über ein einziges Modul dargestellt. Die
Erweiterung prüft die gesammelten Links regelmäßig selbst, holt sich die
Favicons der verlinkten Seiten und lässt Besucher neue Links vorschlagen oder
kaputte melden.

Die jeweils aktuellste Version ist hier im Einsatz:
[Deutscher Schachbund](https://www.schachbund.de/linksammlung.html)

## Funktionen

- Kategorien und Unterkategorien in beliebig vielen Ebenen
- Automatische Prüfung, ob die hinterlegten Adressen noch erreichbar sind, plus ein Backend-Knopf für die Sammelprüfung auf Zuruf
- Automatisches Laden der Favicons sowie Erkennung von Sprache und CMS der verlinkten Seiten
- Toplinks (meistgeklickt) und neue Links als eigene Ansichten
- Volltextsuche über Kategorien, Titel, Adressen und Beschreibungen
- Meldeformulare für neue und für fehlerhafte Links
- Halbautomatische Weiterleitung ins Webarchiv, wenn eine Seite endgültig offline geht
- Zugriffszählung je Link

## Voraussetzungen

| Software | Version |
|---|---|
| PHP | 8.1 oder neuer, mit der Erweiterung `curl` |
| Contao | 4.13 LTS oder Contao 5 |

## Installation

Im Contao Manager das Paket `schachbulle/contao-linkscollection-bundle` suchen
und installieren, oder auf der Kommandozeile:

```bash
composer require schachbulle/contao-linkscollection-bundle
```

Danach die Datenbank aktualisieren — im Contao Manager unter „System-Wartung“
oder auf der Kommandozeile:

```bash
vendor/bin/contao-console contao:migrate
```

Dabei entstehen die Tabellen `tl_linkscollection` (Kategorien) und
`tl_linkscollection_links` (Links).

> **Umstieg von Version 1.x:** `go.php` und `search.php` im Web-Verzeichnis
> sind entfallen und durch die Routen `/linkscollection/go/{id}` sowie
> `/linkscollection/suche` ersetzt. Eigene Templates, die noch
> `bundles/contaolinkscollection/go.php?id=…` verlinken, müssen angepasst
> werden — Details in [docs/technik.md](docs/technik.md#umstieg-von-version-1x).

## Schnelleinstieg

1. **Kategorien anlegen:** Im Backend unter *Inhalte → Linksammlung* mindestens
   eine Kategorie erstellen und veröffentlichen.
2. **Links eintragen:** In der Kategorieliste auf *Links der Kategorie anzeigen*
   klicken und Links hinzufügen. Titel und Adresse genügen — alles Weitere
   ermittelt die Erweiterung beim Speichern selbst.
3. **Seite vorbereiten:** Eine Seite anlegen, die die Linksammlung aufnimmt.
   Sie muss zusätzliche Adressbestandteile zulassen; Näheres in
   [docs/frontend.md](docs/frontend.md).
4. **Modul einbinden:** Unter *Layout → Module* ein Modul vom Typ
   *Linksammlung* anlegen und es über ein Inhaltselement „Modul“ oder über den
   Seitenaufbau in die Seite einfügen.
5. **Einstellungen prüfen:** Unter *System → Einstellungen* im Abschnitt
   *Linksammlung* festlegen, wie lange ein Link als neu gilt und in welchem
   Abstand die Adressen erneut geprüft werden.

## Dokumentation

- [docs/backend.md](docs/backend.md) — Kategorien und Links pflegen, Linkliste,
  Prüfbericht, Statistik, Einstellungen
- [docs/frontend.md](docs/frontend.md) — Modul einbinden, Adressen der
  Ansichten, Templates und Stile, Sprachfähnchen, Suche
- [docs/technik.md](docs/technik.md) — Routen, Linkprüfung, Favicons,
  Datenbanktabellen, Umstieg von Version 1.x

## Verwendete Grafiken

`src/Resources/public/images/icon.png`
License: CC Attribution 4.0
Commercial usage: Allowed (Backlink to http://www.fatcow.com/free-icons required)

`src/Resources/public/images/category.png`
Artist: celldrifter
Iconset: Muku Style Icons (72 icons)
License: CC Attribution-Share Alike 4.0
Commercial usage: Not allowed

`src/Resources/public/images/favicon.png`
Artist: Umut Pulat
Iconset: Tulliana 2 Icons (415 icons)
License: GNU Lesser General Public License
Commercial usage: Allowed

`src/Resources/public/images/problem.png`, `src/Resources/public/images/problem_16.png`
Artist: Aha-Soft (Available for custom work)
Iconset: 3D Interface Icons (54 icons)
License: Linkware (Backlink to http://www.aha-soft.com required)
Commercial usage: Allowed

Die Sprachfähnchen stammen aus dem Paket
[flag-icon-css](https://github.com/lipis/flag-icons), das Composer als
Abhängigkeit mitinstalliert.

## Lizenz

LGPL-3.0-or-later — siehe [LICENSE](LICENSE).

**Frank Hoppe**
