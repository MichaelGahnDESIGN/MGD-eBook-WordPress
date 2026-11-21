// MGD eBook - Vorschau-Fenster
(function () {
  'use strict';
  var LOCK = 'mgd-ebook-lock';

  // Fenster oeffnen
  document.addEventListener('click', function (event) {
    var opener = event.target.closest('[data-mgd-ebook-open]');
    if (opener) {
      var dialog = document.getElementById(opener.getAttribute('data-mgd-ebook-open'));
      // Alter Browser ohne <dialog>: Link laedt einfach die Datei
      if (!dialog || typeof dialog.showModal !== 'function') { return; }
      event.preventDefault();
      dialog.showModal();
      document.body.classList.add(LOCK);
      return;
    }
    // Schliessen Button
    var closer = event.target.closest('[data-mgd-ebook-close]');
    if (closer) {
      closer.closest('dialog').close();
      return;
    }
    // Klick auf den dunklen Hintergrund
    if (event.target.matches && event.target.matches('dialog.mgd-ebook__dialog')) {
      event.target.close();
    }
  });

  // Scroll-Sperre wieder weg (auch bei Escape)
  document.addEventListener('close', function (event) {
    if (event.target.matches && event.target.matches('dialog.mgd-ebook__dialog')) {
      document.body.classList.remove(LOCK);
    }
  }, true);
})();
