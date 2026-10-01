/**
 * Suite C - REST endpoints.
 *
 * Every negative case asserts two things: the HTTP status, and that the target record did
 * not change. A 403 that still wrote the update would pass a status-only assertion, which is
 * exactly the defect 1.32.6 fixed - the endpoint used to trust any pid the caller supplied.
 */

import type { APIRequestContext, Page } from '@playwright/test';
import { test, expect, PAGES, getRestNonce, restGet } from '../fixtures/base';
import { seededIds } from '../fixtures/ids';
import {
  civiApi4,
  civiApi4First,
  getParticipant,
  getParticipantStatus,
  setParticipantStatus,
  PARTICIPANT_STATUS,
} from '../fixtures/civi';

const ids = seededIds();

function markAttendanceUrl(
  pid: number,
  eid: number,
  attendance = 1,
  // Widened to number: PARTICIPANT_STATUS is `as const`, so inferring from the default would
  // pin these to the literals 2 and 3 and reject C-07's deliberately arbitrary status id.
  aStat: number = PARTICIPANT_STATUS.Attended,
  naStat: number = PARTICIPANT_STATUS.NoShow
) {
  return `/wp-json/civicrm_ux/mark-event-attendance/${pid}/${eid}/${attendance}/${aStat}/${naStat}`;
}

test.describe('Suite C - mark attendance', () => {
  test('C-01 the owning contact can mark their own attendance', async ({ memberPage }) => {
    const own = getParticipant(ids.memberContactId, ids.pastEventId);
    const nonce = await getRestNonce(memberPage, PAGES.markAttendance, 'event_markattendance_wp_nonce');

    try {
      const res = await restGet(memberPage, markAttendanceUrl(own.id, ids.pastEventId), nonce);
      expect(res.status).toBe(200);
      expect(getParticipantStatus(own.id)).toBe(PARTICIPANT_STATUS.Attended);
    } finally {
      // Leave the environment as it was found, so ordering between specs cannot matter.
      setParticipantStatus(own.id, PARTICIPANT_STATUS.Registered);
    }
  });

  test("C-02 a contact cannot mark another contact's attendance", async ({ memberPage }) => {
    const theirs = getParticipant(ids.otherContactId, ids.pastEventId);
    const before = getParticipantStatus(theirs.id);
    const nonce = await getRestNonce(memberPage, PAGES.markAttendance, 'event_markattendance_wp_nonce');

    const res = await restGet(memberPage, markAttendanceUrl(theirs.id, ids.pastEventId), nonce);

    expect(res.status).toBe(403);
    // The status assertion is the real test: the fix is an ownership check, not a hidden button.
    expect(getParticipantStatus(theirs.id)).toBe(before);
  });

  test('C-03 a logged-out caller is refused', async ({ anonymousPage }) => {
    const own = getParticipant(ids.memberContactId, ids.pastEventId);
    const before = getParticipantStatus(own.id);

    await anonymousPage.goto('/');
    const res = await restGet(anonymousPage, markAttendanceUrl(own.id, ids.pastEventId));

    expect(res.status).toBe(403);
    expect(getParticipantStatus(own.id)).toBe(before);
  });

  test('C-04 a pid/eid mismatch is refused', async ({ memberPage }) => {
    const own = getParticipant(ids.memberContactId, ids.pastEventId);
    const before = getParticipantStatus(own.id);
    const nonce = await getRestNonce(memberPage, PAGES.markAttendance, 'event_markattendance_wp_nonce');

    // A participant id the contact does own, but paired with the wrong event.
    const res = await restGet(memberPage, markAttendanceUrl(own.id, ids.futureEventId), nonce);

    expect(res.status).toBe(403);
    expect(getParticipantStatus(own.id)).toBe(before);
  });

  test('C-05 a non-existent pid is refused, not a server error', async ({ memberPage }) => {
    const nonce = await getRestNonce(memberPage, PAGES.markAttendance, 'event_markattendance_wp_nonce');
    const res = await restGet(memberPage, markAttendanceUrl(99999999, ids.pastEventId), nonce);
    expect(res.status).toBe(403);
  });

  test('C-06 a logged-in caller with no nonce is treated as anonymous', async ({ memberPage }) => {
    const own = getParticipant(ids.memberContactId, ids.pastEventId);
    const before = getParticipantStatus(own.id);

    // rest_cookie_check_errors() calls wp_set_current_user(0) when no nonce is present, so
    // this is 403 despite a valid session. Asserted deliberately: it is why a URL pasted into
    // the address bar can never reach this endpoint, and why the plugin's JS sends the header.
    const res = await restGet(memberPage, markAttendanceUrl(own.id, ids.pastEventId));

    expect(res.status).toBe(403);
    expect(getParticipantStatus(own.id)).toBe(before);
  });

  test('C-07 the route accepts an arbitrary status id for the caller’s own record', async ({
    memberPage,
  }) => {
    const own = getParticipant(ids.memberContactId, ids.pastEventId);
    const nonce = await getRestNonce(memberPage, PAGES.markAttendance, 'event_markattendance_wp_nonce');

    try {
      // a_stat is caller-supplied and unvalidated, so a member can set their own participant
      // status to any id - here Cancelled. This documents current behaviour rather than
      // endorsing it; see section 7 of the test plan.
      const res = await restGet(
        memberPage,
        markAttendanceUrl(own.id, ids.pastEventId, 1, PARTICIPANT_STATUS.Cancelled),
        nonce
      );
      expect(res.status).toBe(200);
      expect(getParticipantStatus(own.id)).toBe(PARTICIPANT_STATUS.Cancelled);
    } finally {
      setParticipantStatus(own.id, PARTICIPANT_STATUS.Registered);
    }
  });
});

test.describe('Suite C - cancel registration', () => {
  test('C-08 a logged-out caller is refused', async ({ anonymousPage }) => {
    await anonymousPage.goto('/');
    const res = await restGet(anonymousPage, `/wp-json/civicrm_ux/cancel-event-registration/${ids.futureEventId}`);
    expect(res.status).toBe(403);
  });

  test('C-09 a registered contact can cancel their own registration', async ({ memberPage }) => {
    const own = getParticipant(ids.memberContactId, ids.futureEventId);
    const nonce = await getRestNonce(
      memberPage,
      PAGES.cancelRegistration,
      'event_cancellation_wp_nonce'
    );

    try {
      const res = await restGet(
        memberPage,
        `/wp-json/civicrm_ux/cancel-event-registration/${ids.futureEventId}`,
        nonce
      );
      expect(res.status).toBe(200);
      expect(getParticipantStatus(own.id)).toBe(PARTICIPANT_STATUS.Cancelled);
    } finally {
      setParticipantStatus(own.id, PARTICIPANT_STATUS.Registered);
    }
  });
});

test.describe('Suite C - iCal feeds', () => {
  test('C-10 the internal feed serves a calendar for the correct hash', async ({ request }) => {
    const { wpCli } = await import('../fixtures/civi');
    const hash = wpCli(['option', 'get', 'internal_ical_hash']).trim();
    expect(hash).not.toBe('');

    const res = await request.get(`/wp-json/ICalFeed/manage?hash=${hash}`);
    expect(res.status()).toBe(200);
    expect(res.headers()['content-type']).toContain('text/calendar');
    expect(await res.text()).toContain('BEGIN:VCALENDAR');
  });

  test('C-10b the feeds still serve a calendar with no named site timezone', async ({ request }) => {
    // Regression test. WordPress leaves timezone_string EMPTY when the site is configured by
    // UTC offset rather than by city, which is the default on a fresh install. The feed built
    // its VTIMEZONE from that value and called ->serialize() on the FALSE it got back, so
    // every iCal endpoint returned a 500. VTIMEZONE is optional, so the feed should simply
    // omit it.
    const { wpCli } = await import('../fixtures/civi');
    const original = wpCli(['option', 'get', 'timezone_string']).trim();
    const hash = wpCli(['option', 'get', 'internal_ical_hash']).trim();

    try {
      wpCli(['option', 'update', 'timezone_string', '']);

      for (const url of [`/wp-json/ICalFeed/manage?hash=${hash}`, '/wp-json/ICalFeed/event']) {
        const res = await request.get(url);
        expect(res.status(), `${url} should not 500 without a named timezone`).toBe(200);
        expect(await res.text()).toContain('BEGIN:VCALENDAR');
      }
    } finally {
      wpCli(['option', 'update', 'timezone_string', original]);
    }
  });

  for (const [label, query] of [
    ['a wrong hash', '?hash=deadbeefdeadbeefdeadbeefdeadbeef'],
    ['an empty hash', '?hash='],
    ['no hash at all', ''],
  ] as const) {
    test(`C-11 the internal feed 404s for ${label}`, async ({ request }) => {
      // hash_equals() replaced a loose == comparison in 1.32.6. The empty and missing cases
      // matter most: with == an unset option would have compared equal to an empty string.
      const res = await request.get(`/wp-json/ICalFeed/manage${query}`);
      expect(res.status()).toBe(404);
    });
  }

  test('C-12 the external feed is public and returns a calendar', async ({ request }) => {
    const res = await request.get('/wp-json/ICalFeed/event');
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('BEGIN:VCALENDAR');
  });
});

type CalendarEvent = {
  id: number;
  extra_fields?: Record<string, unknown>;
  extendedProps: Record<string, unknown>;
};

async function getEventsAll(request: APIRequestContext, params: Record<string, string>) {
  const res = await request.get('/wp-json/civicrm_ux/get_events_all', {
    params: { start_date: '2000-01-01', ...params },
  });
  expect(res.status()).toBe(200);
  const events = (await res.json()).result as CalendarEvent[];
  // Every negative case below asserts an absence, which an empty result would pass vacuously.
  expect(events.length).toBeGreaterThan(0);
  return events;
}

/** The field configuration [ux_event_fullcalendar] hands to its script. */
async function calendarConfig(page: Page, url: string) {
  await page.goto(url);
  return page.evaluate(() => (window as any).uxFullcalendar as Record<string, string | undefined>);
}

test.describe('Suite C - all-events JSON', () => {
  test('C-13 the endpoint returns valid JSON to an anonymous caller', async ({ request }) => {
    const res = await request.get('/wp-json/civicrm_ux/get_events_all');
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(Array.isArray(body) || typeof body === 'object').toBe(true);
  });

  test('C-14 unsigned extra_fields return nothing, joins included', async ({ request }) => {
    // The reported disclosure: Event.get runs without permission checks and follows joins, so
    // these two names used to return the name and email of whoever created each event.
    const events = await getEventsAll(request, {
      extra_fields: 'created_id.display_name,created_id.email_primary.email,max_participants',
    });

    for (const event of events) {
      expect(event.extra_fields, `event ${event.id}`).toBeUndefined();
      expect(event.extendedProps.extra_fields, `event ${event.id}`).toBeUndefined();
    }
  });

  test('C-15 an unsigned image_src_field other than the default is refused', async ({ request }) => {
    // The same select as extra_fields, returned through file.uri and image_url instead.
    const events = await getEventsAll(request, { image_src_field: 'created_id.email_primary.email' });

    for (const event of events) {
      expect(event.extendedProps['file.uri'], `event ${event.id}`).toBeUndefined();
      expect(event.extendedProps.image_url, `event ${event.id}`).toBeUndefined();
    }
  });

  test('C-16 the shortcode signs only the fields the calendar may publish', async ({ anonymousPage }) => {
    const config = await calendarConfig(anonymousPage, PAGES.eventCalendarFields);

    expect(config.extra_fields).toBe('max_participants');
    expect(config.image_src_field).toBe('file.uri');
    expect(config.fields_sig).toMatch(/^[0-9a-f]{64}$/);
  });

  test('C-17 the signed configuration from the page is honoured', async ({ anonymousPage, request }) => {
    const config = await calendarConfig(anonymousPage, PAGES.eventCalendarFields);

    const events = await getEventsAll(request, {
      extra_fields: config.extra_fields!,
      image_src_field: config.image_src_field!,
      fields_sig: config.fields_sig!,
    });

    for (const event of events) {
      expect(Object.keys(event.extra_fields ?? {}), `event ${event.id}`).toEqual(['max_participants']);
    }
  });

  test("C-18 a page's signature does not authorise different fields", async ({ anonymousPage, request }) => {
    const config = await calendarConfig(anonymousPage, PAGES.eventCalendarFields);

    const events = await getEventsAll(request, {
      extra_fields: 'created_id.email_primary.email',
      image_src_field: config.image_src_field!,
      fields_sig: config.fields_sig!,
    });

    for (const event of events) {
      expect(event.extra_fields, `event ${event.id}`).toBeUndefined();
    }
  });

  test('C-19 an event with "Show Location" off is listed without its address', async ({ request }) => {
    // CiviCRM's own event pages hide the venue when is_show_location is off. The seeded events
    // have no venue, so this gives one a location block, checks it is listed while the flag is
    // on - otherwise the hidden case would pass vacuously - then turns the flag off.
    const eventId = ids.unattendedEventId;
    const where = [['id', '=', eventId]];
    const original = civiApi4First<{ loc_block_id: number | null; is_show_location: boolean }>('Event.get', {
      where,
      select: ['loc_block_id', 'is_show_location'],
    });
    const address = civiApi4First<{ id: number }>('Address.create', {
      values: { street_address: 'UXTEST 1 Venue Street', city: 'Melbourne', location_type_id: 1 },
    });
    const locBlock = civiApi4First<{ id: number }>('LocBlock.create', { values: { address_id: address.id } });

    try {
      civiApi4('Event.update', { where, values: { loc_block_id: locBlock.id, is_show_location: true } });
      const shown = (await getEventsAll(request, {})).find((e) => e.id === eventId);
      expect(shown?.extendedProps.street_address).toBe('UXTEST 1 Venue Street');

      civiApi4('Event.update', { where, values: { is_show_location: false } });
      const hidden = (await getEventsAll(request, {})).find((e) => e.id === eventId);
      expect(hidden).toBeDefined();
      expect(hidden!.extendedProps.street_address).toBeNull();
      expect(hidden!.extendedProps.html_render).not.toContain('Venue Street');
    } finally {
      civiApi4('Event.update', {
        where,
        values: { loc_block_id: original.loc_block_id, is_show_location: original.is_show_location },
      });
      civiApi4('LocBlock.delete', { where: [['id', '=', locBlock.id]] });
      civiApi4('Address.delete', { where: [['id', '=', address.id]] });
    }
  });

  test('C-20 a calendar with a single attribute still sanitises its types', async ({ anonymousPage }) => {
    // The sanitising block was guarded by count($atts) > 1, so a lone types attribute reached
    // the event type filter unsanitised while the endpoint filtered on the sanitised label.
    const config = await calendarConfig(anonymousPage, PAGES.eventCalendarSingleAttr);

    expect(config.types).toBe('Conference');
    expect((config as Record<string, unknown>).filterTypes).toEqual(['Conference']);
  });
});
