# Playwright frontend tests

End-to-end tests for the WP CiviCRM UX plugin, run against a live WordPress + CiviCRM site.

The test **environment** is centrally managed in
[agileware/ci-workflows](https://github.com/agileware/ci-workflows)
(`.github/workflows/playwright-tests.yml`); the **tests** live here and are extended
alongside the plugin. `.github/workflows/frontend-tests.yml` wires the two together and runs
on every push to `master`, every pull request, and on demand.

## Layout

| Path | Purpose |
|---|---|
| `specs/` | One spec file per suite of the test plan |
| `fixtures/base.ts` | Logged-in page fixtures per role, page slugs, REST helpers |
| `fixtures/civi.ts` | `cv api3`/`api4` and `wp` helpers for data-level assertions |
| `fixtures/cache.ts` | Transient purging — see *Caching* below |
| `fixtures/ids.ts` | Reads the ids written by `seed-data.php` |
| `fixtures/setup-environment.sh` | Roles, users, Form Processors, seed data, host pages |
| `fixtures/seed-data.php` | CiviCRM records, run through `cv scr` |
| `fixtures/resolve-page-placeholders.php` | Substitutes seeded ids into the host pages |
| `fixtures/test-users.json` | Credentials shared by the setup script and the specs |

## Running locally

Requires a WordPress + CiviCRM site with this plugin active, and `wp` and `cv` reachable.
Under ddev:

```bash
cd tests/playwright
npm ci
npx playwright install --with-deps

export BASE_URL=https://your-site.ddev.site
export CIVI_EXEC_PREFIX="ddev exec"
export WP_ADMIN_USER=admin WP_ADMIN_PASS=admin

npm run seed      # one-off: creates roles, users, Form Processors, seed data and pages
npm test
```

`CIVI_EXEC_PREFIX` is prepended to every `wp`/`cv` call, so the specs can read and write
CiviCRM from outside the container. CI sets it to a `docker exec` command.

## Two things that will bite you

**Caching.** `[ux_cv_api4_get]` caches its *rendered output* in a site-wide WordPress
transient for four hours. When the query runs with permission checks the output varies by
viewer, so whoever loads the page first decides what everyone else sees. Any test asserting
per-user behaviour must call `purgeApi4Transients()` first — `api4-get.spec.ts` does this in
a `beforeEach`. `?reset=1` is not a substitute: it bypasses the cache *read* but still
writes, so it poisons the next test.

**REST nonces.** WordPress treats a cookie-authenticated REST request with no nonce as
anonymous, so a request without `X-WP-Nonce` returns 403 no matter who is logged in. Use
`getRestNonce()` and `restGet()` from `fixtures/base.ts`, which read the nonce the plugin's
own shortcodes emit. `C-06` asserts this deliberately.

## Conventions

- **Assert on data, not just the page.** A 403 only means something if the record did not
  change. Use the `civi.ts` helpers to check, as `rest-endpoints.spec.ts` does.
- **Restore what you mutate.** Specs that change a participant status reset it in a
  `finally`, so spec ordering can never matter.
- **Name seeded records `UXTEST…`.** Assertions should never depend on incidental data.
- **Form Processors are required** for the attendance and cancellation suites. They ship with
  the plugin under `data/` and are imported by the setup script. A "button missing" failure
  is worth checking against that step's output first.

## Adding tests

A new shortcode or REST endpoint should arrive with its own spec, a host page in
`setup-environment.sh`, and any records it needs in `seed-data.php`. Keep environment
concerns in the central workflow and plugin-specific setup here.
