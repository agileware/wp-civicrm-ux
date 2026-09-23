/**
 * Suite H - [ux_self_serve_checksum].
 *
 * The point of the 1.32.6 change was to stop the form revealing whether an email address
 * belongs to a contact. Both branches must now produce the same message, the same form
 * state and the same markup - so the central assertion here compares the two responses
 * against each other, not against a fixed string.
 */

import { test, expect, PAGES, users } from '../fixtures/base';
import { wpCli } from '../fixtures/civi';

const NO_SUCH_ADDRESS = 'ux-definitely-not-a-contact@example.invalid';

// Whether an email was actually SENT is deliberately not asserted here. Doing it properly
// needs a mail-capture drop-in in the CI image, which does not exist yet; asserting it from
// the page would only re-test the message, which H-03 already covers more strictly. The
// "no email for a non-matching address" half of the change is verified manually for now
// (test plan section 7.1).

async function submitEmail(page: import('@playwright/test').Page, email: string) {
  await page.goto(PAGES.selfServeChecksum);
  await page.locator('input[name="ss-cs-email"]').fill(email);
  await page.locator('form').first().evaluate((f: HTMLFormElement) => f.requestSubmit());
  await page.waitForLoadState('networkidle');
  return {
    text: ((await page.textContent('body')) || '').replace(/\s+/g, ' ').trim(),
    formVisible: (await page.locator('input[name="ss-cs-email"]').count()) > 0,
  };
}

test.describe('Suite H - enumeration resistance', () => {
  test('H-01 a matching email is accepted', async ({ anonymousPage }) => {
    const result = await submitEmail(anonymousPage, users.member.email);
    expect(result.text).toMatch(/an email will be sent/i);
    expect(result.formVisible).toBe(false);
    // The protected content must NOT be revealed merely by submitting the form.
    expect(result.text).not.toContain('UX_PROTECTED_CONTENT');
  });

  test('H-02 a non-matching email is accepted identically', async ({ anonymousPage }) => {
    const result = await submitEmail(anonymousPage, NO_SUCH_ADDRESS);
    expect(result.text).toMatch(/an email will be sent/i);
    expect(result.formVisible).toBe(false);
    // The old behaviour showed "not in our records" here, and kept the form on screen.
    expect(result.text).not.toMatch(/not in our records/i);
  });

  test('H-03 the two responses are indistinguishable', async ({ browser }) => {
    const render = async (email: string) => {
      const context = await browser.newContext();
      const page = await context.newPage();
      const result = await submitEmail(page, email);
      await context.close();
      return result;
    };

    const matched = await render(users.member.email);
    const unmatched = await render(NO_SUCH_ADDRESS);

    expect(matched.formVisible).toBe(unmatched.formVisible);
    // The submitted address is echoed into the confirmation, so it is normalised out before
    // comparing: everything else must be byte-identical.
    const normalise = (s: string, email: string) => s.split(email).join('{EMAIL}');
    expect(normalise(matched.text, users.member.email)).toBe(normalise(unmatched.text, NO_SUCH_ADDRESS));
  });
});

test.describe('Suite H - input validation', () => {
  test('H-04 a tampered nonce sends nothing and redisplays the form', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.selfServeChecksum);
    await anonymousPage.locator('input[name="ss-cs-email"]').fill(users.member.email);
    await anonymousPage
      .locator('input[name="ux_self_serve_checksum_nonce"]')
      .evaluate((el: HTMLInputElement) => {
        el.value = 'deliberately-invalid-nonce';
      });
    await anonymousPage.locator('form').first().evaluate((f: HTMLFormElement) => f.requestSubmit());
    await anonymousPage.waitForLoadState('networkidle');

    // The form comes back so the visitor can retry. Note that the plugin's "Security check
    // failed" message never actually displays - the variable holding it is overwritten before
    // rendering (pre-existing, see section 7.2 of the test plan) - so this asserts the
    // observable behaviour rather than the intended message.
    await expect(anonymousPage.locator('input[name="ss-cs-email"]')).toBeVisible();
    const text = (await anonymousPage.textContent('body')) || '';
    expect(text).not.toMatch(/an email will be sent/i);
  });

  test('H-05 a malformed address is rejected', async ({ anonymousPage }) => {
    // The browser's own validation would block a bad address, so the type is relaxed first -
    // the server-side check is what this case is about.
    await anonymousPage.goto(PAGES.selfServeChecksum);
    const field = anonymousPage.locator('input[name="ss-cs-email"]');
    await field.evaluate((el: HTMLInputElement) => el.setAttribute('type', 'text'));
    await field.fill('not-an-email');
    await anonymousPage.locator('form').first().evaluate((f: HTMLFormElement) => f.requestSubmit());
    await anonymousPage.waitForLoadState('networkidle');

    expect((await anonymousPage.textContent('body')) || '').toMatch(/valid email address/i);
  });
});

test.describe('Suite H - checksum links', () => {
  test('H-06 a valid checksum link reveals the protected content', async ({ anonymousPage }) => {
    const { seededIds } = await import('../fixtures/ids');
    const ids = seededIds();
    const cs = wpCli([
      'eval',
      `civicrm_initialize(); echo \\Civi\\Api4\\Contact::getChecksum(FALSE)->setContactId(${ids.memberContactId})->execute()->first()['checksum'];`,
    ]).trim();

    await anonymousPage.goto(`${PAGES.selfServeChecksum}?cid=${ids.memberContactId}&cs=${cs}`);
    expect((await anonymousPage.textContent('body')) || '').toContain('UX_PROTECTED_CONTENT');
  });

  test('H-07 a tampered checksum is refused', async ({ anonymousPage }) => {
    const { seededIds } = await import('../fixtures/ids');
    const ids = seededIds();

    await anonymousPage.goto(`${PAGES.selfServeChecksum}?cid=${ids.memberContactId}&cs=0000_0000_0000`);
    const text = (await anonymousPage.textContent('body')) || '';
    expect(text).not.toContain('UX_PROTECTED_CONTENT');
    expect(text).toMatch(/expired or is invalid/i);
  });
});

test.describe('Suite H - logged-in visitors', () => {
  test('H-08 a logged-in contact bypasses the form entirely', async ({ memberPage }) => {
    await memberPage.goto(PAGES.selfServeChecksum);
    const text = (await memberPage.textContent('body')) || '';
    expect(text).toContain('UX_PROTECTED_CONTENT');
    await expect(memberPage.locator('input[name="ss-cs-email"]')).toHaveCount(0);
  });
});
