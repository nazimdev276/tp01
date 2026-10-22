# Coding Conventions

## Core Sections (Required)

### 1) Naming Rules

| Item | Rule | Example | Evidence |
|------|------|---------|----------|
| Files | Lowercase PHP filenames; many use French snake_case. | `afficher_php.php`, `enregistrer.php` | `personne/`, `nationalite/` |
| Functions/methods | Global helper functions use lowercase snake_case; closures are used for local request helpers. | `post_liste()`, `enregistrer_image()`, `$champPost` | `includes/fonctions.php`, `enseignant/enregistrer.php` |
| Types/interfaces | No application classes or interfaces are defined in the inspected source; PHP built-in classes such as `PDO` and `DateTimeImmutable` are used. | `PDO` | `config/database.php`, `enseignant/enregistrer.php` |
| Constants/env vars | Shared option lists use uppercase PHP variables; configuration uses lowercase variables. No environment-variable naming convention is present. | `$CIVILITES`, `$GRADES_ENSEIGNANT`, `$dbname` | `includes/donnees.php`, `config/database.php` |

### 2) Formatting and Linting

- Formatter: none configured.
- Linter: none configured.
- Most relevant enforced rules: no repository-enforced rules were detected; source uses ordinary PHP syntax, prepared SQL, and HTML escaping by convention.
- Run commands:
  - `php -l personne/enregistrer.php`
  - `find . -name '*.php' -exec php -l {} \;`

### 3) Import and Module Conventions

- Import grouping/order: PHP includes appear at the top of each page/handler; a common order is database, shared functions, then static data.
- Alias vs relative import policy: no aliases; use `__DIR__`-relative `require_once` or `require`.
- Public exports/barrel policy: not applicable; this is a collection of directly executable PHP scripts, not an exported module package.

### 4) Error and Logging Conventions

- Error strategy by layer: validation errors are collected in arrays and rendered in French; database writes catch `PDOException` and display user-facing messages. `config/database.php` throws only when diagnostic mode is enabled; otherwise it displays a generic connection error and stops.
- Logging style and required context fields: no application logging library or explicit logging calls were found.
- Sensitive-data redaction rules: [TODO]. The code uses generic database failure messages in the handlers reviewed, but no general redaction policy is documented.

### 5) Testing Conventions

- Test file naming/location rule: no unit-test naming convention; the browser-accessible diagnostic suite is the root-level `tests.php`.
- Mocking strategy norm: none evidenced; the diagnostic suite uses the local HTTP application and MySQL database.
- Coverage expectation: [TODO]. No coverage tool or threshold is configured.

### 6) Evidence

- `AGENTS.md`
- `.github/copilot-instructions.md`
- `config/database.php`
- `includes/fonctions.php`
- `includes/donnees.php`
- `personne/enregistrer.php`
- `tests.php`

