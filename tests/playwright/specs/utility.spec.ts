/**
 * Suite I - utility, activity and GDPR shortcodes.
 *
 * Small surfaces, but two of them are the plugin's main "degrade, don't fatal" cases:
 * ux_convert_date has to cope with a timezone or time string it cannot parse, and
 * ux_gdpr_url depends on a CiviCRM extension that may simply not be installed.
 */

import { test, expect, PAGES } from '../fixtures/base';
import { seededIds } from '../fixtures/ids';
import { civiApi4 } from '../fixtures/civi';

const ids = seededIds();

function field(body: string, label: string): string {
  const match = body.match(new RegExp(`${label}=([^\\n]*)`));
  return match ? match[1].trim() : '';
}

test.describe('Suite I - ux_convert_date', () => {
  test('I-01 converts a time between timezones', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.utility);
    const body = (await anonymousPage.textContent('body')) || '';

    // 15/01/2026 10:00am in Melbourne (UTC+11 in January) is 23:00 UTC on the 14th.
    expect(field(body, 'DATE')).toBe('2026-01-14 23:00');
  });

  test('I-01b an unparseable timezone degrades to a message', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.utility);
    const body = (await anonymousPage.textContent('body')) || '';

    expect(body).not.toContain('There has been a critical error');
    expect(field(body, 'BADTZ')).toContain('Failed to read the timezone string');
  });

  test('I-01c an unparseable time degrades to a message', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.utility);
    const body = (await anonymousPage.textContent('body')) || '';

    expect(field(body, 'BADTIME')).toContain('Failed to read the time string');
  });
});

test.describe('Suite I - ux_custom_button', () => {
  test('I-02 renders a link to the configured url', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.utility);

    const link = anonymousPage.locator('.entry-content a[href*="example.test"]').first();
    await expect(link).toHaveCount(1);
    expect(await link.getAttribute('href')).toContain('example.test');
  });
});

test.describe('Suite I - ux_gdpr_url', () => {
  test('I-03 degrades to "#" when the GDPR extension is absent', async ({ anonymousPage }) => {
    // uk.co.vedaconsulting.gdpr is not part of CiviCRM core and is not installed here. The
    // shortcode used to call a class from it unguarded, and a missing class raises an Error
    // rather than an exception - so it escaped the catch and fatalled the page instead of
    // falling back to '#'. This is the regression test for that.
    await anonymousPage.goto(PAGES.utility);
    const body = (await anonymousPage.textContent('body')) || '';

    expect(body).not.toContain('There has been a critical error');
    expect(field(body, 'GDPR')).toBe('#');
  });
});

test.describe('Suite I - ux_activity_listing', () => {
  test('I-04 a member sees their own activity', async ({ memberPage }) => {
    const seeded = civiApi4<Array<{ id: number }>>('Activity.get', {
      where: [['subject', '=', 'UXTEST Activity']],
      select: ['id'],
    });
    test.skip(!seeded.length, 'No activity was seeded.');

    await memberPage.goto(PAGES.activityListing);
    const body = (await memberPage.textContent('body')) || '';

    expect(body).not.toContain('There has been a critical error');
    expect(body).toContain('UXTEST Activity');
  });

  test('I-04b an activity with no target contact does not break the listing', async ({ memberPage }) => {
    // Regression test. Activity APIv3 builds target_contact_name only while walking an
    // activity's actual targets, so an activity with none has no such key at all - unlike
    // target_contact_id, which is initialised to an empty array either way. Reading the
    // missing key into end() was a TypeError, so a single targetless activity fatalled the
    // whole listing. Confirmed against the CiviCRM 6.16.5 source.
    const seeded = civiApi4<Array<{ id: number }>>('Activity.get', {
      where: [['subject', '=', 'UXTEST Untargeted Activity']],
      select: ['id'],
    });
    test.skip(!seeded.length, 'No untargeted activity was seeded.');

    await memberPage.goto(PAGES.activityListing);
    const body = (await memberPage.textContent('body')) || '';

    expect(body).not.toContain('There has been a critical error');
    expect(body).toContain('UXTEST Untargeted Activity');
    // The targeted activity is still listed alongside it.
    expect(body).toContain('UXTEST Activity');
  });

  test('I-05 an anonymous visitor sees no activity data', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.activityListing);
    const body = (await anonymousPage.textContent('body')) || '';

    expect(body).not.toContain('There has been a critical error');
    expect(body).not.toContain('UXTEST Activity');
  });
});
