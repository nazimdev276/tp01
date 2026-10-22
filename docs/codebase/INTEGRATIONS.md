# External Integrations

## Core Sections (Required)

### 1) Integration Inventory

| System | Type (API/DB/Queue/etc) | Purpose | Auth model | Criticality | Evidence |
|--------|---------------------------|---------|------------|-------------|----------|
| MySQL database `tp01_paw` | Database | Stores people, teachers, modules, study programs, and lookup records. | PDO credentials are configured as literals in PHP; current configuration uses local `root` with an empty password. | High | `config/database.php`, `database/database.sql` |
| Apache/PHP local web stack | Web server/runtime | Serves directly addressable PHP pages for the confirmed local/classroom scope. | No application login/session authorization was found in ordinary feature pages. | High | `AGENTS.md`, `index.php`, feature handlers |

No third-party API, message queue, or event bus was detected. The HTTP requests in `tests.php` target the local application as part of its diagnostics.

### 2) Data Stores

| Store | Role | Access layer | Key risk | Evidence |
|-------|------|--------------|----------|----------|
| MySQL `tp01_paw` | Application records and dynamic option tables. | Shared PDO handle from `config/database.php`; schema and seeds in `database/database.sql`. | Local root/blank-password settings are unsafe if carried into an externally reachable deployment. | `config/database.php`, `database/database.sql` |
| Filesystem `uploads/` | Stores uploaded images referenced by person rows. | `enregistrer_image()` in `includes/fonctions.php`; served from `uploads/`. | Old images are not visibly removed when replaced or when a person is deleted. | `includes/fonctions.php`, `personne/enregistrer.php`, `personne/supprimer.php`, `uploads/.htaccess` |

### 3) Secrets and Credentials Handling

- Credential sources: hard-coded local MySQL values in `config/database.php`; no `.env.example`, `.env.template`, or environment-variable reads were detected.
- Hardcoding checks: database host, database name, username, and password are PHP literals. The file comments identify the values as local XAMPP settings.
- Rotation or lifecycle notes: [TODO]. No credential rotation mechanism is present in the repository.

### 4) Reliability and Failure Behavior

- Retry/backoff behavior: none found for database operations.
- Timeout policy: no database timeout is configured in `config/database.php`; behavior for the local Apache/MySQL stack is otherwise [TODO].
- Circuit-breaker or fallback behavior: none found. Database connection errors are rethrown only in diagnostic mode; otherwise a generic message is shown and execution stops.

### 5) Observability for Integrations

- Logging around external calls: no explicit logging calls were found.
- Metrics/tracing coverage: none detected.
- Missing visibility gaps: database errors are not logged by the application; handlers generally show generic messages to users.

### 6) Evidence

- `config/database.php`
- `database/database.sql`
- `includes/fonctions.php`
- `tests.php`
- `personne/enregistrer.php`
- `personne/supprimer.php`
- `uploads/.htaccess`
