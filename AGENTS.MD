# Blok45 Theme Agent Notes

## Что это за репозиторий

Это кастомная WordPress-тема для `blok45.art`.

- Репозиторий содержит исходники темы, пайплайн сборки ассетов и сопутствующие `mu-plugins`.
- Деплойная тема лежит в `public/`.
- Исходники фронтенда лежат в `src/`.
- Корень репозитория содержит toolchain: `package.json`, `gulpfile.js`, `webpack.config.js`, `composer.json`, `phpcs.xml`, `Dockerfile`.

Практически это monorepo в миниатюре:

- `public/` — то, что WordPress видит как тему `Blok45`
- `src/` — исходники для сборки в `public/assets/`
- `mu-plugins/` — must-use плагины, которые ставятся рядом с темой, но не являются частью самой темы

## Ключевая структура

### `public/`

Главная папка темы.

- `functions.php` подключает все PHP-модули из `public/modules/` и `template-tags.php`.
- `style.css` содержит заголовок темы WordPress.
- `theme.json` хранит часть конфигурации Gutenberg/theme settings.
- `assets/` — собранные CSS/JS/fonts/images. Эти файлы генерируются, вручную их лучше не править.
- `admin/` — JS для админских экранов метаполей/панелей.
- `page-templates/` — кастомные page templates.
- `template-parts/` — переиспользуемые части шаблонов.

### `public/modules/`

В проекте основная PHP-логика разнесена по self-loaded классам вида `Blok45_Modules_*`. Каждый модуль сам вызывает `load_module()` в конце файла и регистрирует свои хуки.

По назначению:

- `global.php` — базовая конфигурация темы, enqueue стилей, cleanup WP, порядок постов, body/post classes, отключение дефолтных архивов и search.
- `blocks.php` — ограничения и cleanup Gutenberg/core block behavior.
- `comments.php` — глобальное отключение комментариев, REST/XML-RPC comment endpoints и related UI.
- `images.php` — размеры изображений, редирект attachment pages, JPEG/compression, imgproxy-логика.
- `rating.php` — meta `blok45_rating`, REST endpoints и фронтовый лайк/рейтинг.
- `sitemeta.php` — SEO/OG/Twitter meta, favicon/icons, sitemap cleanup.
- `translit.php` — транслитерация slug/file names.
- `filters.php` — taxonomies, post meta, REST API для фильтров, фронтовые и админские scripts для координат и состояния граффити.
- `archive.php` — поведение архивов, сортировка, подключение archive/filter script.
- `map.php` — ассеты карты и подготовка данных для шаблонов карты.
- `gallery.php` — сбор данных галереи и swiper assets для single.
- `single.php` — подготовка контекста для single template.
- `menu.php` — регистрация меню и модификация nav markup/classes.
- `directory.php` — каталог художников, кеширование preview, special archive/rewrite для unknown artists.
- `settings.php` — theme settings/options и отключение кастомайзера.

### `template-tags.php`

Публичный слой helper-функций шаблонов (`blok45_get_*`, `blok45_display_meta()` и т.д.), которые проксируют данные из модулей в шаблоны.

### `src/`

Исходники фронтенда.

- `src/styles/` — SCSS, разделенный на `config/`, `basic/`, `layout/`, `blocks/`.
- `src/scripts/` — отдельные entry points. Каждый `.js` автоматически собирается в отдельный `public/assets/<name>.min.js`.
- `src/icons/` — SVG-иконки, копируются в `public/assets/images/`.
- `src/fonts/`, `src/images/` — статические ассеты, копируются как есть.

Важно: `gulpfile.js` динамически читает все файлы `src/scripts/*.js`, так что новый JS entry обычно достаточно просто добавить в эту директорию.

### `mu-plugins/`

Must-use плагины, которые Dockerfile копирует в `wp-content/mu-plugins`.

- `clear-cache.php`
- `hide-login.php`
- `s3-uploads.php`

Это не часть темы в строгом смысле, но часть рабочего окружения проекта.

### Infra / ops

- `Dockerfile` собирает WordPress image с темой, `mu-plugins`, Redis object cache, `s3-uploads` и несколькими WP plugins.
- `deploy/` содержит Kubernetes/YAML-манифесты.
- `wp-config-docker.php` — docker-specific WP config.

## Как запускать сборку ассетов

Зависимости уже описаны в `package.json`, пакетный менеджер в репозитории — `yarn`.

Установка:

```bash
yarn install
```

Watch-режим для разработки:

```bash
yarn start
```

Это запускает `gulp` default task:

1. собирает стили
2. собирает JS
3. копирует изображения, иконки, шрифты, vendor assets
4. включает watcher для `src/styles/**/*` и `src/scripts/**/*`

Одноразовая production-сборка:

```bash
yarn build
```

Что делает сборка:

- `src/styles/index.scss` -> `public/assets/styles.min.css`
- `src/scripts/*.js` -> `public/assets/*.min.js`
- `src/images/**/*` -> `public/assets/images/`
- `src/icons/*.svg` -> `public/assets/images/`
- `src/fonts/**/*.{ttf,woff,woff2}` -> `public/assets/fonts/`
- `swiper` из `node_modules` -> `public/assets/swiper/`

## Линтеры и проверки

### PHP CodeSniffer

Установка PHP tooling:

```bash
composer install
```

Проверка:

```bash
vendor/bin/phpcs --standard=phpcs.xml public
```

Автоисправление где возможно:

```bash
vendor/bin/phpcbf --standard=phpcs.xml public
```

Текущий ruleset:

- базируется на `WordPress`
- исключает `WordPress-Docs`
- исключает `WordPress.Files.FileName.InvalidClassFileName`
- исключает `WordPress.PHP.YodaConditions`
- исключает `WordPress.WP.I18n.MissingTranslatorsComment`

Из проверки исключены:

- `external/*`
- `wp-config-docker.php`

### ESLint

Отдельного script в `package.json` нет, запускать вручную:

```bash
npx eslint src/scripts
```

Конфиг лежит в `.eslintrc` и расширяет `plugin:@wordpress/eslint-plugin/recommended`.

## Практика правок

- Если меняется фронтенд, почти всегда редактировать надо `src/`, а не `public/assets/`.
- Если меняется PHP-поведение темы, обычно точка входа в `public/modules/*.php`, шаблонах из `public/*.php`, `public/template-parts/` или `public/page-templates/`.
- Новую шаблонную helper-функцию лучше добавлять в `public/template-tags.php`, а тяжелую логику держать в модуле.
- Новые REST endpoints логично добавлять в соответствующий модуль, а не в `functions.php`.
- `functions.php` здесь играет роль bootstrap-файла, а не места для основной бизнес-логики.

## Нюансы проекта

- Тема использует модульную архитектуру поверх стандартного WordPress theme structure.
- На фронте есть зависимости на `Swiper`.
- Фильтры, карта и рейтинг завязаны на кастомные REST routes в namespace `blok45/v1`.
- В коде активно используются кастомные post meta вроде `blok45_coords`, `b45_coords`, `blok45_graffiti_archived`, `blok45_rating`.
- `directory.php` добавляет rewrite/query behavior для special unknown artist archive, так что после изменений rewrite-related логики может понадобиться flush rewrite rules.
- `global.php` намеренно отключает часть стандартного WordPress behavior: дефолтные taxonomies для post, comments feed, user endpoints, search на фронте, часть block/FSE функций.

## Что проверять после изменений

После PHP-правок:

```bash
vendor/bin/phpcs --standard=phpcs.xml public
```

После JS-правок:

```bash
npx eslint src/scripts
yarn build
```

После SCSS-правок:

```bash
yarn build
```

После изменений в rewrite/taxonomies/post meta:

- проверить соответствующие архивы/фильтры/REST endpoints вручную в WordPress
- при необходимости сделать flush permalinks

## Что не забывать агенту

- `public/` — это deployable theme root; WordPress должен видеть именно эту папку как тему `Blok45`.
- Не стоит вручную редактировать собранные файлы в `public/assets/`, если можно изменить исходник в `src/`.
- В рабочем дереве могут быть пользовательские незакоммиченные изменения; не откатывать их без явного запроса.
- Для поиска по проекту удобнее всего `rg`.
- Репозиторий не выглядит как block theme/plugin boilerplate: здесь много кастомной логики, поэтому перед изменением лучше сначала найти соответствующий модуль и связанные template tags.

## Полезные стартовые команды

```bash
rg --files
rg -n "Blok45_Modules_|blok45_" public
vendor/bin/phpcs --standard=phpcs.xml public
npx eslint src/scripts
yarn build
yarn start
```

## Замечание по i18n

В теме используется text domain `blok45`. Новые переводимые строки и связанные настройки лучше держать в этом же соглашении, чтобы не плодить расхождения.
