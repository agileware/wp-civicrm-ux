import { execFileSync } from 'node:child_process';

/**
 * Shells out to `cv` to read and write CiviCRM data directly, bypassing the browser.
 *
 * The security behaviour this suite guards is mostly invisible in the UI: a 403 from the
 * attendance endpoint is only meaningful if the participant's status genuinely did not
 * change, and a permission-denied shortcode is only meaningful if no data reached the page.
 * Both need a data-level assertion.
 *
 * CIVI_EXEC_PREFIX (set by the CI workflow to a `docker exec ...` command targeting the
 * WordPress container) is prepended to `cv`. Locally under ddev, set it to `ddev exec`.
 */
function execPrefixParts(): string[] {
  const prefix = process.env.CIVI_EXEC_PREFIX;
  return prefix ? prefix.split(' ').filter(Boolean) : [];
}

function run(args: string[]): string {
  const all = [...execPrefixParts(), ...args];
  const [cmd, ...rest] = all;
  return execFileSync(cmd, rest, { encoding: 'utf-8' });
}

export function civiApi4<T = any>(entityDotAction: string, params: Record<string, unknown> = {}): T {
  return JSON.parse(run(['cv', 'api4', entityDotAction, JSON.stringify(params)]));
}

export function civiApi3<T = any>(entity: string, action: string, params: Record<string, unknown> = {}): T {
  return JSON.parse(run(['cv', 'api3', `${entity}.${action}`, JSON.stringify(params)]));
}

/** Run a wp-cli command in the WordPress container, e.g. wpCli(['transient', 'delete', '--all']). */
export function wpCli(args: string[]): string {
  return run(['wp', ...args]);
}

export function civiApi4First<T = any>(entityDotAction: string, params: Record<string, unknown> = {}): T {
  const rows = civiApi4<T[]>(entityDotAction, params);
  if (!rows.length) {
    throw new Error(`cv api4 ${entityDotAction} returned nothing for ${JSON.stringify(params)}`);
  }
  return rows[0];
}

// ---------------------------------------------------------------- lookups

export function getContactIdByEmail(email: string): number {
  return civiApi4First<{ contact_id: number }>('Email.get', {
    where: [['email', '=', email]],
    select: ['contact_id'],
  }).contact_id;
}

export function getContactIdByName(firstName: string, lastName: string): number {
  return civiApi4First<{ id: number }>('Contact.get', {
    where: [
      ['first_name', '=', firstName],
      ['last_name', '=', lastName],
      ['is_deleted', '=', false],
    ],
    select: ['id'],
  }).id;
}

export function getEventIdByTitle(title: string): number {
  return civiApi4First<{ id: number }>('Event.get', {
    where: [['title', '=', title]],
    select: ['id'],
  }).id;
}

export function getParticipant(contactId: number, eventId: number) {
  return civiApi4First<{ id: number; status_id: number }>('Participant.get', {
    where: [
      ['contact_id', '=', contactId],
      ['event_id', '=', eventId],
    ],
    select: ['id', 'status_id'],
  });
}

export function getParticipantStatus(participantId: number): number {
  return civiApi4First<{ status_id: number }>('Participant.get', {
    where: [['id', '=', participantId]],
    select: ['status_id'],
  }).status_id;
}

/** Restore a participant's status, so a mutating test leaves the environment as it found it. */
export function setParticipantStatus(participantId: number, statusId: number): void {
  civiApi4('Participant.update', {
    where: [['id', '=', participantId]],
    values: { status_id: statusId },
  });
}

/** Participant status ids are fixed in a stock CiviCRM install. */
export const PARTICIPANT_STATUS = {
  Registered: 1,
  Attended: 2,
  NoShow: 3,
  Cancelled: 4,
} as const;
