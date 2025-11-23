# Blok45 — WordPress Theme

WordPress theme for blok45.art. The repository contains the full production theme and asset sources. A large part of the application logic was produced as vibe code with the help of an LLM (Codex). Treat every reuse as unreviewed code: audit, adapt, and test before shipping.

## Highlights

- Modular structure: PHP modules live in `public/modules`, page templates in `public/page-templates`, and shared parts in `public/template-parts`.
- Frontend assets are bundled with Gulp/webpack from `src/` into `public/assets`.
- Strings are wrapped with WordPress i18n helpers and ready for `.po/.mo` translations.
- MIT licensed — feel free to fork while keeping attribution.
- Composer handles PHP tooling, Yarn handles the asset pipeline.
- Platform‑agnostic: works on bare metal, Docker, or any LAMP/LEMP stack.

## Repository Layout

- `public/` — final theme: `style.css`, `theme.json`, templates, modules, compiled assets.
- `src/` — SCSS, JS, icons, fonts, and images that are built into `public/assets/`.
- `gulpfile.js`, `webpack.config.js` — asset build pipeline.
- `composer.json` — PHP dependencies (for example `phpmailer/phpmailer`) and dev tools (`wp-coding-standards/wpcs`).
- `package.json`, `yarn.lock` — frontend dependencies and scripts.
- `phpcs.xml` — coding standard ruleset.

## Requirements

- WordPress 6.0+ and PHP 8.1+ (theme relies on modern hooks and types).
- Node.js 18+ and Yarn ≥ 1.22 for asset builds.
- Composer 2.6+ for PHP dependencies.
- (Optional) Docker + Docker Compose for instant environments.
- WP-CLI recommended for theme activation and i18n tasks.

## Installing Yarn

1. With Node.js 18+ just enable Corepack:
   ```bash
   corepack enable
   corepack prepare yarn@stable --activate
   ```
2. Or install via a package manager:
   - macOS: `brew install yarn`
   - Ubuntu/Debian: `npm install -g yarn`

## Quick Start (no Docker)

1. Clone the repo into `wp-content/themes` or symlink the `public` folder.
   ```bash
   git clone https://github.com/antonlukin/blok45.git
   cd blok45/theme
   ```
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Install frontend dependencies and build assets:
   ```bash
   yarn install
   yarn build
   ```
4. Activate the theme via wp-admin or WP-CLI:
   ```bash
   wp theme activate blok45
   ```

## Assets Workflow (Yarn + Gulp)

- `yarn start` — runs Gulp in watch mode: SCSS → CSS, JS → `public/assets/*.min.js`, copies icons/fonts/images.
- `yarn build` — one-off build (use in CI/CD).
- Sources live in `src/styles`, `src/scripts`, `src/images`, `src/icons`, `src/fonts`. Generated files should not be edited manually.
- Lint JavaScript with `npx eslint src/scripts`.

## Docker Environment

The theme plugs into a standard WordPress + MariaDB stack. Example `docker-compose.yml` you can adapt:

```yaml
version: "3.9"

services:
  wordpress:
    image: wordpress:6.5-php8.2
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: blok45
      WORDPRESS_DB_PASSWORD: blok45
      WORDPRESS_DB_NAME: blok45
    volumes:
      - wp_data:/var/www/html
      - ./public:/var/www/html/wp-content/themes/blok45
  db:
    image: mariadb:10.6
    environment:
      MARIADB_DATABASE: blok45
      MARIADB_USER: blok45
      MARIADB_PASSWORD: blok45
      MARIADB_ROOT_PASSWORD: root
    volumes:
      - db_data:/var/lib/mysql

volumes:
  wp_data:
  db_data:
```

1. Save the file and launch the stack: `docker compose up -d`.
2. Install WordPress (browser wizard or `docker compose exec wordpress wp core install ...`).
3. Build assets (`yarn build`) and activate the theme: `docker compose exec wordpress wp theme activate blok45`.

## Composer & Coding Standards

- `composer install` pulls PHPCS tooling.
- Run PHP CodeSniffer via `vendor/bin/phpcs --standard=phpcs.xml public`.
- Optionally auto-fix with `vendor/bin/phpcbf`.

## Localization

- Strings use `__()`, `_e()`, `esc_html__()` with the `blok45` text domain.
- Generate a POT file: `wp i18n make-pot public languages/blok45.pot`.
- Drop `.po/.mo` files into `public/languages` and load them via standard WP flows.

## License & Code Origin

- MIT licensed (see `package.json` and `composer.json`).
- Most business logic (templates, modules) was generated with Codex and then refined manually. Always review for security and project fit before reuse.

## Support

- Integration questions: https://lukin.me
- Issues and feature requests are welcome via GitHub.
