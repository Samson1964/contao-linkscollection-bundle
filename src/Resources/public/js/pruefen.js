/**
 * Sammelprüfung der Linksammlung (Backend, &key=pruefen)
 *
 * Ruft für jeden Link aus window.linkscollectionPruefung.links nacheinander
 * die Route linkscollection_pruefen auf. Bewusst nacheinander statt parallel:
 * eine hängende Zielseite soll die Prüfung nur verzögern, nicht mehrere
 * Anfragen gleichzeitig gegen denselben Server schicken. Läuft ein einzelner
 * Abruf auf einen Fehler (Netzwerk, Zeitüberschreitung, HTTP-Fehler vom
 * eigenen Server), macht die Prüfung trotzdem mit dem nächsten Link weiter —
 * das ist hier der Kern der Anforderung, nicht nur eine Randbehandlung.
 */
document.addEventListener('DOMContentLoaded', function () {
	var daten = window.linkscollectionPruefung;

	if (!daten || !daten.links || !daten.links.length) {
		return;
	}

	var status = document.getElementById('linkpruefung_status');
	var balken = document.getElementById('linkpruefung_balken');
	var log = document.getElementById('linkpruefung_log');
	var gesamt = daten.links.length;
	var erledigt = 0;

	function zeileFuer(link) {
		var zeile = document.createElement('li');
		zeile.id = 'linkpruefung_zeile_' + link.id;
		zeile.className = 'tl_content';
		zeile.textContent = link.title + ' … wird geprüft';
		if (log) {
			log.appendChild(zeile);
		}
		return zeile;
	}

	function zeigeErgebnis(zeile, link, ergebnis) {
		if (!zeile) {
			return;
		}

		if (!ergebnis || !ergebnis.ok) {
			zeile.textContent = link.title + ' — nicht geprüft (Link nicht mehr vorhanden?)';
			return;
		}

		var code = ergebnis.statecode || '000';
		var zusatz = ergebnis.statetext ? ' (' + ergebnis.statetext + ')' : '';
		zeile.textContent = link.title + ' — ' + code + zusatz;
	}

	function pruefeNaechsten(index) {
		if (index >= gesamt) {
			if (status) {
				status.textContent = gesamt + ' / ' + gesamt + ' geprüft — fertig. Seite neu laden, um die Ergebnisse in der Liste zu sehen.';
			}
			return;
		}

		var link = daten.links[index];
		var zeile = zeileFuer(link);

		fetch(daten.basisUrl + '/' + link.id, {credentials: 'same-origin'})
			.then(function (antwort) {
				if (!antwort.ok) {
					throw new Error('HTTP ' + antwort.status);
				}
				return antwort.json();
			})
			.then(function (ergebnis) {
				zeigeErgebnis(zeile, link, ergebnis);
			})
			.catch(function () {
				zeigeErgebnis(zeile, link, null);
			})
			.then(function () {
				erledigt++;
				if (balken) {
					balken.value = erledigt;
				}
				if (status) {
					status.textContent = erledigt + ' / ' + gesamt + ' geprüft …';
				}
				pruefeNaechsten(index + 1);
			});
	}

	pruefeNaechsten(0);
});
