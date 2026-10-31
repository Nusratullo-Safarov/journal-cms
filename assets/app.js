// Небольшие улучшения интерфейса. Без JavaScript сайт и админ-панель тоже работают.
(function () {
  // Мобильное меню
  var toggle = document.querySelector('[data-nav-toggle]');
  if (toggle) {
    toggle.addEventListener('click', function () {
      var open = document.querySelector('[data-nav]').classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Подтверждение удаления
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!confirm(form.dataset.confirm)) ev.preventDefault();
    });
  });

  // Автоотправка (смена роли пользователя)
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  // Защита от двойной отправки формы
  document.querySelectorAll('form[data-submit-once]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button:not([name])');
      if (btn) setTimeout(function () { btn.disabled = true; }, 0);
    });
  });

  // Вкладки языков в админке. Без JS все языки показаны друг под другом.
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
    buttons.forEach(function (b) { b.addEventListener('click', function () { show(b.dataset.tab); }); });
    show(buttons[0].dataset.tab);
    // Если браузер не пропускает форму из-за пустого обязательного поля на скрытой вкладке — открываем её
    box.querySelectorAll('[required]').forEach(function (input) {
      input.addEventListener('invalid', function () {
        show(input.closest('[role=tabpanel]').id);
      });
    });
  });

  // Простой визуальный редактор для textarea[data-editor]. Сервер всё равно очищает HTML.
  var tools = [
    ['formatBlock:<h2>', 'H2'], ['formatBlock:<h3>', 'H3'], ['formatBlock:<p>', '¶'],
    ['bold', '<b>B</b>'], ['italic', '<i>I</i>'], ['underline', '<u>U</u>'],
    ['insertUnorderedList', '• ≡'], ['insertOrderedList', '1. ≡'],
    ['createLink', '🔗'], ['unlink', '⛓̸'], ['removeFormat', '⌫']
  ];
  document.querySelectorAll('textarea[data-editor]').forEach(function (ta) {
    var wrap = document.createElement('div');
    wrap.className = 'editor';
    var bar = document.createElement('div');
    bar.className = 'editor-bar';
    var area = document.createElement('div');
    area.className = 'editor-area prose';
    area.contentEditable = 'true';
    area.innerHTML = ta.value;
    var source = false;

    tools.forEach(function (t) {
      var b = document.createElement('button');
      b.type = 'button';
      b.innerHTML = t[1];
      b.title = t[0].split(':')[0];
      b.addEventListener('mousedown', function (ev) { ev.preventDefault(); });
      b.addEventListener('click', function () {
        var parts = t[0].split(':');
        var arg = parts[1] || null;
        if (parts[0] === 'createLink') {
          arg = prompt('URL (https://…)', 'https://');
          if (!arg) return;
        }
        document.execCommand(parts[0], false, arg);
        area.focus();
      });
      bar.appendChild(b);
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
    ta.hidden = true;
    ta.removeAttribute('required');
    ta.form.addEventListener('submit', function () {
      if (!source) ta.value = area.innerHTML;
    });
  });
})();
