<?php
// Переключение языка: lang.php?l=ru&back=/issue.php
require __DIR__ . '/includes/bootstrap.php';

set_lang(get_str('l', 5));
header('Location: ' . safe_back(get_str('back', 500), ''), true, 303);
