// MGD eBook Backend - Kopieren, Dropzone, Loeschen bestaetigen
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
      button.textContent = ok ? 'Kopiert ✓' : 'Von Hand kopieren';
      setTimeout(function () { button.textContent = original; }, 2000);
    });
  });

  // Dropzone: gewaehlte Dateien als Liste zeigen
  var drop = document.querySelector('[data-mgd-drop]');
  if (drop) {
    var input = drop.querySelector('input[type=file]');
    var list = document.querySelector('[data-mgd-chosen]');
    var show = function () {
      list.innerHTML = '';
      Array.prototype.forEach.call(input.files, function (file) {
        var item = document.createElement('li');
        item.textContent = file.name;
        list.appendChild(item);
      });
      list.hidden = !input.files.length;
    };
    input.addEventListener('change', show);
    ['dragenter', 'dragover'].forEach(function (name) {
      drop.addEventListener(name, function () { drop.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (name) {
      drop.addEventListener(name, function () { drop.classList.remove('is-over'); });
    });
    // Beim Upload den Knopf sperren (kein Doppelklick)
    var form = drop.closest('form');
    form.addEventListener('submit', function () {
      var submit = form.querySelector('[data-mgd-submit]');
      if (submit) { submit.value = 'Wird hochgeladen …'; submit.disabled = true; }
    });
  }

  // Loeschen nur nach Rueckfarge
  document.addEventListener('submit', function (event) {
    var message = event.target.getAttribute && event.target.getAttribute('data-mgd-confirm');
    if (message && !window.confirm(message)) { event.preventDefault(); }
  });
})();
