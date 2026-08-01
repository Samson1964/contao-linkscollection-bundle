/**
 * Suche der Linksammlung
 *
 * Schickt den Suchbegriff an die Route linkscollection_suche und setzt das
 * zurückgelieferte HTML in die Seite ein. Ohne JavaScript sendet dasselbe
 * Formular ganz normal ab und zeigt die Treffer als eigene Seite.
 *
 * Die frühere Fassung (ajax.js) setzte jQuery voraus, das Contao 5 nicht mehr
 * von sich aus einbindet.
 */
document.addEventListener('DOMContentLoaded', function () {
	var form = document.getElementById('myForm');

	if (!form) {
		return;
	}

	var ziel = document.getElementById('searchresult');
	var lader = document.getElementById('ajaxloader');
	var adresse = form.getAttribute('data-linkscollection-suche');
	var seite = form.getAttribute('data-linkscollection-seite');

	form.addEventListener('submit', function (event) {
		var feld = document.getElementById('s');

		if (!feld || feld.value.trim().length < 3) {
			return; // Zu kurz: Das Formular meldet das selbst
		}

		event.preventDefault();

		if (lader) {
			lader.innerHTML = '<img src="bundles/contaolinkscollection/images/laden.gif" alt="">';
		}

		var url = adresse + '?s=' + encodeURIComponent(feld.value) + '&seite=' + encodeURIComponent(seite);

		fetch(url, {credentials: 'same-origin'})
			.then(function (antwort) {
				return antwort.text();
			})
			.then(function (html) {
				if (lader) {
					lader.innerHTML = '';
				}

				if (ziel) {
					ziel.innerHTML = html;
				}

				feld.focus();
				feld.select();
			})
			.catch(function () {
				if (lader) {
					lader.innerHTML = '';
				}

				if (ziel) {
					ziel.innerHTML = '<p>Die Suche ist nicht erreichbar.</p>';
				}
			});
	});
});
