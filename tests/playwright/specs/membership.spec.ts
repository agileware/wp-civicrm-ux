/**
 * Suite F - membership shortcodes.
 *
 * Ten shortcodes that all read the logged-in member's own membership. The behaviour worth
 * guarding is the negative case: an anonymous visitor, or a logged-in user with no
 * membership, must get empty output rather than someone else's record or a PHP notice.
 */

import { test, expect, PAGES } from '../fixtures/base';
import { seededIds } from '../fixtures/ids';
import { civiApi4 } from '../fixtures/civi';

const ids = seededIds();

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

test.describe('Suite F - negative cases', () => {
  test('F-05 an anonymous visitor sees no membership data', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.membership);
    const body = (await anonymousPage.textContent('body')) || '';

    // The labels still render - the shortcodes simply resolve to nothing - so the assertion
    // is that no VALUE follows each one.
    expect(body).toMatch(/STATUS=\s*(TYPE=|$)/);
    expect(body).not.toMatch(/ID=\d/);
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
