import { civiApi4 } from './civi';

export type SeededIds = {
  memberContactId: number;
  otherContactId: number;
  privilegedContactId: number;
  pastEventId: number;
  futureEventId: number;
  unattendedEventId: number;
  campaignId: number;
  emptyCampaignId: number;
};

const PREFIX = 'UXTEST';

let cached: SeededIds | null = null;

function contactId(rows: Array<{ id: number; first_name: string; last_name: string }>, last: string): number {
  const row = rows.find((r) => r.last_name === last);
  if (!row) {
    throw new Error(
      `No seeded contact "Uxtest ${last}" found. Run fixtures/setup-environment.sh (the CI workflow does this via SETUP_SCRIPT).`
    );
  }
  return row.id;
}

function byTitle(rows: Array<{ id: number; title: string }>, title: string, required = true): number {
  const row = rows.find((r) => r.title === title);
  if (!row && required) {
    throw new Error(`No seeded record titled "${title}" found. Has setup-environment.sh run?`);
  }
  return row ? row.id : 0;
}

function load(): SeededIds {
  if (cached) {
    return cached;
  }

  // Read back from CiviCRM rather than from a handover file. seed-data.php runs as www-data
  // inside the container, while the repository is bind-mounted from the runner's checkout and
  // owned by the runner user - so the seed script cannot write a file the specs could read.
  // Both sides can reach the database, so the records are looked up by the names they were
  // seeded with. Three calls, memoised for the life of the worker process.
  const contacts = civiApi4<Array<{ id: number; first_name: string; last_name: string }>>('Contact.get', {
    where: [
      ['first_name', '=', 'Uxtest'],
      ['is_deleted', '=', false],
    ],
    select: ['id', 'first_name', 'last_name'],
  });

  const events = civiApi4<Array<{ id: number; title: string }>>('Event.get', {
    where: [['title', 'LIKE', `${PREFIX}%`]],
    select: ['id', 'title'],
  });

  // Campaigns are optional: CiviCampaign may be disabled, in which case the entity does not
  // exist and the call throws. That must not break the suites that need no campaign.
  let campaigns: Array<{ id: number; title: string }> = [];
  try {
    campaigns = civiApi4<Array<{ id: number; title: string }>>('Campaign.get', {
      where: [['title', 'LIKE', `${PREFIX}%`]],
      select: ['id', 'title'],
    });
  } catch {
    campaigns = [];
  }

  cached = {
    memberContactId: contactId(contacts, 'Member'),
    otherContactId: contactId(contacts, 'Othermember'),
    privilegedContactId: contactId(contacts, 'Privileged'),
    pastEventId: byTitle(events, `${PREFIX} Past Event`),
    futureEventId: byTitle(events, `${PREFIX} Future Event`),
    unattendedEventId: byTitle(events, `${PREFIX} Unattended Event`),
    campaignId: byTitle(campaigns, `${PREFIX} Campaign`, false),
    emptyCampaignId: byTitle(campaigns, `${PREFIX} Empty Campaign`, false),
  };

  return cached;
}

/**
 * Ids of the records seed-data.php created.
 *
 * Resolution is deferred to first property access rather than happening on import. Specs
 * assign this at module scope, and resolving there would make an unseeded environment a
 * COLLECTION error - `playwright test --list`, IDE test discovery and `--grep` would all fail
 * with zero tests found, rather than the tests that need ids failing individually with a
 * message that says what to run.
 */
export function seededIds(): SeededIds {
  return new Proxy({} as SeededIds, {
    get: (_target, prop: string) => load()[prop as keyof SeededIds],
    has: (_target, prop: string) => prop in load(),
    ownKeys: () => Reflect.ownKeys(load()),
    getOwnPropertyDescriptor: () => ({ enumerable: true, configurable: true }),
  });
}
