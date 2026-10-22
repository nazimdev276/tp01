# Codebase Structure

## Core Sections (Required)

### 1) Top-Level Map

| Path | Purpose | Evidence |
|------|---------|----------|
| `index.php` | Landing page and navigation to the application's management areas. | `index.php` |
| `personne/` | Person/student and traveler forms, handlers, display, list, update, and delete pages. | `personne/formulaire.php`, `personne/enregistrer.php`, `personne/voyageurs.php` |
| `enseignant/` | Teacher entry, management, and list pages. | `enseignant/enseignant.php`, `enseignant/enregistrer.php`, `enseignant/liste.php` |
| `module/` | Module management and list pages. | `module/module.php`, `module/enregistrer.php`, `module/liste.php` |
| `filiere/` | Study-program management and list pages. | `filiere/filiere.php`, `filiere/enregistrer.php`, `filiere/liste.php` |
| `nationalite/`, `sport/`, `transport/` | Lookup-data forms, handlers, and lists; transport also has a delete handler. | `nationalite/`, `sport/`, `transport/` |
| `config/` | Shared database connection. | `config/database.php` |
| `includes/` | Shared header/footer, utility functions, and static form option arrays. | `includes/header.php`, `includes/fonctions.php`, `includes/donnees.php` |
| `database/` | MySQL schema, upgrade statements, and seed values. | `database/database.sql` |
| `css/`, `js/` | Stylesheet and browser-side form behavior. | `css/style.css`, `js/script.js` |
| `uploads/` | Uploaded images; `.htaccess` denies PHP-like script execution in this directory. | `uploads/.htaccess` |
| `tests.php` | Local-only diagnostic page that checks routes and exercises selected data flows. | `tests.php` |
| `.github/`, `docs/` | Copilot repository instructions and generated codebase documentation. | `.github/copilot-instructions.md`, `docs/codebase/` |

### 2) Entry Points

- Main runtime entry: `index.php`.
- Secondary entry point: `tests.php` for local diagnostics; feature pages are also directly addressable PHP scripts.
- How entry is selected: Apache serves the PHP path directly. There is no router or front controller. The home page links to feature pages.

### 3) Module Boundaries

| Boundary | What belongs here | What must not be here |
|----------|-------------------|------------------------|
| Page/domain directories | Feature-specific HTML forms, request handling, validation, and queries for their domain. | Shared database setup or general escaping/upload helpers, which live in `config/` and `includes/`. |
| `config/` | The single PDO connection to MySQL. | Feature-specific page rendering. |
| `includes/` | Shared layout fragments, utility functions, and fixed option data. | Feature-specific POST endpoints. |
| `database/` | Schema, schema upgrades, and seed data. | PHP page rendering. |
| `css/`, `js/`, `uploads/` | Browser presentation/behavior and stored user image files. | PHP page handlers in the upload directory; `.htaccess` denies those scripts. |

### 4) Naming and Organization Rules

- File naming pattern: lowercase names, commonly French snake_case, such as `afficher_php.php` and `database.php`; domain pages are grouped by lowercase feature directory.
- Directory organization pattern: feature/domain folders plus shared `config/`, `includes/`, `database/`, and asset folders.
- Import/path convention: PHP files use `require_once`/`require` with `__DIR__`-relative paths; no aliases or module system are configured.

### 5) Evidence

- `index.php`
- `personne/formulaire.php`
- `personne/enregistrer.php`
- `enseignant/enseignant.php`
- `module/module.php`
- `config/database.php`
- `includes/header.php`
- `uploads/.htaccess`

