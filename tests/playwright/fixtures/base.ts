import { test as base, expect, type Page, type Browser } from '@playwright/test';
import users from './test-users.json';

type Fixtures = {
  /** Logged out. Most of this plugin's security behaviour is "what must an anonymous visitor NOT see". */
  anonymousPage: Page;
  /** A typical site member: own registrations, own membership, own contact record. */
  memberPage: Page;
  /** A second member, for cross-user isolation - member A must never see member B's data. */
  otherMemberPage: Page;
  /** A member who additionally holds View All Contacts, so permission-gated shortcodes render. */
  privilegedPage: Page;
  /** The WordPress administrator, for settings screens and admin-only checks. */
  adminPage: Page;
};

async function login(page: Page, username: string, password: string) {
  await page.goto('/wp-login.php');
  await page.locator('#user_login').fill(username);
  await page.locator('#user_pass').fill(password);
  await page.locator('#wp-submit').click();
  await page.waitForURL(/wp-admin/);
}

async function loggedInPage(browser: Browser, username: string, password: string) {
  const context = await browser.newContext();
  const page = await context.newPage();
  await login(page, username, password);
  return { context, page };
}

export const test = base.extend<Fixtures>({
  anonymousPage: async ({ browser }, use) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await use(page);
    await context.close();
  },

  memberPage: async ({ browser }, use) => {
    const { context, page } = await loggedInPage(browser, users.member.username, users.member.password);
    await use(page);
    await context.close();
  },

  otherMemberPage: async ({ browser }, use) => {
    const { context, page } = await loggedInPage(browser, users.otherMember.username, users.otherMember.password);
    await use(page);
    await context.close();
  },

  privilegedPage: async ({ browser }, use) => {
    const { context, page } = await loggedInPage(browser, users.privileged.username, users.privileged.password);
    await use(page);
    await context.close();
  },

  adminPage: async ({ browser }, use) => {
    const { context, page } = await loggedInPage(
      browser,
      process.env.WP_ADMIN_USER || 'admin',
      process.env.WP_ADMIN_PASS || 'admin'
    );
    await use(page);
    await context.close();
  },
});

export { expect, users };

/**
 * Slugs of the WordPress pages created by setup-environment.sh, one per shortcode under test.
 * Keeping them in one place means a spec never hardcodes a slug that the setup script renames.
 */
export const PAGES = {
  eventListing: '/ux-test-event-listing/',
  eventCalendar: '/ux-test-event-calendar/',
  eventIcalFeed: '/ux-test-event-ical-feed/',
  markAttendance: '/ux-test-mark-attendance/',
  cancelRegistration: '/ux-test-cancel-registration/',
  api4Contact: '/ux-test-api4-contact/',
  api4Event: '/ux-test-api4-event/',
  api4MyEvents: '/ux-test-api4-my-events/',
  contactValue: '/ux-test-contact-value/',
  membership: '/ux-test-membership/',
  campaign: '/ux-test-campaign/',
  selfServeChecksum: '/ux-test-self-serve-checksum/',
  utility: '/ux-test-utility/',
  activityListing: '/ux-test-activity-listing/',
} as const;

/** The plugin's "no results" template text, rendered whenever a query returns nothing or is denied. */
export const NO_RESULTS = 'No results found';

/**
 * Read the plugin's REST nonce from a page that renders it.
 *
 * [ux_event_markattendance] and [ux_event_cancelregistration] each emit a `wp_rest` nonce into
 * a global JS variable. WordPress treats a cookie-authenticated REST request with NO nonce as
 * anonymous (see rest_cookie_check_errors), so a request without this header returns 403 no
 * matter who is logged in - which is why the plugin's own JS sends it as X-WP-Nonce.
 */
export async function getRestNonce(page: Page, url: string, varName: string): Promise<string> {
  await page.goto(url);
  const nonce = await page.evaluate((name) => (window as any)[name], varName);
  if (!nonce) {
    throw new Error(`${varName} was not defined on ${url} - is the nonce-emitting shortcode on that page?`);
  }
  return nonce as string;
}

/** Issue a REST GET from inside the page's session, with the nonce header the plugin's JS uses. */
export async function restGet(page: Page, path: string, nonce?: string) {
  return page.evaluate(
    async ({ path, nonce }) => {
      const res = await fetch(path, { headers: nonce ? { 'X-WP-Nonce': nonce } : {} });
      let body: unknown = null;
      try {
        body = await res.json();
      } catch {
        body = null;
      }
      return { status: res.status, body };
    },
    { path, nonce }
  );
}
