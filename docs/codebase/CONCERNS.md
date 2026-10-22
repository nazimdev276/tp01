# Codebase Concerns

## Core Sections (Required)

### 1) Top Risks (Prioritized)

| Severity | Concern | Evidence | Impact | Suggested action |
|----------|---------|----------|--------|------------------|
| High if externally reachable | Normal feature pages have no visible authentication or CSRF checks; the database configuration uses local `root` with an empty password. | `config/database.php`, `personne/supprimer.php`, `enseignant/enseignant.php`, `tests.php` | The user confirmed local/classroom use. If deployment scope changes to network-accessible, unauthorized or cross-site requests could change data, and a compromised app process may have excessive database privileges. | Before any external exposure, add authentication/authorization and CSRF protections, and use a least-privilege database account with deployment-managed secrets. |
| Medium | Replaced/deleted person records can leave uploaded image files behind. | `includes/fonctions.php`, `personne/enregistrer.php`, `personne/modifier.php`, `personne/supprimer.php` | Unreferenced files accumulate in `uploads/`. | Add safe cleanup when a photo is replaced or its record is deleted, accounting for shared references if applicable. |
| Medium | Person multi-select values are stored as comma-separated text, and list pages fetch complete result sets. | `database/database.sql`, `personne/enregistrer.php`, `personne/liste.php` | Filtering/reporting is awkward; memory and response size grow with record count. | Normalize multi-valued fields and add pagination if the application needs larger datasets. |

### 2) Technical Debt

| Debt item | Why it exists | Where | Risk if ignored | Suggested fix |
|-----------|---------------|-------|-----------------|---------------|
| Duplicated person validation and write paths | Both the main save handler and separate edit page implement similar field rules and updates. | `personne/enregistrer.php`, `personne/modifier.php` | A future rule change can be applied inconsistently. | Share narrowly scoped validation/persistence helpers if the flows continue to evolve. |
| Deployment credentials are fixed in source | Configuration is directly assigned in the one PDO entry point for the documented local setup. | `config/database.php` | Local defaults could be reused in an unsafe deployment. | Keep local defaults out of deployed configuration; use a least-privilege account and deployment-managed secrets. |
| Multi-value columns are denormalized | Several selected values are joined and stored in `VARCHAR` columns. | `database/database.sql`, `personne/enregistrer.php` | More complex queries, validation, and schema growth. | Normalize only if query/reporting needs justify the migration. |

### 3) Security Concerns

| Risk | OWASP category (if applicable) | Evidence | Current mitigation | Gap |
|------|--------------------------------|----------|--------------------|-----|
| Data-changing feature pages have no visible access-control or CSRF-token checks. | A01:2021 Broken Access Control | `personne/supprimer.php`, `module/module.php`, `enseignant/enseignant.php`; the only matching session/token checks are in `tests.php`. | Input validation and prepared statements are used in the handlers reviewed; user confirmed a local/classroom deployment scope. | The ordinary mutation pages do not establish who may perform a change or verify a per-request CSRF token. Reassess before network exposure. |
| Database credentials are local administrator defaults in the PHP source. | N/A | `config/database.php` | A comment documents the local XAMPP assumption; connection errors are not exposed with raw database details in normal mode. | `root` with a blank password is not suitable for a network-accessible deployment. |
| Upload execution is blocked in the upload directory. | N/A | `includes/fonctions.php`, `uploads/.htaccess` | MIME and image checks, a 2 MB limit, randomized filename, and Apache denial for PHP-like extensions. | Keep the directory protection and writable permissions aligned during deployment. |

### 4) Performance and Scaling Concerns

| Concern | Evidence | Current symptom | Scaling risk | Suggested improvement |
|---------|----------|-----------------|-------------|-----------------------|
| Person list loads all rows and columns into memory. | `personne/liste.php` | `fetchAll()` is used for the unfiltered list and row rendering. | Memory use and response size increase with the number of records. | Add bounded pagination if datasets grow. |
| Multiple selections use comma-separated text instead of relations. | `database/database.sql`, `personne/enregistrer.php` | Some transport checks require string parsing (`FIND_IN_SET`/`REPLACE`). | Filtering, indexing, integrity checks, and reporting are limited. | Normalize only if expected data volume or query needs require it. |

### 5) Fragile/High-Churn Areas

| Area | Why fragile | Churn signal | Safe change strategy |
|------|-------------|--------------|----------------------|
| Person form and handlers | Large form surface, multiple alternate form actions, and duplicated validation/update logic. | Recent repository history identifies `personne/formulaire.php`, `personne/afficher_php.php`, `personne/liste.php`, and `personne/enregistrer.php` among the most frequently changed files. | Preserve each form action, keep PHP validation authoritative, update the edit and save flows together, and run the local diagnostic where available. |
| Shared header and database schema | Header variables affect every page; schema/seed changes affect multiple features. | Recent repository history identifies `includes/header.php` and `database/database.sql` among the highest-churn files. | Check page contract and existing schema-upgrade behavior; run PHP syntax checks and the local diagnostic after changes. |

### 6) `[ASK USER]` Questions

1. [ASK USER] Resolved — Is this application strictly for local/classroom use, or will it be reachable by other users or networks? The user confirmed local/classroom use. If that scope changes, revisit authentication/authorization and CSRF protection before external exposure.

### 7) Evidence

- `config/database.php`
- `personne/enregistrer.php`
- `personne/modifier.php`
- `personne/supprimer.php`
- `personne/liste.php`
- `module/module.php`
- `enseignant/enseignant.php`
- `database/database.sql`
- `includes/fonctions.php`
- `uploads/.htaccess`
- `tests.php`
- `AGENTS.md`
