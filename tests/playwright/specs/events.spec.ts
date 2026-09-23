/**
 * Suites A and B - event display shortcodes, and the participant actions built on them.
 *
 * Suite B depends on the Form Processor definitions imported by setup-environment.sh. Without
 * them the buttons render nothing, so a failure here that looks like "button missing" is
 * worth checking against the setup step's output before hunting in the plugin.
 */

import { test, expect, PAGES } from '../fixtures/base';
import { seededIds } from '../fixtures/ids';
import {
  civiApi4,
  getParticipant,
  getParticipantStatus,
  setParticipantStatus,
  PARTICIPANT_STATUS,
} from '../fixtures/civi';

const ids = seededIds();

test.describe('Suite A - event display', () => {
  test('A-01 anonymous visitor sees public active events', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.eventListing);
    const body = await anonymousPage.textContent('body');
    expect(body).toContain('UXTEST');
  });

  test('A-03 the calendar renders without console errors', async ({ anonymousPage }) => {
    const errors: string[] = [];
    anonymousPage.on('console', (m) => {
      if (m.type() === 'error') errors.push(m.text());
    });
    anonymousPage.on('pageerror', (e) => errors.push(e.message));

    await anonymousPage.goto(PAGES.eventCalendar);
    await anonymousPage.waitForLoadState('networkidle');

    // FullCalendar builds its grid client-side, so an empty container means the JS failed.
    await expect(anonymousPage.locator('.fc, #calendar, [class*="fullcalendar"]').first()).toBeVisible();
    expect(errors, `console errors: ${errors.join(' | ')}`).toHaveLength(0);
  });

  test('A-07 an event with no registration_link_text still renders', async ({ anonymousPage }) => {
    // Regression test. registration_link_text is `varchar(255) NULL` with no default, so an
    // event created through the API rather than CiviCRM's own form leaves it NULL. The
    // listing used to pass that straight into a non-nullable `string` parameter, which is a
    // TypeError in PHP 8 - so one such event took down every page using the shortcode.
    const eventId = ids.unattendedEventId;
    const original = civiApi4<Array<{ registration_link_text: string | null }>>('Event.get', {
      where: [['id', '=', eventId]],
      select: ['registration_link_text'],
    })[0].registration_link_text;

    try {
      civiApi4('Event.update', {
        where: [['id', '=', eventId]],
        values: { registration_link_text: null },
      });

      await anonymousPage.goto(PAGES.eventListing);
      const body = (await anonymousPage.textContent('body')) || '';

      expect(body).not.toContain('There has been a critical error');
      expect(body).toContain('UXTEST');
      // Falls back to the default label rather than rendering an empty link.
      expect(body).toContain('Register now');
    } finally {
      civiApi4('Event.update', {
        where: [['id', '=', eventId]],
        values: { registration_link_text: original },
      });
    }
  });

  test('A-05 the iCal feed link renders and resolves to a calendar', async ({ anonymousPage, request }) => {
    await anonymousPage.goto(PAGES.eventIcalFeed);

    // Scoped to the post body, and matched on the REST namespace only. A looser `a[href*="ical"]`
    // also matches this page's own nav link, because the host page's slug contains "ical".
    const href = await anonymousPage
      .locator('.entry-content a[href*="ICalFeed"]')
      .first()
      .getAttribute('href');
    expect(href, 'no iCal feed link was rendered in the post body').toBeTruthy();

    const res = await request.get(href!);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('BEGIN:VCALENDAR');
  });
});

test.describe('Suite B - mark attendance button visibility', () => {
  test('B-01 renders for a registered participant on a past event', async ({ memberPage }) => {
    await memberPage.goto(PAGES.markAttendance);
    await expect(memberPage.locator('button.event-mark-attendance')).toBeVisible();
  });

  test('B-04 does not render for an anonymous visitor', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.markAttendance);
    await expect(anonymousPage.locator('button.event-mark-attendance')).toHaveCount(0);
  });

  test('B-05 does not render for a contact with no registration', async ({ privilegedPage }) => {
    // The privileged test contact is deliberately not a participant on the seeded events.
    await privilegedPage.goto(PAGES.markAttendance);
    await expect(privilegedPage.locator('button.event-mark-attendance')).toHaveCount(0);
  });

  test('B-06 does not render for a future event', async ({ memberPage }) => {
    // The cancel-registration page hosts the FUTURE event, so the attendance button for it
    // must be absent even though this member is registered.
    await memberPage.goto(PAGES.cancelRegistration);
    await expect(memberPage.locator('button.event-mark-attendance')).toHaveCount(0);
  });
});

test.describe('Suite B - marking attendance through the UI', () => {
  test('B-02 choosing Attended updates the participant status', async ({ memberPage }) => {
    const own = getParticipant(ids.memberContactId, ids.pastEventId);

    try {
      await memberPage.goto(PAGES.markAttendance);
      await memberPage.locator('button.event-mark-attendance').click();

      const dialog = memberPage.locator(`#event-markattendance-confirm-dialog-${ids.pastEventId}`);
      await expect(dialog).toBeVisible();

      // The dialog is a form: pick "Yes" and submit. The radio carries the attendance value
      // (1 = attended), and the hidden attended_status/not_attended_status fields supply the
      // participant status ids the REST call is built from - see
      // templates/shortcode/shortcode-event-markattendance-form.php.
      await dialog.locator('#attendance-yes').check();
      await dialog.locator('button.submit').click();
      await memberPage.waitForLoadState('networkidle');

      expect(getParticipantStatus(own.id)).toBe(PARTICIPANT_STATUS.Attended);
    } finally {
      setParticipantStatus(own.id, PARTICIPANT_STATUS.Registered);
    }
  });
});

test.describe('Suite B - cancel registration', () => {
  test('B-07 a member can cancel their own registration', async ({ memberPage }) => {
    const own = getParticipant(ids.memberContactId, ids.futureEventId);

    try {
      await memberPage.goto(PAGES.cancelRegistration);
      const button = memberPage.locator('button.event-cancel-registration');
      await expect(button).toBeVisible();
      await button.click();

      // Note the dialog id/class say "cancellation" while the trigger button says
      // "cancel-registration" - the markup in event-cancelregistration-button.php is the
      // authority here, not the shortcode name.
      const dialog = memberPage.locator(`#event-cancellation-confirm-dialog-${ids.futureEventId}`);
      await expect(dialog).toBeVisible();
      await dialog.locator('button.confirm-yes').click();
      await memberPage.waitForLoadState('networkidle');

      expect(getParticipantStatus(own.id)).toBe(PARTICIPANT_STATUS.Cancelled);
    } finally {
      setParticipantStatus(own.id, PARTICIPANT_STATUS.Registered);
    }
  });

  test('B-08 the cancel button is absent for an anonymous visitor', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.cancelRegistration);
    await expect(anonymousPage.locator('button.event-cancel-registration')).toHaveCount(0);
  });
});
