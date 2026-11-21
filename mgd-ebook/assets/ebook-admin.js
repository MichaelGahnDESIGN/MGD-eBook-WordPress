// MGD eBook Backend - Kopieren + Loeschen bestaetigen
(function () {
  'use strict';

  // Zwischenablage: erst modern, sonst altes execCommand
  function copy(field) {
    field.focus();
    field.select();
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(field.value).then(function () { return true; }, function () { return legacy(field); });
    }
    return Promise.resolve(legacy(field));
  }
  function legacy(field) {
    try { return document.execCommand('copy'); } catch (e) { return false; }
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-mgd-copy]');
    if (!button) { return; }
    var field = document.getElementById(button.getAttribute('data-mgd-copy'));
    if (!field) { return; }
    var original = button.getAttribute('data-label') || button.textContent;
    button.setAttribute('data-label', original);
    copy(field).then(function (ok) {
      button.textContent = ok ? 'Kopiert ✓' : 'Bitte von Hand kopieren';
      setTimeout(function () { button.textContent = original; }, 2000);
    });
  });

  // Loeschen nur nach Rueckfarge
  document.addEventListener('submit', function (event) {
    var message = event.target.getAttribute && event.target.getAttribute('data-mgd-confirm');
    if (message && !window.confirm(message)) { event.preventDefault(); }
  });
})();
