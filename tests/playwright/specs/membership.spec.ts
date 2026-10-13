/**
 * Suite F - membership shortcodes.
 *
 * Ten shortcodes that all read the logged-in member's own membership. The behaviour worth
 * guarding is the negative case: an anonymous visitor, or a logged-in user with no
 * membership, must get empty output rather than someone else's record or a PHP notice.
 */

import { test, expect, PAGES } from '../fixtures/base';
import { seededIds } from '../fixtures/ids';
import { civiApi4, civiApi4First } from '../fixtures/civi';

const ids = seededIds();

/** The seeded membership, which every case in this file builds on. */
function seededMembership() {
  return civiApi4First<{ id: number; membership_type_id: number; end_date: string; join_date: string }>(
    'Membership.get',
    {
      where: [['contact_id', '=', ids.memberContactId]],
      select: ['id', 'membership_type_id', 'end_date', 'join_date'],
      orderBy: { id: 'ASC' },
    }
  );
}

/** Add another membership for the member; callers delete it again in a finally block. */
function addMembership(values: Record<string, unknown>) {
  return civiApi4First<{ id: number }>('Membership.create', {
    values: { contact_id: ids.memberContactId, ...values },
  });
}

function deleteMembership(id: number) {
  civiApi4('Membership.delete', { where: [['id', '=', id]] });
}

test.describe('Suite F - a member sees their own membership', () => {
  test('F-01 status, type and id match the CiviCRM record', async ({ memberPage }) => {
    const membership = civiApi4<Array<{ id: number; status_id: number; membership_type_id: number }>>(
      'Membership.get',
      {
        where: [['contact_id', '=', ids.memberContactId]],
        select: ['id', 'status_id', 'membership_type_id'],
      }
    )[0];
    test.skip(!membership, 'No membership was seeded - check seed-data.php found a membership type.');

    await memberPage.goto(PAGES.membership);
    const body = (await memberPage.textContent('body')) || '';

    expect(body).toContain(`ID=${membership.id}`);
    // Status and type are rendered as labels rather than ids, so the assertion is only that
    // they are populated - comparing the label text would restate CiviCRM's own option list.
    expect(body).toMatch(/STATUS=\S/);
    expect(body).toMatch(/TYPE=\S/);
  });

  test('F-02 the expiry date matches the membership end date', async ({ memberPage }) => {
    const membership = civiApi4<Array<{ end_date: string }>>('Membership.get', {
      where: [['contact_id', '=', ids.memberContactId]],
      select: ['end_date'],
    })[0];
    test.skip(!membership, 'No membership was seeded.');

    await memberPage.goto(PAGES.membership);
    const body = (await memberPage.textContent('body')) || '';

    // Rendered through CiviCRM's date formatter, so the year is the stable part to assert.
    const year = membership.end_date.slice(0, 4);
    expect(body).toContain('EXPIRY=');
    expect(body).toContain(year);
  });
});

test.describe('Suite F - the most recent membership is shown', () => {
  test('F-03 a later-ending membership is shown instead of the oldest record', async ({ memberPage }) => {
    const seeded = seededMembership();
    test.skip(!seeded, 'No membership was seeded.');

    // The shortcodes used to read memberships in id order, so they showed the first record (the
    // lowest id) or the last one, whichever the code happened to reach. They now show the
    // membership with the latest end date, whatever its id.
    const later = addMembership({
      membership_type_id: seeded.membership_type_id,
      join_date: seeded.join_date,
      start_date: seeded.join_date,
      end_date: new Date(new Date(seeded.end_date).getTime() + 365 * 86400000).toISOString().slice(0, 10),
    });

    try {
      await memberPage.goto(PAGES.membership);
      const body = (await memberPage.textContent('body')) || '';

      expect(body).toMatch(new RegExp(`ID=${later.id}\\b`));
      expect(body).not.toMatch(new RegExp(`ID=${seeded.id}\\b`));
    } finally {
      deleteMembership(later.id);
    }
  });

  test('F-04 a cancelled record with the same end date does not hide the active membership', async ({
    memberPage,
  }) => {
    const seeded = seededMembership();
    test.skip(!seeded, 'No membership was seeded.');

    // A cancelled or pending application for the same period is created after the real
    // membership, so it has the higher id. When end dates tie, the membership with a current
    // status wins; otherwise the member's active membership would show no status at all.
    const cancelled = addMembership({
      membership_type_id: seeded.membership_type_id,
      join_date: seeded.join_date,
      start_date: seeded.join_date,
      end_date: seeded.end_date,
      'status_id:name': 'Cancelled',
      is_override: true,
    });

    try {
      await memberPage.goto(PAGES.membership);
      const body = (await memberPage.textContent('body')) || '';

      expect(body).toMatch(new RegExp(`ID=${seeded.id}\\b`));
      expect(body).not.toMatch(new RegExp(`ID=${cancelled.id}\\b`));
      expect(body).toMatch(/STATUS=\S/);
      expect(body).toContain('EXPIRY=');
    } finally {
      deleteMembership(cancelled.id);
    }
  });
});

test.describe('Suite F - negative cases', () => {
  test('F-05 an anonymous visitor sees no membership data', async ({ anonymousPage }) => {
    const membership = civiApi4<Array<{ id: number; 'membership_type_id:label': string }>>('Membership.get', {
      where: [['contact_id', '=', ids.memberContactId]],
      select: ['id', 'membership_type_id:label'],
    })[0];
    test.skip(!membership, 'No membership was seeded.');

    await anonymousPage.goto(PAGES.membership);
    const body = (await anonymousPage.textContent('body')) || '';

    // Asserted on the member's actual record rather than on the shape of the label text: a
    // whitespace-sensitive regex over "STATUS= TYPE= ..." breaks on any theme or markup
    // change, while "the seeded membership's id must not appear" is the thing that matters.
    expect(body).not.toContain(`ID=${membership.id}`);
    expect(body).not.toMatch(/ID=\d/);

    // With nobody logged in the shortcodes used to query memberships with no contact filter, and
    // so showed another member's type, status and expiry. The type and the expiry sentence are
    // text that only a membership record can produce.
    expect(body).not.toContain(membership['membership_type_id:label']);
    expect(body).not.toContain('Your membership expires');
  });

  test('F-06 a logged-in user with no membership degrades cleanly', async ({ privilegedPage }) => {
    const errors: string[] = [];
    privilegedPage.on('pageerror', (e) => errors.push(e.message));

    await privilegedPage.goto(PAGES.membership);
    const body = (await privilegedPage.textContent('body')) || '';

    // The privileged test contact deliberately has no membership.
    expect(body).not.toMatch(/ID=\d/);
    expect(body).not.toMatch(/Warning|Notice|Fatal error/);
    expect(errors, `page errors: ${errors.join(' | ')}`).toHaveLength(0);
  });
});
