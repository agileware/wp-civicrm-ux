import { readFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';

export type SeededIds = {
  memberContactId: number;
  otherContactId: number;
  privilegedContactId: number;
  pastEventId: number;
  futureEventId: number;
  campaignId: number;
  emptyCampaignId: number;
};

const FILE = join(__dirname, 'seeded-ids.json');

let cached: SeededIds | null = null;

function load(): SeededIds {
  if (cached) {
    return cached;
  }
  if (!existsSync(FILE)) {
    throw new Error(
      `${FILE} is missing. Run fixtures/setup-environment.sh (the CI workflow does this via SETUP_SCRIPT).`
    );
  }
  cached = JSON.parse(readFileSync(FILE, 'utf-8')) as SeededIds;
  return cached;
}

/**
 * Ids of the records seed-data.php created.
 *
 * seed-data.php runs inside the WordPress container but writes this file into its own
 * directory - which is the repository, bind-mounted from the runner's checkout. So the file
 * written in the container is the file the specs read on the runner, and no ids have to be
 * re-derived or passed through environment variables.
 *
 * Resolution is deferred to first property access rather than happening on import. Specs
 * assign this at module scope, and reading the file there would make a missing seed file a
 * COLLECTION error - `playwright test --list`, IDE test discovery and `--grep` would all
 * fail with zero tests found, rather than the tests that need ids failing individually with
 * a message that says what to run.
 */
export function seededIds(): SeededIds {
  return new Proxy({} as SeededIds, {
    get: (_target, prop: string) => load()[prop as keyof SeededIds],
    has: (_target, prop: string) => prop in load(),
    ownKeys: () => Reflect.ownKeys(load()),
    getOwnPropertyDescriptor: () => ({ enumerable: true, configurable: true }),
  });
}
