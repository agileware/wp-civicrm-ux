/**
 * Suite E - [ux_contact_value] and [ux_cf_value].
 *
 * 1.32.6 changed how the `permission` attribute is read: an empty value used to skip the
 * check entirely, which meant permission="" silently published another contact's field.
 * It now falls through to a deny, along with any value that is only separators.
 */

import { test, expect, PAGES } from '../fixtures/base';
import { seededIds } from '../fixtures/ids';
import { wpCli } from '../fixtures/civi';

const ids = seededIds();
const DENY = '(permission deny)';

test.describe('Suite E - viewing your own contact', () => {
  test('E-01 a member sees their own field with no permission attribute', async ({ memberPage }) => {
    await memberPage.goto(PAGES.contactValue);
    const body = (await memberPage.textContent('body')) || '';
    expect(body).toContain('OWN=Uxtest Member');
  });
});

test.describe('Suite E - viewing another contact', () => {
  test('E-02a an unauthorised member is denied', async ({ memberPage }) => {
    await memberPage.goto(PAGES.contactValue);
    const body = (await memberPage.textContent('body')) || '';
    expect(body).toContain(`OTHER_DEFAULT=${DENY}`);
    expect(body).not.toContain('OTHER_DEFAULT=Uxtest Othermember');
  });

  test('E-02b an authorised viewer sees the value', async ({ privilegedPage }) => {
    await privilegedPage.goto(PAGES.contactValue);
    const body = (await privilegedPage.textContent('body')) || '';
    expect(body).toContain('OTHER_DEFAULT=Uxtest Othermember');
  });

  test('E-03 permission="" denies rather than disabling the check', async ({ privilegedPage }) => {
    await privilegedPage.goto(PAGES.contactValue);
    const body = (await privilegedPage.textContent('body')) || '';
    // Even for a viewer who WOULD pass the default check, an empty permission must deny -
    // it is an authoring mistake, not an instruction to skip the gate.
    expect(body).toContain(`OTHER_EMPTY=${DENY}`);
  });

  test('E-04 a separator-only permission denies', async ({ privilegedPage }) => {
    const html = await renderShortcode(
      privilegedPage,
      `V=[ux_contact_value id="${ids.otherContactId}" field="display_name" permission=" , "]`
    );
    expect(html).toContain(`V=${DENY}`);
  });

  test('E-05 a missing field attribute reports the missing attribute', async ({ memberPage }) => {
    const html = await renderShortcode(memberPage, 'V=[ux_contact_value]');
    expect(html).toContain('(Not enough attributes)');
  });

  test('E-07 id_from_url still applies the permission check', async ({ memberPage }) => {
    const html = await renderShortcode(
      memberPage,
      'V=[ux_contact_value id_from_url="cid" field="display_name"]',
      `?cid=${ids.otherContactId}`
    );
    // Resolving the contact from the query string must not bypass the gate.
    expect(html).toContain(`V=${DENY}`);
  });
});

/** Render an arbitrary shortcode on a temporary page, optionally with a query string. */
async function renderShortcode(
  page: import('@playwright/test').Page,
  shortcode: string,
  query = ''
): Promise<string> {
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
    await page.goto(`/${slug}/${query}`);
    return (await page.textContent('body')) || '';
  } finally {
    const id = wpCli(['post', 'list', '--post_type=page', `--name=${slug}`, '--field=ID']).trim();
    if (id) {
      wpCli(['post', 'delete', id, '--force']);
    }
  }
}
