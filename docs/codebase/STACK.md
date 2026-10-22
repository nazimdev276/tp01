# Technology Stack

## Core Sections (Required)

### 1) Runtime Summary

| Area | Value | Evidence |
|------|-------|----------|
| Primary language | PHP 8; the current CLI reports PHP 8.5.11. The repository does not pin a PHP minor version. | `AGENTS.md`, PHP CLI output |
| Runtime + version | PHP on Apache under the documented XAMPP/LAMPP setup; MySQL is the database. Exact server/database versions are not pinned. | `AGENTS.md`, `config/database.php`, `database/database.sql` |
| Package manager | None detected; there is no Composer or other dependency manifest. | Repository scan; no `composer.json` |
| Module/build system | Directly executed PHP page scripts and browser-native JavaScript; no bundler or build step. | `index.php`, `js/script.js` |

### 2) Production Frameworks and Dependencies

| Dependency | Version | Role in system | Evidence |
|------------|---------|----------------|----------|
| PHP PDO / MySQL PDO driver | Provided by PHP; version not pinned | Database connection and prepared SQL statements. | `config/database.php` |
| MySQL | Version not pinned | Stores application records and lookup values. | `config/database.php`, `database/database.sql` |
| Apache HTTP Server | Version not pinned | Serves the PHP application in the documented XAMPP/LAMPP environment. | `AGENTS.md` |

No PHP framework or third-party application library is declared. `js/script.js` uses plain browser JavaScript.

### 3) Development Toolchain

| Tool | Purpose | Evidence |
|------|---------|----------|
| PHP CLI | Syntax-check PHP files. | `AGENTS.md`, `.github/copilot-instructions.md` |
| `tests.php` | Local web diagnostic for page availability and selected CRUD/upload flows; it is not a unit-test framework. | `tests.php` |
| XAMPP/LAMPP | Local Apache and MySQL services. | `AGENTS.md` |

No repository linter, formatter, build tool, CI pipeline, or package-manager manifest was detected.

### 4) Key Commands

```bash
# Start the local web/database services
/opt/lampp/lampp start

# Check one PHP file
php -l personne/enregistrer.php

# Check all PHP files
find . -name '*.php' -exec php -l {} \;

# Re-import schema and seed values when needed
/opt/lampp/bin/mysql -uroot < database/database.sql
```

The diagnostic suite is run from `http://localhost/tp01/tests.php` and requires the local application/database services.

### 5) Environment and Config

- Config source: `config/database.php` contains the database host, database name, user, and password as PHP literals; no environment-variable template was found.
- Required environment variables: none read by the application code.
- Runtime requirements evidenced by source: PHP with PDO and its MySQL driver; `mbstring` is used for string-length validation, and `fileinfo` plus image functions are used for upload validation.
- Deployment/runtime constraints: repository instructions document a local XAMPP/LAMPP installation at `/opt/lampp/htdocs/tp01`, served at `http://localhost/tp01/`; the user confirmed the intended scope is local/classroom use.

### 6) Evidence

- `AGENTS.md`
- `.github/copilot-instructions.md`
- `config/database.php`
- `database/database.sql`
- `includes/fonctions.php`
- `tests.php`
- `js/script.js`
