-- Сомонаи маҷаллаи илмӣ: ҷадвалҳо ва маълумоти намунавӣ.
-- Одатан ин файлро install.php худ ворид мекунад. Барои воридоти дастӣ:
--   phpMyAdmin → «Импорт» ё  mysql -u USER -p DBNAME < database.sql
-- Пас аз воридоти дастӣ install.php-ро кушоед, то корбари аввалро созед.

SET NAMES utf8mb4;
SET time_zone = '+05:00';

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(64) NOT NULL PRIMARY KEY,
  v TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(50) NOT NULL,
  name VARCHAR(100) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'editor',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sections (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title_tj VARCHAR(200) NOT NULL DEFAULT '',
  title_ru VARCHAR(200) NOT NULL DEFAULT '',
  title_en VARCHAR(200) NOT NULL DEFAULT '',
  sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS issues (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  year SMALLINT UNSIGNED NOT NULL,
  volume VARCHAR(20) NOT NULL DEFAULT '',
  number VARCHAR(20) NOT NULL,
  title_tj VARCHAR(300) NOT NULL DEFAULT '',
  title_ru VARCHAR(300) NOT NULL DEFAULT '',
  title_en VARCHAR(300) NOT NULL DEFAULT '',
  published_at DATE NULL,
  cover VARCHAR(255) NOT NULL DEFAULT '',
  pdf VARCHAR(255) NOT NULL DEFAULT '',
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_issues_year (is_published, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  issue_id INT UNSIGNED NOT NULL,
  section_id INT UNSIGNED NULL,
  sort INT NOT NULL DEFAULT 0,
  pages VARCHAR(20) NOT NULL DEFAULT '',
  udc VARCHAR(100) NOT NULL DEFAULT '',
  doi VARCHAR(150) NOT NULL DEFAULT '',
  title_tj VARCHAR(500) NOT NULL DEFAULT '',
  title_ru VARCHAR(500) NOT NULL DEFAULT '',
  title_en VARCHAR(500) NOT NULL DEFAULT '',
  authors_tj TEXT NOT NULL,
  authors_ru TEXT NOT NULL,
  authors_en TEXT NOT NULL,
  abstract_tj TEXT NOT NULL,
  abstract_ru TEXT NOT NULL,
  abstract_en TEXT NOT NULL,
  keywords_tj TEXT NOT NULL,
  keywords_ru TEXT NOT NULL,
  keywords_en TEXT NOT NULL,
  refs MEDIUMTEXT NOT NULL,
  pdf VARCHAR(255) NOT NULL DEFAULT '',
  views INT UNSIGNED NOT NULL DEFAULT 0,
  downloads INT UNSIGNED NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_articles_issue (issue_id, sort),
  CONSTRAINT fk_articles_issue FOREIGN KEY (issue_id) REFERENCES issues (id),
  CONSTRAINT fk_articles_section FOREIGN KEY (section_id) REFERENCES sections (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(60) NOT NULL,
  title_tj VARCHAR(200) NOT NULL DEFAULT '',
  title_ru VARCHAR(200) NOT NULL DEFAULT '',
  title_en VARCHAR(200) NOT NULL DEFAULT '',
  body_tj MEDIUMTEXT NOT NULL,
  body_ru MEDIUMTEXT NOT NULL,
  body_en MEDIUMTEXT NOT NULL,
  sort INT NOT NULL DEFAULT 0,
  in_menu TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_pages_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title_tj VARCHAR(300) NOT NULL DEFAULT '',
  title_ru VARCHAR(300) NOT NULL DEFAULT '',
  title_en VARCHAR(300) NOT NULL DEFAULT '',
  body_tj MEDIUMTEXT NOT NULL,
  body_ru MEDIUMTEXT NOT NULL,
  body_en MEDIUMTEXT NOT NULL,
  image VARCHAR(255) NOT NULL DEFAULT '',
  published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_news_published (is_published, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS submissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL DEFAULT '',
  affiliation VARCHAR(300) NOT NULL DEFAULT '',
  title VARCHAR(500) NOT NULL,
  comment TEXT NOT NULL,
  file VARCHAR(100) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_submissions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  bucket VARCHAR(100) NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  KEY idx_rate_limits_bucket (bucket, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Маълумоти намунавӣ (дар панели идоракунӣ иваз кунед) ----------

INSERT INTO settings (k, v) VALUES
  ('journal_name_tj', 'Паёми илмӣ'),
  ('journal_name_ru', 'Научный вестник'),
  ('journal_name_en', 'Scientific Bulletin'),
  ('tagline_tj', 'Маҷаллаи илмӣ-назариявӣ ва амалӣ'),
  ('tagline_ru', 'Научно-теоретический и практический журнал'),
  ('tagline_en', 'Scientific, theoretical and practical journal'),
  ('publisher_tj', 'Номи муассисаи таъсисдиҳанда'),
  ('publisher_ru', 'Название учредителя'),
  ('publisher_en', 'Founder name'),
  ('issn_print', ''),
  ('issn_online', ''),
  ('frequency_tj', 'Дар як сол 4 шумора'),
  ('frequency_ru', '4 выпуска в год'),
  ('frequency_en', '4 issues per year'),
  ('address_tj', 'ш. Душанбе'),
  ('address_ru', 'г. Душанбе'),
  ('address_en', 'Dushanbe'),
  ('phone', ''),
  ('email', ''),
  ('logo', ''),
  ('color', '#1d4ed8'),
  ('telegram_bot_token', ''),
  ('telegram_chat_id', ''),
  ('submissions_open', '1');

INSERT INTO sections (title_tj, title_ru, title_en, sort) VALUES
  ('Иқтисодиёт', 'Экономика', 'Economics', 1),
  ('Ҳуқуқ', 'Право', 'Law', 2),
  ('Идоракунии давлатӣ', 'Государственное управление', 'Public administration', 3),
  ('Педагогика', 'Педагогика', 'Pedagogy', 4);

INSERT INTO pages (slug, title_tj, title_ru, title_en, body_tj, body_ru, body_en, sort, in_menu) VALUES
  ('about', 'Дар бораи маҷалла', 'О журнале', 'About the journal',
   '<p>Маҷалла мақолаҳои илмии олимон, омӯзгорон ва мутахассисонро нашр мекунад. Матнро дар панели идоракунӣ иваз кунед.</p>',
   '<p>Журнал публикует научные статьи учёных, преподавателей и специалистов. Измените этот текст в панели управления.</p>',
   '<p>The journal publishes research papers by scholars, lecturers and practitioners. Edit this text in the admin panel.</p>', 1, 1),
  ('editorial-board', 'Ҳайати таҳририя', 'Редакционная коллегия', 'Editorial board',
   '<h2>Сармуҳаррир</h2><p>Насаб Ном Номи падар, доктори илм, профессор</p><h2>Аъзои ҳайати таҳририя</h2><ul><li>Насаб Н.Н., номзади илм</li></ul>',
   '<h2>Главный редактор</h2><p>Фамилия Имя Отчество, доктор наук, профессор</p><h2>Члены редколлегии</h2><ul><li>Фамилия И.О., кандидат наук</li></ul>',
   '<h2>Editor-in-chief</h2><p>Full Name, Doctor of Sciences, Professor</p><h2>Editorial board members</h2><ul><li>Full Name, PhD</li></ul>', 2, 1),
  ('for-authors', 'Талабот ба муаллифон', 'Требования к авторам', 'Author guidelines',
   '<p>Мақола бо формати DOCX, ҳаҷми то 15 саҳифа, бо аннотатсия ва калидвожаҳо ба се забон (тоҷикӣ, русӣ, англисӣ) пешниҳод карда мешавад.</p><ul><li>Шрифт: Times New Roman, 14</li><li>Фосилаи байни сатрҳо: 1,5</li><li>УДК дар аввали мақола</li><li>Адабиёт дар охири мақола</li></ul>',
   '<p>Статья подаётся в формате DOCX, объёмом до 15 страниц, с аннотацией и ключевыми словами на трёх языках (таджикском, русском, английском).</p><ul><li>Шрифт: Times New Roman, 14</li><li>Межстрочный интервал: 1,5</li><li>УДК в начале статьи</li><li>Список литературы в конце статьи</li></ul>',
   '<p>Papers are submitted in DOCX format, up to 15 pages, with an abstract and keywords in three languages (Tajik, Russian, English).</p><ul><li>Font: Times New Roman, 14</li><li>Line spacing: 1.5</li><li>UDC at the beginning</li><li>References at the end</li></ul>', 3, 1),
  ('ethics', 'Одоби нашр', 'Публикационная этика', 'Publication ethics',
   '<p>Ҳамаи мақолаҳо аз тақриз (рецензия) ва санҷиш ба плагиат мегузаранд.</p>',
   '<p>Все статьи проходят рецензирование и проверку на заимствования.</p>',
   '<p>All papers undergo peer review and plagiarism screening.</p>', 4, 1);

INSERT INTO issues (year, volume, number, published_at, is_published) VALUES
  (2026, '', '1', '2026-03-30', 1),
  (2026, '', '2', '2026-06-30', 1);

INSERT INTO articles (issue_id, section_id, sort, pages, udc, title_tj, title_ru, title_en,
  authors_tj, authors_ru, authors_en, abstract_tj, abstract_ru, abstract_en, keywords_tj, keywords_ru, keywords_en, refs) VALUES
  (2, 3, 1, '5-14', '35.07',
   'Рақамикунонии хизматрасониҳои давлатӣ: таҷриба ва дурнамо',
   'Цифровизация государственных услуг: опыт и перспективы',
   'Digitalization of public services: experience and prospects',
   'Раҳимов А.С. | Донишгоҳи миллии Тоҷикистон',
   'Рахимов А.С. | Таджикский национальный университет',
   'Rakhimov A.S. | Tajik National University',
   'Дар мақола таҷрибаи рақамикунонии хизматрасониҳои давлатӣ таҳлил шуда, роҳҳои беҳтар кардани онҳо пешниҳод мегарданд.',
   'В статье анализируется опыт цифровизации государственных услуг и предлагаются пути их совершенствования.',
   'The article analyses the experience of digitalizing public services and proposes ways to improve them.',
   'рақамикунонӣ, хизматрасонии давлатӣ, ҳукумати электронӣ',
   'цифровизация, государственные услуги, электронное правительство',
   'digitalization, public services, e-government',
   'Намунаи адабиёт 1.\nНамунаи адабиёт 2.'),
  (2, 1, 2, '15-24', '330.3',
   'Омилҳои рушди соҳибкории хурд дар минтақаҳо',
   'Факторы развития малого предпринимательства в регионах',
   'Factors of small business development in the regions',
   'Каримова М.Р. | Донишгоҳи давлатии тиҷорат\nСаидов Ф.Н. | Донишгоҳи давлатии тиҷорат',
   'Каримова М.Р. | Государственный университет коммерции\nСаидов Ф.Н. | Государственный университет коммерции',
   'Karimova M.R. | State University of Commerce\nSaidov F.N. | State University of Commerce',
   'Омилҳои асосии рушди соҳибкории хурд ва нақши дастгирии давлатӣ баррасӣ шудаанд.',
   'Рассмотрены основные факторы развития малого предпринимательства и роль государственной поддержки.',
   'The main factors of small business development and the role of state support are considered.',
   'соҳибкории хурд, минтақа, дастгирии давлатӣ',
   'малое предпринимательство, регион, государственная поддержка',
   'small business, region, state support',
   ''),
  (1, 2, 1, '5-12', '342',
   'Асосҳои конститутсионии ҳокимияти маҳаллӣ',
   'Конституционные основы местной власти',
   'Constitutional foundations of local government',
   'Назаров Ҷ.Б. | Донишкадаи такмили ихтисос',
   'Назаров Дж.Б. | Институт повышения квалификации',
   'Nazarov J.B. | Institute of Advanced Training',
   'Асосҳои конститутсионии ташкил ва фаъолияти мақомоти ҳокимияти маҳаллӣ таҳлил карда мешаванд.',
   'Анализируются конституционные основы организации и деятельности органов местной власти.',
   'The constitutional foundations of the organization and activity of local authorities are analysed.',
   'конститутсия, ҳокимияти маҳаллӣ, худидоракунӣ',
   'конституция, местная власть, самоуправление',
   'constitution, local government, self-government',
   '');

INSERT INTO news (title_tj, title_ru, title_en, body_tj, body_ru, body_en) VALUES
  ('Қабули мақолаҳо барои шумораи нав оғоз шуд', 'Начат приём статей в новый выпуск', 'Call for papers for the next issue',
   '<p>Мақолаҳоро тавассути саҳифаи «Ирсоли мақола» фиристед.</p>',
   '<p>Присылайте статьи через страницу «Подать статью».</p>',
   '<p>Please submit your papers via the «Submit a paper» page.</p>');
