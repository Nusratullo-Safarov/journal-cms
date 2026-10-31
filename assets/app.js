// Беҳбудиҳои интерфейс. Бе JavaScript сомона ва панели идоракунӣ ҳам кор мекунанд.
(function () {
  var root = document.documentElement;
  function store(k, v) { try { if (v === null) localStorage.removeItem(k); else localStorage.setItem(k, v); } catch (e) {} }
  function load(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }

  // Ҳолати торик / равшан
  document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var dark = root.dataset.theme !== 'dark';
      if (dark) root.dataset.theme = 'dark'; else delete root.dataset.theme;
      store('theme', dark ? 'dark' : 'light');
    });
  });
  // Андозаи ҳарф: 100% → 112% → 125% → 100%
  document.querySelectorAll('[data-font-size]').forEach(function (b) {
    b.addEventListener('click', function () {
      var next = ((parseInt(root.dataset.fs || '0', 10) + 1) % 3);
      if (next) root.dataset.fs = String(next); else delete root.dataset.fs;
      store('fs', next ? String(next) : null);
    });
  });
  // Контрасти баланд (барои камбинон)
  document.querySelectorAll('[data-contrast]').forEach(function (b) {
    b.addEventListener('click', function () {
      var on = root.dataset.contrast !== '1';
      if (on) root.dataset.contrast = '1'; else delete root.dataset.contrast;
      store('contrast', on ? '1' : null);
    });
  });

  // Эълон: пас аз пӯшидан то матни нав дигар нишон дода намешавад
  var ann = document.querySelector('[data-announcement]');
  if (ann) {
    if (load('ann') === ann.dataset.announcement) ann.hidden = true;
    var close = ann.querySelector('[data-announcement-close]');
    if (close) close.addEventListener('click', function () { ann.hidden = true; store('ann', ann.dataset.announcement); });
  }

  // Менюи мобилӣ ва зерменю
  var toggle = document.querySelector('[data-nav-toggle]');
  if (toggle) {
    toggle.addEventListener('click', function () {
      var open = document.querySelector('[data-nav]').classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
  document.querySelectorAll('[data-sub-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var li = b.closest('.has-sub');
      var open = li.classList.toggle('open');
      b.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
  document.addEventListener('click', function (ev) {
    document.querySelectorAll('.has-sub.open').forEach(function (li) {
      if (!li.contains(ev.target)) { li.classList.remove('open'); li.querySelector('[data-sub-toggle]').setAttribute('aria-expanded', 'false'); }
    });
  });

  // Нусхабардорӣ: data-copy="#id" (матни элемент) ё data-copy-text="..."
  function copyText(text, btn) {
    var done = function () {
      var old = btn.innerHTML;
      btn.textContent = btn.dataset.copied || '✓';
      setTimeout(function () { btn.innerHTML = old; }, 1500);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done);
    } else {
      var ta = document.createElement('textarea');
      ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); done(); } catch (e) {}
      document.body.removeChild(ta);
    }
  }
  document.querySelectorAll('[data-copy], [data-copy-text]').forEach(function (b) {
    b.addEventListener('click', function () {
      var text = b.dataset.copyText || (document.querySelector(b.dataset.copy) || {}).textContent || '';
      copyText(text.trim(), b);
    });
  });

  // PDF дар сомона: танҳо ҳангоми кушодан бор мешавад
  document.querySelectorAll('[data-pdf-viewer]').forEach(function (d) {
    d.addEventListener('toggle', function () {
      var obj = d.querySelector('object');
      if (d.open && obj && !obj.getAttribute('data')) obj.setAttribute('data', obj.dataset.src);
    });
  });

  // Тасдиқи нест кардан
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!confirm(form.dataset.confirm)) ev.preventDefault();
    });
  });

  // Ирсоли худкор (иваз кардани нақши корбар)
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  // Ҳимоя аз ирсоли дукаратаи форма
  document.querySelectorAll('form[data-submit-once]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button:not([name])');
      if (btn) setTimeout(function () { btn.disabled = true; }, 0);
    });
  });

  // Огоҳӣ дар бораи тағйироти нигоҳ дошта нашуда
  document.querySelectorAll('form[data-dirty-check]').forEach(function (form) {
    var dirty = false;
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (ev) { if (dirty) { ev.preventDefault(); ev.returnValue = ''; } });
  });

  // Ҷадвалчаҳои забонҳо дар админ. Нуқтаи сабз — дар ин забон матн ҳаст.
  document.querySelectorAll('[data-lang-tabs]').forEach(function (box) {
    box.classList.add('js');
    var buttons = box.querySelectorAll('[data-tab]');
    function show(id) {
      buttons.forEach(function (b) {
        var on = b.dataset.tab === id;
        b.setAttribute('aria-selected', on ? 'true' : 'false');
        document.getElementById(b.dataset.tab).hidden = !on;
      });
    }
    function markFilled() {
      buttons.forEach(function (b) {
        var panel = document.getElementById(b.dataset.tab);
        var filled = Array.prototype.some.call(panel.querySelectorAll('input, textarea'), function (i) { return i.value.trim() !== ''; });
        var dot = b.querySelector('.dot');
        if (filled && !dot) { dot = document.createElement('span'); dot.className = 'dot'; b.appendChild(dot); }
        if (!filled && dot) dot.remove();
      });
    }
    buttons.forEach(function (b) { b.addEventListener('click', function () { show(b.dataset.tab); }); });
    show(buttons[0].dataset.tab);
    markFilled();
    box.addEventListener('input', markFilled);
    box.querySelectorAll('[required]').forEach(function (input) {
      input.addEventListener('invalid', function () { show(input.closest('[role=tabpanel]').id); });
    });
  });

  // Муҳаррири визуалӣ барои textarea[data-editor]. Сервер HTML-ро ҳамеша тоза мекунад.
  var tools = [
    ['formatBlock:<h2>', 'H2'], ['formatBlock:<h3>', 'H3'], ['formatBlock:<p>', '¶'], ['|'],
    ['bold', '<b>B</b>'], ['italic', '<i>I</i>'], ['underline', '<u>U</u>'], ['strikeThrough', '<s>S</s>'], ['|'],
    ['insertUnorderedList', '• ≡'], ['insertOrderedList', '1. ≡'], ['formatBlock:<blockquote>', '❝'], ['|'],
    ['createLink', '🔗'], ['unlink', '⛓̸'], ['image', '🖼'], ['removeFormat', '⌫']
  ];
  document.querySelectorAll('textarea[data-editor]').forEach(function (ta) {
    var wrap = document.createElement('div');
    wrap.className = 'editor';
    var bar = document.createElement('div');
    bar.className = 'editor-bar';
    var area = document.createElement('div');
    area.className = 'editor-area prose';
    area.contentEditable = 'true';
    area.innerHTML = ta.value; // матн аз пойгоҳ, ки ҳангоми нигоҳдорӣ аллакай тоза шудааст
    var source = false;
    var fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/jpeg,image/png,image/webp';
    fileInput.hidden = true;

    function sync() { if (!source) ta.value = area.innerHTML; ta.dispatchEvent(new Event('input', { bubbles: true })); }

    tools.forEach(function (t) {
      if (t[0] === '|') { var s = document.createElement('span'); s.className = 'sep'; bar.appendChild(s); return; }
      var b = document.createElement('button');
      b.type = 'button';
      b.innerHTML = t[1];
      b.title = t[0].split(':')[0];
      b.addEventListener('mousedown', function (ev) { ev.preventDefault(); });
      b.addEventListener('click', function () {
        var parts = t[0].split(':');
        if (parts[0] === 'image') {
          if (!ta.dataset.uploadUrl) return;
          fileInput.click();
          return;
        }
        var arg = parts[1] || null;
        if (parts[0] === 'createLink') {
          arg = prompt('URL (https://…)', 'https://');
          if (!arg) return;
        }
        document.execCommand(parts[0], false, arg);
        area.focus();
        sync();
      });
      bar.appendChild(b);
    });

    fileInput.addEventListener('change', function () {
      if (!fileInput.files.length) return;
      var fd = new FormData();
      fd.append('file', fileInput.files[0]);
      fd.append('csrf', ta.dataset.csrf);
      wrap.classList.add('uploading');
      fetch(ta.dataset.uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.url) {
            area.focus();
            var img = document.createElement('img');
            img.src = res.url; img.alt = '';
            var sel = window.getSelection();
            if (sel.rangeCount && area.contains(sel.anchorNode)) { var r = sel.getRangeAt(0); r.collapse(false); r.insertNode(img); }
            else area.appendChild(img);
            sync();
          } else alert(res.error || 'Error');
        })
        .catch(function () { alert('Upload failed'); })
        .finally(function () { wrap.classList.remove('uploading'); fileInput.value = ''; });
    });

    var html = document.createElement('button');
    html.type = 'button';
    html.textContent = '</>';
    html.title = 'HTML';
    html.className = 'editor-src';
    html.addEventListener('click', function () {
      source = !source;
      if (source) { ta.value = area.innerHTML; } else { area.innerHTML = ta.value; }
      ta.hidden = !source;
      area.hidden = source;
      html.classList.toggle('active', source);
    });
    bar.appendChild(html);

    ta.parentNode.insertBefore(wrap, ta);
    wrap.appendChild(bar);
    wrap.appendChild(area);
    wrap.appendChild(ta);
    wrap.appendChild(fileInput);
    ta.hidden = true;
    ta.removeAttribute('required');
    area.addEventListener('input', sync);
    ta.form.addEventListener('submit', function () { if (!source) ta.value = area.innerHTML; });
  });
})();
