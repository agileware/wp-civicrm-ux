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

  test('A-05 the iCal feed link renders and resolves to a calendar', async ({ anonymousPage, request }) => {
    await anonymousPage.goto(PAGES.eventIcalFeed);
    const href = await anonymousPage.locator('a[href*="ICalFeed"], a[href*="ical"]').first().getAttribute('href');
    expect(href, 'no iCal feed link was rendered').toBeTruthy();

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

      // The confirmation dialog's own submit control; matched on visible text so the test
      // does not depend on the template's internal class names.
      await dialog.getByRole('button', { name: /attend|yes|confirm/i }).first().click();
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
      const button = memberPage.locator('button.event-cancel-registration, .event-cancelregistration button').first();
      await expect(button).toBeVisible();
      await button.click();

      const dialog = memberPage.locator('dialog[open], .event-cancelregistration-confirm-dialog').first();
      await dialog.getByRole('button', { name: /cancel|yes|confirm/i }).first().click();
      await memberPage.waitForLoadState('networkidle');

      expect(getParticipantStatus(own.id)).toBe(PARTICIPANT_STATUS.Cancelled);
    } finally {
      setParticipantStatus(own.id, PARTICIPANT_STATUS.Registered);
    }
  });

  test('B-08 the cancel button is absent for an anonymous visitor', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.cancelRegistration);
    await expect(
      anonymousPage.locator('button.event-cancel-registration, .event-cancelregistration button')
    ).toHaveCount(0);
  });
});
