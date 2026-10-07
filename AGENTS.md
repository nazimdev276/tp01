# AGENTS.md

<!--toc:start-->

- [AGENTS.md](#agentsmd)
  - [Environment (XAMPP/LAMPP)](#environment-xampplampp)
  - [Page contract (easy to break)](#page-contract-easy-to-break)
  - [Structure & flow](#structure-flow)
  - [Conventions](#conventions)

<!--toc:end-->

Vanilla PHP 8 multi-page app (university TP01/PAW). No framework, no Composer, no tests, CI, lint, or build config — do not invent commands.

## Environment (XAMPP/LAMPP)

- Served from `/opt/lampp/htdocs/tp01` → `http://localhost/tp01/`.
- Control services: `/opt/lampp/lampp start|restart|stop` (Apache :80, MySQL :3306). `lampp status` may wrongly report MySQL stopped (pid file permissions); check `ss -ltn | grep 3306`.
- MySQL CLI is **not** on `PATH`: `/opt/lampp/bin/mysql -uroot` (no password).
- Database `tp01_paw`; schema + seed data in `database/database.sql` (idempotent: `CREATE IF NOT EXISTS` + `INSERT IGNORE`). Re-import with `/opt/lampp/bin/mysql -uroot < database/database.sql`.
- Only verification available: `find . -name '*.php' -exec php -l {} \;` (PHP 8.5 CLI at `/usr/bin/php`).

## Page contract (easy to break)

Every page must set, **before** `require includes/header.php`:

- `$base` — relative path to app root: `''` in `index.php`, `'../'` in subdirectories. Used by header for CSS/JS/menu links.
- `$titre` — page title.

`includes/header.php` calls `h()`, so `includes/fonctions.php` must be loaded first. `header.php`/`footer.php` are half-page includes, not layout wrappers — a page with no `$base`/`$titre` renders broken links silently.

## Structure & flow

- One file per action, no router/front controller. `personne/` and `nationalite/` each have form → `enregistrer.php` (POST) + `liste.php`.
- The form's buttons route via `formaction` to different handlers: `enregistrer.php` (save), `afficher_php.php` (echo only), `upload_image.php` (image only), `liste.php` (GET). Keep this pattern; don't merge into one endpoint.
- `config/database.php` is the single PDO connection (exceptions on, `FETCH_ASSOC`). Pages `require_once` it; never open a second connection.
- Only dynamic lookup is the nationalité dropdown (table `nationalite`). Everything else comes from static arrays in `includes/donnees.php`.
- `plateformes`/`applications` are comma-joined strings in a single column — not join tables.
- FK `personne.nationalite_code → nationalite(code)` with `ON DELETE RESTRICT`: deleting a used nationality fails at the DB level.

## Conventions

- Validation exists in **both** `js/script.js` and PHP (`enregistrer.php`), with duplicate messages and length rules — update both together. JS validation is skipped for `formnovalidate` buttons, so PHP is the real gate.
- `post_liste()` in `includes/fonctions.php` whitelists checkbox values against `includes/donnees.php` arrays; new option values must be added there _and_ in the JS validator.
- Always output through `h()` (XSS) and use prepared statements (existing code does).
- File uploads go only through `enregistrer_image()` (2 MB cap, `finfo` MIME check, random hex name). `uploads/` must stay writable, and its `.htaccess` blocks PHP execution — never remove it.
- Comments, validation messages, and UI strings are French; keep new text French.
