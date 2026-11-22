// MGD eBook - Divi 5 Module (Builder), ohne Build: nutzt nur die Divi-Globals window.divi + window.vendor
(function () {
  'use strict';
  var data = window.MgdEbookDiviData || {};
  var vendor = window.vendor || {};
  var divi = window.divi || {};
  var React = vendor.React;
  var hooks = vendor.wp && vendor.wp.hooks;
  if (!React || !hooks || !divi.module || !divi.moduleLibrary || !Array.isArray(data.modules)) { return; }
  var h = React.createElement;
  var ModuleContainer = divi.module.ModuleContainer;
  var StyleContainer = divi.module.StyleContainer;

  // Modul-Werte aus den Attributen
  function values(attrs) {
    var v = attrs && attrs.ebook && attrs.ebook.innerContent && attrs.ebook.innerContent.desktop && attrs.ebook.innerContent.desktop.value;
    return v || {};
  }

  // Styles wie bei nativen Modulen (Abstände, Rahmen, Hintergrund...)
  function ModuleStyles(props) {
    return h(StyleContainer, { mode: props.mode, state: props.state, noStyleTag: props.noStyleTag },
      props.elements.style({
        attrName: 'module',
        styleProps: {
          defaultPrintedStyleAttrs: props.defaultPrintedStyleAttrs && props.defaultPrintedStyleAttrs.module && props.defaultPrintedStyleAttrs.module.decoration,
          disabledOn: { disabledModuleVisibility: props.settings && props.settings.disabledModuleVisibility }
        }
      })
    );
  }

  // Vorschau holt das echte HTML vom Server (gleiche Ausgabe wie auf der Seite)
  function makeEdit(type) {
    return function Edit(props) {
      var v = values(props.attrs);
      var state = React.useState('');
      var html = state[0];
      var setHtml = state[1];
      var key = [type, v.id, v.ids, v.reihe, v.width, v.caption, v.button].join('|');
      React.useEffect(function () {
        var ctrl = typeof AbortController === 'function' ? new AbortController() : null;
        var q = new URLSearchParams({ type: type, id: v.id || '', ids: v.ids || '', reihe: v.reihe || '', width: v.width || '', caption: v.caption || 'yes', button: v.button || '' });
        fetch(data.preview + (data.preview.indexOf('?') > -1 ? '&' : '?') + q.toString(), {
          credentials: 'same-origin',
          headers: { 'X-WP-Nonce': data.nonce },
          signal: ctrl ? ctrl.signal : undefined
        })
          .then(function (r) { return r.ok ? r.json() : { html: '' }; })
          .then(function (j) { setHtml(j && typeof j.html === 'string' ? j.html : ''); })
          .catch(function () {});
        return function () { if (ctrl) { ctrl.abort(); } };
      }, [key]);
      return h(ModuleContainer, {
        attrs: props.attrs,
        elements: props.elements,
        id: props.id,
        name: props.name,
        stylesComponent: ModuleStyles
      },
        props.elements.styleComponents({ attrName: 'module' }),
        // Klicks im Builder nicht als Download/Fenster ausführen
        h('div', { className: 'mgd-ebook-divi-preview', style: { pointerEvents: 'none' }, dangerouslySetInnerHTML: { __html: html || '<p>…</p>' } })
      );
    };
  }

  hooks.addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'mgdEbook', function () {
    data.modules.forEach(function (metadata) {
      if (!metadata || !metadata.name) { return; }
      var type = metadata.name === 'mgd/ebook-regal' ? 'regal' : 'ebook';
      divi.moduleLibrary.registerModule(metadata, {
        renderers: { edit: makeEdit(type) },
        placeholderContent: metadata.attributes && metadata.attributes.ebook ? { ebook: metadata.attributes.ebook['default'] } : {}
      });
    });
  });
})();
