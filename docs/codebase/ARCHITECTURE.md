# Architecture

## Core Sections (Required)

### 1) Architectural Style

- Primary style: procedural, server-rendered PHP pages organized primarily by feature/domain.
- Why this classification: feature directories contain page-level forms and handlers; pages directly include a shared PDO connection and shared layout fragments. There is no router, framework, service layer, or repository layer in the source tree.
- Primary constraints:
  - Each PHP page is directly addressable and owns its request handling/rendering.
  - Pages share one PDO connection through `config/database.php`.
  - The page layout contract requires `$base` and `$titre` before including `includes/header.php`.

### 2) System Flow

```text
Browser -> feature form/page -> PHP validation and prepared SQL -> MySQL -> HTML response
                                       |
                                       +-> shared header/footer and escaping helpers
```

1. The browser opens a page directly, such as `personne/formulaire.php`.
2. The page loads the PDO connection, shared utility functions, and any static option arrays it needs.
3. The form submits to a feature-specific PHP handler. Some person-form buttons select alternate handlers through `formaction`.
4. The handler checks the HTTP method, validates submitted values, and uses prepared statements to read/write MySQL.
5. The PHP response includes the shared header/footer and escapes displayed values with `h()`.
6. `js/script.js` adds browser-side validation and form display/preview behavior; server-side PHP remains the data validation boundary.

### 3) Layer/Module Responsibilities

| Layer or module | Owns | Must not own | Evidence |
|-----------------|------|--------------|----------|
| Feature page directories | Form rendering, domain request handling, field validation, and feature SQL. | A separate application router; feature pages are direct endpoints. | `personne/formulaire.php`, `personne/enregistrer.php`, `module/module.php` |
| `config/database.php` | PDO initialization and connection failure handling. | Domain-specific queries or page markup. | `config/database.php` |
| `includes/` | HTML escaping, checkbox whitelisting, image validation/storage, fixed option arrays, and common page fragments. | Database connection creation. | `includes/fonctions.php`, `includes/donnees.php`, `includes/header.php` |
| `database/` | MySQL tables, constraints, upgrade statements, and initial lookup rows. | Runtime request handling. | `database/database.sql` |
| `js/script.js` | Client-side validation, form display, and image preview. | Server-side persistence or the authoritative validation gate. | `js/script.js`, `personne/enregistrer.php` |

### 4) Reused Patterns

| Pattern | Where found | Why it exists |
|---------|-------------|---------------|
| Shared PDO connection via `require_once` | `config/database.php` and feature PHP files | Reuses one configured MySQL connection rather than constructing it in every page. |
| Prepared statements | `personne/enregistrer.php`, `personne/liste.php`, and other handlers | Passes query values separately from SQL text. |
| Shared escaping helper | `includes/fonctions.php` and rendered pages | Escapes dynamic values for HTML output. |
| Shared page partials | `includes/header.php`, `includes/footer.php` | Provides common navigation, document structure, and footer. |
| Static allowlists plus database-backed choices | `includes/donnees.php`, `personne/enregistrer.php`, `personne/formulaire.php` | Constrains fixed options while reading lookup tables dynamically. |
| Comma-separated multi-select storage | `database/database.sql`, `personne/enregistrer.php` | Stores several selections in one text column rather than relation tables. |

### 5) Known Architectural Risks

- Person validation and persistence logic is repeated between `personne/enregistrer.php` and `personne/modifier.php`, so rule changes can drift.
- Multi-value choices stored as comma-separated text are harder to query and constrain than normalized relations.
- Authentication and CSRF protection for normal data-changing pages are absent in the inspected application pages. The user confirmed a local/classroom deployment scope; reassess these controls before exposing the application to other users or networks. The diagnostic page has its own localhost and token checks.

### 6) Evidence

- `index.php`
- `personne/formulaire.php`
- `personne/enregistrer.php`
- `personne/modifier.php`
- `config/database.php`
- `includes/fonctions.php`
- `includes/header.php`
- `database/database.sql`
- `js/script.js`
- `tests.php`
