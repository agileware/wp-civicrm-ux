/**
 * Suite C - REST endpoints.
 *
 * Every negative case asserts two things: the HTTP status, and that the target record did
 * not change. A 403 that still wrote the update would pass a status-only assertion, which is
 * exactly the defect 1.32.6 fixed - the endpoint used to trust any pid the caller supplied.
 */

import { test, expect, PAGES, getRestNonce, restGet } from '../fixtures/base';
import { seededIds } from '../fixtures/ids';
import {
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

test.describe('Suite C - all-events JSON', () => {
  test('C-13 the endpoint returns valid JSON to an anonymous caller', async ({ request }) => {
    const res = await request.get('/wp-json/civicrm_ux/get_events_all');
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(Array.isArray(body) || typeof body === 'object').toBe(true);
  });
});
