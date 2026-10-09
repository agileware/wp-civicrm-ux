/**
 * Suite J - output escaping and the template loader.
 *
 * These cases vary a shortcode ATTRIBUTE or a stored field value rather than the viewer, so
 * they render through a temporary page (see renderHtml) and assert on the raw HTML: a value an
 * attacker could control must appear as inert text, never as live markup or an attribute.
 *
 * They assume CiviCRM is active (the [civicrm] shortcode must be registered for J-05).
 */

import { test, expect, PAGES } from '../fixtures/base';
import { seededIds } from '../fixtures/ids';
import { civiApi4, civiApi4First, wpCli } from '../fixtures/civi';

const ids = seededIds();

test.describe('Suite J - attribute escaping', () => {
  test('J-01 the iCal feed link does not reflect its type attribute as an attribute', async ({ anonymousPage }) => {
    const html = await renderHtml(anonymousPage, `[ux_event_ical_feed type='x" onmouseover="alert(1)']`);

    // type is reduced to a safe character set and the href is escaped, so the crafted value
    // cannot add an event handler or break out of the attribute.
    expect(html).not.toContain('onmouseover=');
    expect(html).toContain('/wp-json/ICalFeed/event');
  });

  test('J-02 the custom button does not turn a class value into an event handler', async ({ anonymousPage }) => {
    const html = await renderHtml(
      anonymousPage,
      `[ux_custom_button text="Go" url="https://example.test/" css_classes='a" onmouseover="alert(1)']`
    );

    expect(html).not.toContain('onmouseover=');
    // The button still renders, with the class reduced to valid tokens.
    expect(html).toContain('wp-element-button');
  });

  test('J-03 a contact field value is shown as text, not markup', async ({ memberPage }) => {
    const original = civiApi4First<{ last_name: string }>('Contact.get', {
      where: [['id', '=', ids.memberContactId]],
      select: ['last_name'],
    }).last_name;

    try {
      civiApi4('Contact.update', {
        where: [['id', '=', ids.memberContactId]],
        values: { last_name: '<img src=x onerror=alert(1)>' },
      });

      await memberPage.goto(PAGES.contactValue);
      const html = await memberPage.content();

      // The member's own display_name carries the markup; ux_contact_value escapes it.
      expect(html).not.toContain('<img src=x onerror=alert(1)>');
      expect(html).toContain('&lt;img src=x onerror=alert(1)&gt;');
    } finally {
      civiApi4('Contact.update', {
        where: [['id', '=', ids.memberContactId]],
        values: { last_name: original },
      });
    }
  });
});

test.describe('Suite J - calendar register link', () => {
  test('J-04 the register control is a link, not an inline script handler', async ({ request }) => {
    const res = await request.get('/wp-json/civicrm_ux/get_events_all', { params: { start_date: '2000-01-01' } });
    expect(res.status()).toBe(200);
    const body = await res.json();
    const renders: string = (body.result || []).map((e: any) => e.extendedProps?.html_render || '').join('');

    // At least one seeded event allows online registration, so the register control is present.
    expect(renders).toContain('civicrm-ux-event-listing-register');
    // It is an anchor with an href, and never an onclick that runs a URL as script.
    expect(renders).not.toContain('onclick="window.location');
  });
});

test.describe('Suite J - template loader', () => {
  test('J-05 a template attribute cannot redirect the included file', async ({ anonymousPage }) => {
    // The [civicrm component=event action=register] pre-filter renders a plugin template when an
    // event has online registration off. A template attribute must not be able to point that
    // include at an arbitrary file - here /etc/passwd, which would print "root:" if included.
    const original = civiApi4First<{ is_online_registration: boolean }>('Event.get', {
      where: [['id', '=', ids.unattendedEventId]],
      select: ['is_online_registration'],
    }).is_online_registration;

    const slug = `ux-tmp-${Date.now()}-${Math.floor(Math.random() * 1e6)}`;
    wpCli([
      'post', 'create', '--post_type=page', '--post_status=publish',
      `--post_name=${slug}`, '--post_title=UX temp render',
      `--post_content=[civicrm component="event" action="register" template="/etc/passwd"]`,
    ]);

    try {
      civiApi4('Event.update', {
        where: [['id', '=', ids.unattendedEventId]],
        values: { is_online_registration: false },
      });

      await anonymousPage.goto(`/${slug}/?id=${ids.unattendedEventId}`);
      const html = await anonymousPage.content();

      expect(html).not.toContain('root:');
    } finally {
      civiApi4('Event.update', {
        where: [['id', '=', ids.unattendedEventId]],
        values: { is_online_registration: original },
      });
      const id = wpCli(['post', 'list', '--post_type=page', `--name=${slug}`, '--field=ID']).trim();
      if (id) {
        wpCli(['post', 'delete', id, '--force']);
      }
    }
  });
});

/**
 * Render an arbitrary shortcode through a temporary page and return the raw HTML, then remove the
 * page. wpCli passes the content as an exec argument (no shell), so quotes in the payload are safe.
 */
async function renderHtml(page: import('@playwright/test').Page, shortcode: string): Promise<string> {
  const slug = `ux-tmp-${Date.now()}-${Math.floor(Math.random() * 1e6)}`;
  wpCli([
    'post', 'create', '--post_type=page', '--post_status=publish',
    `--post_name=${slug}`, '--post_title=UX temp render',
    `--post_content=${shortcode}`,
  ]);
  try {
    await page.goto(`/${slug}/`);
    return await page.content();
  } finally {
    const id = wpCli(['post', 'list', '--post_type=page', `--name=${slug}`, '--field=ID']).trim();
    if (id) {
      wpCli(['post', 'delete', id, '--force']);
    }
  }
}
