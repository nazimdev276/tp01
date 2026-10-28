# Copilot instructions for tp01

## Build, test, and lint commands

This is a vanilla PHP 8 application with no Composer setup, test runner, CI pipeline, formatter, or build step.

- Syntax-check one PHP file: `php -l personne/enregistrer.php` (replace with the file being changed).
- Syntax-check all PHP files: `find . -name '*.php' -exec php -l {} \;`
- Start local Apache/MySQL: `/opt/lampp/lampp start`
- Import the schema and seed data when needed: `/opt/lampp/bin/mysql -uroot < database/database.sql`
- Run the integration diagnostics in a browser at `http://localhost/tp01/tests.php`. The page exercises selected routes and CRUD/upload flows against the local app and database; it has no individual-test CLI selector. It requires the services and schema above and writable `uploads/`.

The application is served from `/opt/lampp/htdocs/tp01`.

## High-level architecture

- Apache serves PHP files directly. `index.php` is the landing page; there is no router, framework, or front controller.
- Feature directories own their forms, request handlers, SQL, and list pages. The main areas cover people/students and travelers, teachers, modules, filières, lookup data (nationalities, sports, transport), and grade bulletins.
- Pages share `config/database.php` for the PDO connection, and `includes/` for the header/footer partials, escaping and upload helpers, and static option lists. `js/script.js` supplies client-side form validation and UI behavior.
- `database/database.sql` defines the MySQL schema, constraints, upgrade statements, and seed values. Some choices are loaded from lookup tables (such as nationalities, filières, sports, and transport); fixed options are defined in `includes/donnees.php`.
- Relationships are enforced by foreign keys, including `module.filiere_code` and `personne.nationalite_code`. Several multi-select values on `personne` are stored as comma-separated strings in a single column.
- Uploaded images are stored in `uploads/`; its `.htaccess` protection is part of the upload safety boundary.

## Project structure and page roles

- `index.php` links to the main features. Pages are directly addressable; links and form actions identify the next page or handler.
- `personne/` contains the person form, save/list/update/delete handlers, and alternate form actions for display and image upload. It also contains traveler search and list/delete pages.
- `enseignant/`, `module/`, and `filiere/` each provide a management form and a list page; their management forms support searching and record changes.
- `nationalite/`, `sport/`, and `transport/` manage lookup values used by other forms. Each has a list page; transport also has a delete handler.
- `notes/bulletin.php` searches for a student and manages their module grades; `notes/liste.php` displays grades, and `notes/modules.php` returns JSON modules for a filière.
- `config/` holds the PDO connection. `includes/` holds shared page fragments, helper functions, and fixed option values. `database/` holds the schema and seed data; `css/` and `js/` hold browser assets; `uploads/` holds images.
- `tests.php` is the local diagnostic page. Do not edit it without explicit authorization.

## Key conventions

- Before including `includes/header.php`, each page must define `$base` and `$titre`; load `includes/fonctions.php` first because the header uses `h()`. Use `''` at the root and `'../'` in feature directories. The header and footer are partials, not complete page layouts.
- Use `require_once` with `__DIR__`-relative paths. Reuse the single PDO connection from `config/database.php`; do not create another connection.
- Keep each action in its existing direct PHP endpoint and preserve form routing patterns such as `formaction` and `formnovalidate`; don't introduce a router or merge alternate handlers into one endpoint.
- Validate submitted data in PHP, which is authoritative. When changing a rule mirrored in the browser, update `js/script.js` too. `formnovalidate` bypasses browser validation, not server validation.
- Use prepared statements for SQL and `h()` for dynamic HTML output. Use `post_liste()` with the allowlists in `includes/donnees.php` for checkbox values.
- Route image uploads through `enregistrer_image()`; it enforces a 2 MB limit, checks MIME and image contents, and generates a random filename. Preserve `uploads/.htaccess`.
- Keep UI text, validation messages, and comments in French.
- `tests.php` is a localhost-only diagnostic page that performs temporary writes and cleanup; do not modify it unless explicitly authorized.
