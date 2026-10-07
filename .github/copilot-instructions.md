# Copilot instructions for tp01

## Build, test, and lint commands

This repository is a vanilla PHP 8 multi-page application. There is no framework, Composer setup, test runner, CI pipeline, or build step to invoke.

Use the project’s actual validation commands:

- Syntax-check a single PHP file:
  - `php -l personne/enregistrer.php`
- Syntax-check the whole project:
  - `find . -name '*.php' -exec php -l {} \;`
- Start the local PHP/MySQL environment:
  - `/opt/lampp/lampp start`
- Re-import the schema and seed data if needed:
  - `/opt/lampp/bin/mysql -uroot < database/database.sql`

The app is served from `http://localhost/tp01/` and lives under `/opt/lampp/htdocs/tp01`.

## High-level architecture

This app is structured as a set of page-level PHP scripts rather than a router/front controller:

- `index.php` is the landing page.
- `personne/` contains the person CRUD flow:
  - `formulaire.php` renders the form
  - `enregistrer.php` validates and inserts into `personne`
  - `liste.php` displays records
  - `afficher_php.php` and `upload_image.php` are alternate handlers reached via specific form actions
- `nationalite/` contains the nationality flow:
  - `nationalite.php` renders the form
  - `enregistrer.php` validates and inserts into `nationalite`
  - `liste.php` displays records

Core application wiring:

- `config/database.php` is the single PDO connection used across the app; keep this as the one DB entry point.
- `includes/donnees.php` contains the static arrays used by the UI (`$CIVILITES`, `$PAYS`, `$PLATEFORMES`, `$APPLICATIONS`).
- `includes/fonctions.php` centralizes the utility functions, notably:
  - `h()` for HTML escaping/XSS-safe output
  - `post_liste()` to whitelist checkbox values against the allowed arrays
  - `enregistrer_image()` to validate and save uploaded images
- `js/script.js` mirrors validation rules in JavaScript for the form UX and preview behavior.

Important data-flow conventions:

- The nationality dropdown is loaded dynamically from the `nationalite` table in MySQL.
- `plateformes` and `applications` are stored as comma-joined strings in a single database column rather than in join tables.
- `personne.nationalite_code` references `nationalite(code)` and is protected by a foreign key with `ON DELETE RESTRICT`.
- Uploaded files are stored under `uploads/` and must keep the existing `.htaccess` protection in place.

## Key conventions

- Page contract: every page must define `$base` and `$titre` before including `includes/header.php`.
  - use `''` in `index.php`
  - use `'../'` in subdirectories
- `includes/header.php` / `includes/footer.php` are partial includes, not full layouts; do not omit the required variables.
- Keep the form-action pattern intact: the same form can submit to different handlers using `formaction` and `formnovalidate`.
- Validation must be updated in both PHP and JavaScript when changing field rules or allowed values.
  - JavaScript validation is bypassed for `formnovalidate` buttons, so PHP is the real gate.
- `post_liste()` whitelists checkbox values against `includes/donnees.php`; add any new option in both the PHP arrays and the JS validator.
- Use prepared statements for all SQL and escape output with `h()` before rendering to HTML.
- Keep UI text and comments in French.
- Do not add another PDO database connection; re-use `config/database.php`.
- Keep the application simple: one file per action, no router/front controller, no framework.

## Added project context

- The schema and seed data live in `database/database.sql` and are intended to be idempotent.
- File uploads are intentionally strict: maximum 2 MB, MIME check via `finfo`, and random hex filename generation.
- The repository does not include a higher-level app framework, so the safest way to validate changes is to run PHP lint checks and keep the existing page/form patterns consistent.
