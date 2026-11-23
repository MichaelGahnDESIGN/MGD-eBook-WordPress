// MGD eBook - Vorschau-Fenster
(function () {
  'use strict';
  var LOCK = 'mgd-ebook-lock';

  // Fenster oeffnen - im Capture, sonst schnappen sich Theme-Skripte (Divi Smooth Scroll) den Klick vorher
  document.addEventListener('click', function (event) {
    var opener = event.target.closest('[data-mgd-ebook-open]');
    if (opener) {
      var dialog = document.getElementById(opener.getAttribute('data-mgd-ebook-open'));
      // Alter Browser ohne <dialog>: Link laedt einfach die Datei
      if (!dialog || typeof dialog.showModal !== 'function') { return; }
      event.preventDefault();
      event.stopPropagation();
      dialog.showModal();
      document.body.classList.add(LOCK);
      return;
    }
    // Leseprobe auf/zu, danach hinscrollen
    var toggle = event.target.closest('[data-mgd-ebook-sample]');
    if (toggle) {
      var sample = document.getElementById(toggle.getAttribute('aria-controls'));
      if (!sample) { return; }
      var open = sample.hasAttribute('hidden');
      if (open) { sample.removeAttribute('hidden'); } else { sample.setAttribute('hidden', ''); }
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        sample.scrollIntoView({ behavior: calm ? 'auto' : 'smooth', block: 'start' });
      }
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
  }, true);

  // Scroll-Sperre wieder weg (auch bei Escape)
  document.addEventListener('close', function (event) {
    if (event.target.matches && event.target.matches('dialog.mgd-ebook__dialog')) {
      document.body.classList.remove(LOCK);
    }
  }, true);
})();
