import { wpCli } from './civi';

/**
 * Purge the transients written by [ux_cv_api4_get].
 *
 * That shortcode caches its RENDERED OUTPUT in a site-wide WordPress transient for four
 * hours. When the query runs with permission checks the output varies by viewer, so whoever
 * loads the page first decides what everyone else sees until it expires. Any test asserting
 * per-user behaviour must therefore start from a cold cache, or it will read the previous
 * test's result and pass or fail for the wrong reason.
 *
 * Note that `?reset=1` is NOT a substitute: it bypasses the cache read but still writes the
 * cache, so it would poison the next test.
 */
export function purgeApi4Transients(): void {
  // Deleted through wp-cli's own transient API rather than a raw DELETE, so that an object
  // cache (if one is ever added to the CI image) is invalidated too.
  wpCli([
    'transient',
    'delete',
    '--all',
  ]);
}

/**
 * Names of the transients this shortcode writes, for tests that need to assert on the cache
 * itself rather than just clear it - for example, proving that a permission-denied render
 * writes no cache entry at all.
 */
export function listApi4Transients(): string[] {
  // `wp option list` goes through $wpdb, whereas `wp db query` shells out to the mysql client
  // binary - which the WordPress container does not ship ("env: 'mysql': No such file or
  // directory"). Same result, no external dependency, and no table prefix to resolve.
  const out = wpCli([
    'option',
    'list',
    '--search=_transient_ux_cv_api4_get*',
    '--field=option_name',
  ]);
  return out.split('\n').map((l) => l.trim()).filter(Boolean);
}
