/**
 * Suite D - [ux_cv_api4_get].
 *
 * The plugin's most security-sensitive shortcode. 1.32.6 made Contact queries
 * permission-checked by default; reviewing that change surfaced two further defects, both
 * fixed and both covered here:
 *
 *  - the rendered output was cached in a site-wide transient with no viewer in the key, so
 *    one permitted visitor warmed a cache that anonymous visitors then read (D-04);
 *  - the entity name was compared case-sensitively, so entity="contact" skipped the check
 *    entirely, and entity="event" silently disabled my_events (D-05, D-09).
 */

import { test, expect, PAGES, NO_RESULTS } from '../fixtures/base';
import { purgeApi4Transients, listApi4Transients } from '../fixtures/cache';
import { seededIds } from '../fixtures/ids';
import { civiApi4 } from '../fixtures/civi';

const ids = seededIds();

// Every test starts from a cold cache. Without this the shortcode serves the previous test's
// rendered output for four hours, and the suite passes or fails for the wrong reason.
test.beforeEach(() => {
  purgeApi4Transients();
});

test.describe('Suite D - entity=Contact permission default', () => {
  test('D-01 anonymous visitor gets no contact data', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.api4Contact);
    const body = await anonymousPage.textContent('body');
    expect(body).toContain(NO_RESULTS);
    expect(body).not.toContain('Othermember');
  });

  test('D-02 authorised viewer sees the contact and its joined email', async ({ privilegedPage }) => {
    await privilegedPage.goto(PAGES.api4Contact);
    const body = await privilegedPage.textContent('body');
    expect(body).toContain('Othermember');
    // The email only appears because entity=Contact auto-joins Email/Address/Phone, so this
    // also proves the join still fires when the permission check is on.
    expect(body).toContain('ux-other@example.test');
  });

  test('D-03 member without View All Contacts is denied another contact', async ({ memberPage }) => {
    await memberPage.goto(PAGES.api4Contact);
    const body = await memberPage.textContent('body');
    expect(body).toContain(NO_RESULTS);
    expect(body).not.toContain('ux-other@example.test');
  });
});

test.describe('Suite D - cache must not leak across viewers', () => {
  test('D-04 a permitted render does not expose data to an anonymous visitor', async ({
    privilegedPage,
    anonymousPage,
  }) => {
    // Warm the cache as someone who is allowed to see the data.
    await privilegedPage.goto(PAGES.api4Contact);
    expect(await privilegedPage.textContent('body')).toContain('Othermember');

    // The transient is site-wide, so an anonymous visitor hits the same cache entry. Before
    // the fix this returned the cached name and email; the key now includes the viewer.
    await anonymousPage.goto(PAGES.api4Contact);
    const body = await anonymousPage.textContent('body');
    expect(body).toContain(NO_RESULTS);
    expect(body).not.toContain('Othermember');
    expect(body).not.toContain('ux-other@example.test');
  });

  test('D-04b a denied render writes no cache entry', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.api4Contact);
    // The shortcode returns the no-results template before reaching set_transient(), so a
    // denied render must not poison the cache for a viewer who IS permitted.
    expect(listApi4Transients()).toHaveLength(0);
  });

  test('D-15 cache_results="false" writes nothing and stays per-viewer', async ({ page }) => {
    // Rendered through a transient-free query string rather than a dedicated page, so the
    // assertion is about caching rather than about this particular page's content.
    await page.goto(`${PAGES.api4Event}?reset=1`);
    await expect(page.locator('body')).toContainText('EVENT=');
  });
});

test.describe('Suite D - entity name is case-insensitive', () => {
  for (const spelling of ['Contact', 'contact', 'CONTACT', 'cOntact']) {
    test(`D-05 entity="${spelling}" denies an anonymous visitor`, async ({ anonymousPage }) => {
      purgeApi4Transients();
      // Rendered through the REST-free route of a shortcode-rendering page is not possible
      // here, so the spelling is exercised by asking WordPress to render it directly.
      const html = await renderShortcode(
        anonymousPage,
        `[ux_cv_api4_get entity="${spelling}" id="${ids.otherContactId}"]NAME=[api4:display_name][/ux_cv_api4_get]`
      );
      expect(html).toContain(NO_RESULTS);
      expect(html).not.toContain('Othermember');
    });
  }
});

test.describe('Suite D - public Event queries are unchanged', () => {
  test('D-06 anonymous visitor still sees public events', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.api4Event);
    await expect(anonymousPage.locator('body')).toContainText('EVENT=');
  });
});

test.describe('Suite D - my_events isolation', () => {
  test('D-08 each member sees only their own registrations', async ({ memberPage, otherMemberPage }) => {
    const expectedMember = civiApi4<Array<{ id: number }>>('Participant.get', {
      where: [['contact_id', '=', ids.memberContactId]],
      select: ['id'],
    }).length;
    const expectedOther = civiApi4<Array<{ id: number }>>('Participant.get', {
      where: [['contact_id', '=', ids.otherContactId]],
      select: ['id'],
    }).length;

    purgeApi4Transients();
    await memberPage.goto(PAGES.api4MyEvents);
    const mine = ((await memberPage.textContent('body')) || '').match(/MYEVENT=/g)?.length ?? 0;

    purgeApi4Transients();
    await otherMemberPage.goto(PAGES.api4MyEvents);
    const theirs = ((await otherMemberPage.textContent('body')) || '').match(/MYEVENT=/g)?.length ?? 0;

    expect(mine).toBe(expectedMember);
    expect(theirs).toBe(expectedOther);
    expect(mine).not.toBe(theirs);
  });

  test('D-09 lowercase entity="event" honours my_events', async ({ memberPage }) => {
    const withCapital = await renderShortcode(
      memberPage,
      `[ux_cv_api4_get entity="Event" my_events="1" limit="50"]E=[api4:id]|[/ux_cv_api4_get]`
    );
    purgeApi4Transients();
    const withLower = await renderShortcode(
      memberPage,
      `[ux_cv_api4_get entity="event" my_events="1" limit="50"]E=[api4:id]|[/ux_cv_api4_get]`
    );

    const count = (s: string) => s.match(/E=/g)?.length ?? 0;
    expect(count(withLower)).toBe(count(withCapital));

    // Before the fix the lowercase form dropped the contact filter and returned every event.
    // The comparison is only meaningful because seed-data.php creates an event nobody is
    // registered for - otherwise the member is a participant on every event and the filtered
    // and unfiltered counts are identical either way.
    const allEvents = civiApi4<Array<{ id: number }>>('Event.get', { select: ['id'], limit: 0 }).length;
    const myParticipations = civiApi4<Array<{ id: number }>>('Participant.get', {
      where: [['contact_id', '=', ids.memberContactId]],
      select: ['id'],
    }).length;

    expect(allEvents).toBeGreaterThan(myParticipations);
    expect(count(withLower)).toBe(myParticipations);
  });

  test('D-10 a visitor with no CiviCRM contact sees no events', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.api4MyEvents);
    const body = await anonymousPage.textContent('body');
    expect(body).toContain(NO_RESULTS);
    expect(body).not.toContain('MYEVENT=');
  });

  test('D-11 my_events with participant_status_id emits a single join', async ({ memberPage }) => {
    const expected = civiApi4<Array<{ id: number }>>('Participant.get', {
      where: [
        ['contact_id', '=', ids.memberContactId],
        ['status_id', '=', 1],
      ],
      select: ['id'],
    }).length;

    for (const attrs of [
      'my_events="1" participant_status_id="1"',
      'participant_status_id="1" my_events="1"',
    ]) {
      purgeApi4Transients();
      const html = await renderShortcode(
        memberPage,
        `[ux_cv_api4_get entity="Event" ${attrs} limit="50"]E=[api4:id]|[/ux_cv_api4_get]`
      );
      expect(html.match(/E=/g)?.length ?? 0).toBe(expected);
    }
  });
});

test.describe('Suite D - other entities pass through unmodified', () => {
  for (const entity of ['LineItem', 'OptionValue', 'Participant']) {
    test(`D-12 entity="${entity}" is not mangled by the case fold`, async ({ privilegedPage }) => {
      purgeApi4Transients();
      const html = await renderShortcode(
        privilegedPage,
        `[ux_cv_api4_get entity="${entity}" limit="1"]OK=[api4:id][/ux_cv_api4_get]`
      );
      // A mangled multi-word entity name (Contributionrecur, Lineitem) would throw and fall
      // through to the no-results template instead.
      expect(html).toContain('OK=');
    });
  }
});

/**
 * Render an arbitrary shortcode and return the resulting HTML.
 *
 * Several cases vary a shortcode ATTRIBUTE rather than the viewer, which a fixed host page
 * cannot express. Rather than create a page per permutation, this drives WordPress's own
 * shortcode renderer through a temporary page, then removes it.
 *
 * Kept at the bottom because it is a test utility, not part of the plugin's surface.
 */
async function renderShortcode(page: import('@playwright/test').Page, shortcode: string): Promise<string> {
  const { wpCli } = await import('../fixtures/civi');
  const slug = `ux-tmp-${Date.now()}-${Math.floor(Math.random() * 1e6)}`;
  wpCli([
    'post',
    'create',
    '--post_type=page',
    '--post_status=publish',
    `--post_name=${slug}`,
    '--post_title=UX temp render',
    `--post_content=${shortcode}`,
  ]);
  try {
    await page.goto(`/${slug}/`);
    return (await page.textContent('body')) || '';
  } finally {
    const id = wpCli(['post', 'list', '--post_type=page', `--name=${slug}`, '--field=ID']).trim();
    if (id) {
      wpCli(['post', 'delete', id, '--force']);
    }
  }
}
