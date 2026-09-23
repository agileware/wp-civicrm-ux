/**
 * Suite G - campaign shortcodes.
 *
 * All eight take a single `id` attribute and read straight from CiviCRM, so the assertions
 * here are mostly "does the rendered figure match the database". The cases that carry real
 * weight are the empty campaign (a thermometer must not divide by zero) and a missing id.
 *
 * seed-data.php gives the funded campaign a goal of 1000 and two completed contributions
 * totalling 250, so the expected percentage is exactly 25%.
 */

import { test, expect, PAGES } from '../fixtures/base';
import { seededIds } from '../fixtures/ids';
import { civiApi4 } from '../fixtures/civi';

const ids = seededIds();

/** Pull "LABEL=value" out of the rendered page, one line per shortcode. */
function field(body: string, label: string): string {
  const match = body.match(new RegExp(`${label}=([^\\n]*)`));
  return match ? match[1].trim() : '';
}

test.describe('Suite G - funded campaign', () => {
  test.beforeEach(() => {
    test.skip(!ids.campaignId, 'CiviCampaign is not enabled - campaigns were not seeded.');
  });

  test('G-02 goal and total raised match the campaign record', async ({ anonymousPage }) => {
    const campaign = civiApi4<Array<{ goal_revenue: number }>>('Campaign.get', {
      where: [['id', '=', ids.campaignId]],
      select: ['goal_revenue'],
    })[0];

    await anonymousPage.goto(PAGES.campaign);
    const body = (await anonymousPage.textContent('body')) || '';

    // Both are rendered through CRM_Utils_Money::format, so the amount is asserted on its
    // digits rather than on a currency symbol or separator this site happens to use.
    expect(field(body, 'GOAL')).toContain(String(Math.round(campaign.goal_revenue)));
    expect(field(body, 'RAISED')).toMatch(/250/);
    expect(field(body, 'GOAL')).not.toContain('Campaign not found');
  });

  test('G-03 the contribution count matches completed contributions', async ({ anonymousPage }) => {
    const expected = civiApi4<Array<{ id: number }>>('Contribution.get', {
      where: [
        ['campaign_id', '=', ids.campaignId],
        ['contribution_status_id:name', '=', 'Completed'],
      ],
      select: ['id'],
    }).length;

    await anonymousPage.goto(PAGES.campaign);
    const body = (await anonymousPage.textContent('body')) || '';

    expect(field(body, 'COUNT')).toBe(String(expected));
  });

  test('G-01 the thermometer renders at the funded percentage', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.campaign);

    const meter = anonymousPage.locator('.campaign-thermometer-wrap .campaign-meter span').first();
    await expect(meter).toHaveCount(1);

    // 250 raised against a 1000 goal.
    const width = await meter.evaluate((el) => (el as HTMLElement).style.width);
    expect(width).toBe('25%');
  });

  test('G-01b the info thermometer renders', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.campaign);
    const body = (await anonymousPage.textContent('body')) || '';
    expect(field(body, 'INFO')).not.toContain('Campaign not found');
    expect(await anonymousPage.locator('.campaign-thermometer-wrap').count()).toBeGreaterThan(1);
  });

  test('G-04 days remaining and end date reflect the campaign dates', async ({ anonymousPage }) => {
    const campaign = civiApi4<Array<{ end_date: string }>>('Campaign.get', {
      where: [['id', '=', ids.campaignId]],
      select: ['end_date'],
    })[0];

    await anonymousPage.goto(PAGES.campaign);
    const body = (await anonymousPage.textContent('body')) || '';

    // The seed ends the campaign 30 days out, so this is a positive count, and singular
    // "1 day remaining" is handled separately by the shortcode.
    expect(field(body, 'DAYS')).toMatch(/\d+ days? remaining/);
    expect(field(body, 'DAYS')).not.toMatch(/-\d/);
    expect(field(body, 'END')).toContain(campaign.end_date.slice(0, 4));
  });

  test('G-05 the honour listing renders without exposing a fatal', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.campaign);
    const body = (await anonymousPage.textContent('body')) || '';

    expect(body).not.toContain('There has been a critical error');
    expect(field(body, 'HONOUR')).not.toContain('Campaign not found');
  });
});

test.describe('Suite G - edge cases', () => {
  test('G-06 a campaign with no contributions does not divide by zero', async ({ anonymousPage }) => {
    test.skip(!ids.emptyCampaignId, 'CiviCampaign is not enabled - campaigns were not seeded.');

    await anonymousPage.goto(PAGES.campaign);
    const body = (await anonymousPage.textContent('body')) || '';

    expect(body).not.toContain('There has been a critical error');
    expect(body).not.toMatch(/Division by zero|NAN|INF/i);
    expect(field(body, 'EMPTYCOUNT')).toBe('0');
    // The empty campaign's meter is the last one on the page.
    const meters = anonymousPage.locator('.campaign-thermometer-wrap .campaign-meter span');
    const width = await meters.last().evaluate((el) => (el as HTMLElement).style.width);
    expect(width).toBe('0%');
  });

  test('G-07 a missing id is reported rather than rendering an empty figure', async ({ anonymousPage }) => {
    await anonymousPage.goto(PAGES.campaign);
    const body = (await anonymousPage.textContent('body')) || '';
    expect(field(body, 'NOID')).toContain('Please provide the campaign id');
  });
});
