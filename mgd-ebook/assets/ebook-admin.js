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

// Cover-Editor: Bild waehlen, Ausschnitt verschieben und zoomen, speichern
(function () {
  'use strict';
  var root = document.querySelector('[data-mgd-editor]');
  if (!root || !window.mgdEbook) { return; }

  var stage = root.querySelector('[data-mgd-stage]');
  var img = root.querySelector('[data-mgd-editor-img]');
  var empty = root.querySelector('[data-mgd-editor-empty]');
  var zoom = root.querySelector('[data-mgd-zoom]');
  var saveBtn = root.querySelector('[data-mgd-save]');
  var status = root.querySelector('[data-mgd-status]');
  var state = null;  // aktuelles eBook + Bild
  var opener = null;
  var frame = null;

  function say(text, bad) {
    status.textContent = text || '';
    status.className = 'mgd-ebook-editor__status' + (bad ? ' is-error' : '');
  }

  // Bildgroesse im Rahmen berechnen (Bild muss den Rahmen immer ganz fuellen)
  function metrics() {
    var w = stage.clientWidth, h = stage.clientHeight;
    var base = Math.max(w / state.nw, h / state.nh);
    var scale = base * state.z;
    return { w: w, h: h, scale: scale, iw: state.nw * scale, ih: state.nh * scale };
  }
  function clamp() {
    var m = metrics();
    state.ox = Math.min(0, Math.max(m.w - m.iw, state.ox));
    state.oy = Math.min(0, Math.max(m.h - m.ih, state.oy));
  }
  function draw() {
    if (!state || !state.nw) { return; }
    clamp();
    var m = metrics();
    img.style.width = m.iw + 'px';
    img.style.height = m.ih + 'px';
    img.style.left = state.ox + 'px';
    img.style.top = state.oy + 'px';
  }
  function setZoom(value) {
    if (!state || !state.nw) { return; }
    var before = metrics();
    // Mitte des Rahmens bleibt an derselben Stelle im Bild
    var cx = (before.w / 2 - state.ox) / before.scale;
    var cy = (before.h / 2 - state.oy) / before.scale;
    state.z = Math.min(4, Math.max(1, value));
    zoom.value = state.z;
    var after = metrics();
    state.ox = before.w / 2 - cx * after.scale;
    state.oy = before.h / 2 - cy * after.scale;
    draw();
  }

  // Bild laden und mittig einpassen
  function load(url, source) {
    say('Bild wird geladen …');
    var probe = new Image();
    probe.onload = function () {
      state.source = source;
      state.nw = probe.naturalWidth;
      state.nh = probe.naturalHeight;
      state.z = 1;
      img.src = url;
      img.hidden = false;
      empty.hidden = true;
      var m = metrics();
      state.ox = (m.w - m.iw) / 2;
      state.oy = (m.h - m.ih) / 2;
      zoom.disabled = false;
      zoom.value = 1;
      saveBtn.disabled = false;
      say('');
      draw();
    };
    probe.onerror = function () { say('Das Bild konnte nicht geladen werden.', true); };
    probe.src = url;
  }

  function open(button) {
    opener = button;
    state = { book: button.getAttribute('data-book'), nw: 0, nh: 0, z: 1, ox: 0, oy: 0, source: '' };
    root.querySelector('[data-mgd-editor-book]').textContent = button.getAttribute('data-title');
    img.hidden = true;
    img.removeAttribute('src');
    empty.hidden = false;
    zoom.disabled = true;
    zoom.value = 1;
    saveBtn.disabled = true;
    say('');
    root.hidden = false;
    document.body.classList.add('mgd-ebook-editing');
    var cover = button.getAttribute('data-cover');
    // vorhandenes Cover direkt zum Nachbearbeiten anbieten
    if (cover) { load(cover, 'current'); }
    stage.focus();
  }
  function close() {
    root.hidden = true;
    document.body.classList.remove('mgd-ebook-editing');
    if (opener) { opener.focus(); }
  }

  function post(action, extra) {
    var data = new FormData();
    data.append('action', action);
    data.append('nonce', window.mgdEbook.nonce);
    data.append('book_id', state.book);
    Object.keys(extra || {}).forEach(function (key) { data.append(key, extra[key]); });
    return fetch(window.mgdEbook.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
      .then(function (response) { return response.json(); })
      .then(function (json) {
        if (!json || !json.success) { throw new Error(json && json.data && json.data.message ? json.data.message : 'Das hat nicht geklappt.'); }
        return json.data;
      });
  }

  // Neues Cover in der Karte austauschen
  function refreshCard(url) {
    var card = document.querySelector('[data-mgd-card="' + state.book + '"]');
    if (!card) { return; }
    var current = card.querySelector('[data-mgd-cover-img]');
    var fresh = document.createElement('img');
    fresh.className = 'mgd-ebook-admin__cover';
    fresh.setAttribute('data-mgd-cover-img', '');
    fresh.alt = '';
    fresh.width = 72;
    if (url) {
      fresh.src = url;
      current.replaceWith(fresh);
    } else {
      // kein Cover mehr: Platzhalter-Buchstabe
      var none = document.createElement('span');
      none.className = 'mgd-ebook-admin__cover mgd-ebook-admin__cover--none';
      none.setAttribute('data-mgd-cover-img', '');
      none.setAttribute('aria-hidden', 'true');
      none.textContent = (card.querySelector('h3').textContent || '?').charAt(0);
      current.replaceWith(none);
    }
    card.querySelector('[data-mgd-cover-edit]').setAttribute('data-cover', url || '');
  }

  // Oeffnen
  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-mgd-cover-edit]');
    if (button) { open(button); }
  });
  root.querySelector('[data-mgd-cancel]').addEventListener('click', close);

  // Mediathek
  root.querySelector('[data-mgd-pick]').addEventListener('click', function () {
    if (!window.wp || !wp.media) { say('Die Mediathek ist nicht verfügbar.', true); return; }
    if (!frame) {
      frame = wp.media({ title: 'Cover-Bild wählen', button: { text: 'Dieses Bild verwenden' }, library: { type: 'image' }, multiple: false });
      frame.on('select', function () {
        var picked = frame.state().get('selection').first().toJSON();
        load(picked.url, String(picked.id));
      });
    }
    frame.open();
  });

  // Speichern
  saveBtn.addEventListener('click', function () {
    var m = metrics();
    saveBtn.disabled = true;
    say('Wird gespeichert …');
    post('mgd_ebook_cover', {
      source: state.source,
      x: -state.ox / m.iw,
      y: -state.oy / m.ih,
      w: m.w / m.iw,
      h: m.h / m.ih
    }).then(function (data) {
      refreshCard(data.url);
      close();
    }).catch(function (error) {
      saveBtn.disabled = false;
      say(error.message, true);
    });
  });

  // Original aus der EPUB
  root.querySelector('[data-mgd-restore]').addEventListener('click', function () {
    if (!window.confirm('Das aktuelle Cover wird durch das Original aus der EPUB-Datei ersetzt. Weiter?')) { return; }
    say('Wird wiederhergestellt …');
    post('mgd_ebook_cover_reset').then(function (data) {
      refreshCard(data.url);
      close();
    }).catch(function (error) { say(error.message, true); });
  });

  // Ziehen mit Maus oder Finger
  var drag = null;
  stage.addEventListener('pointerdown', function (event) {
    if (!state || !state.nw) { return; }
    drag = { x: event.clientX, y: event.clientY, ox: state.ox, oy: state.oy };
    stage.setPointerCapture(event.pointerId);
    stage.classList.add('is-drag');
  });
  stage.addEventListener('pointermove', function (event) {
    if (!drag) { return; }
    state.ox = drag.ox + event.clientX - drag.x;
    state.oy = drag.oy + event.clientY - drag.y;
    draw();
  });
  ['pointerup', 'pointercancel'].forEach(function (name) {
    stage.addEventListener(name, function () { drag = null; stage.classList.remove('is-drag'); });
  });
  // Zoom: Regler und Mausrad
  zoom.addEventListener('input', function () { setZoom(parseFloat(zoom.value)); });
  stage.addEventListener('wheel', function (event) {
    if (!state || !state.nw) { return; }
    event.preventDefault();
    setZoom(state.z - Math.sign(event.deltaY) * 0.1);
  }, { passive: false });
  // Tastatur: Pfeile verschieben, Plus/Minus zoomen
  stage.addEventListener('keydown', function (event) {
    if (!state || !state.nw) { return; }
    var step = event.shiftKey ? 30 : 10;
    var handled = true;
    if (event.key === 'ArrowLeft') { state.ox += step; }
    else if (event.key === 'ArrowRight') { state.ox -= step; }
    else if (event.key === 'ArrowUp') { state.oy += step; }
    else if (event.key === 'ArrowDown') { state.oy -= step; }
    else if (event.key === '+' || event.key === '=') { setZoom(state.z + 0.1); }
    else if (event.key === '-') { setZoom(state.z - 0.1); }
    else { handled = false; }
    if (handled) { event.preventDefault(); draw(); }
  });

  // Escape schliesst den Editor (aber nicht, wenn gerade die Mediathek offen ist)
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape' || root.hidden) { return; }
    if (document.querySelector('.media-modal:not([style*="display: none"])')) { return; }
    close();
  });
  // Klick auf den abgedunkelten Rand schliesst
  root.addEventListener('mousedown', function (event) { if (event.target === root) { close(); } });
  window.addEventListener('resize', draw);
})();
