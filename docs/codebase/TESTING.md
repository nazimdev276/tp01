# Testing Patterns

## Core Sections (Required)

### 1) Test Stack and Commands

- Primary test framework: none. `tests.php` is a custom, browser-accessible diagnostic and integration-flow page.
- Assertion/mocking tools: custom assertion/request helpers in `tests.php`; no mocking framework is present.
- Commands:

```bash
# Start the required local services
/opt/lampp/lampp start

# Syntax-check PHP source
find . -name '*.php' -exec php -l {} \;
```

Run the application diagnostic at `http://localhost/tp01/tests.php`. It is restricted to local host requests, uses a CSRF token, and its tests temporarily write records and an image before cleanup. No CLI test runner or coverage command is configured.

### 2) Test Layout

- Test file placement pattern: one root-level diagnostic page, `tests.php`.
- Naming convention: no separate test-file naming convention is configured.
- Setup files and where they run: `tests.php` starts a session, loads shared helpers/data, connects to MySQL, then sends HTTP requests to the local application.

### 3) Test Scope Matrix

| Scope | Covered? | Typical target | Notes |
|-------|----------|----------------|-------|
| Unit | No | Individual helpers/functions | No unit-test runner or isolated unit tests found. |
| Integration | Yes, selectively | PHP pages and MySQL CRUD operations | `tests.php` checks page responses and creates/modifies/deletes temporary fixture records. |
| E2E | Partial | Browser-facing form/action flows | The diagnostic sends HTTP requests through application pages; it is not a browser automation suite. |

### 4) Mocking and Isolation Strategy

- Main mocking approach: none evidenced; the diagnostic uses the real local HTTP server and database.
- Isolation guarantees: generated unique test values are used; database cleanup runs in a `finally` block, and the created test image is removed when present.
- Common failure mode in tests: the suite depends on Apache/PHP, MySQL, the database schema, and writable uploads; a local service/configuration failure prevents completion.

### 5) Coverage and Quality Signals

- Coverage tool + threshold: [TODO]; none configured.
- Current reported coverage: [TODO]; no coverage report found.
- Known gaps/flaky areas: no automated unit suite, CI pipeline, or browser automation was detected; the custom diagnostic exercises only selected paths.

### 6) Evidence

- `tests.php`
- `AGENTS.md`
- `.github/copilot-instructions.md`
- `config/database.php`
- `includes/fonctions.php`
- `uploads/.htaccess`

