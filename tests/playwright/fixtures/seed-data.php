<?php
/**
 * Seeds the CiviCRM records the Playwright suite asserts against.
 *
 * Run with `cv scr`, after the WordPress users exist (setup-environment.sh creates them
 * first, because each CiviCRM contact here is linked to one via UFMatch).
 *
 * Idempotent: every record is looked up by a recognisable "UXTEST" name before being
 * created, so re-running the script updates rather than duplicating. Nothing here relies
 * on incidental data that happens to be in the database.
 */

use Civi\Api4\Activity;
use Civi\Api4\Campaign;
use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\Email;
use Civi\Api4\Event;
use Civi\Api4\FinancialType;
use Civi\Api4\Membership;
use Civi\Api4\MembershipType;
use Civi\Api4\Participant;
use Civi\Api4\UFMatch;

const PREFIX = 'UXTEST';

$users = json_decode(file_get_contents(__DIR__ . '/test-users.json'), TRUE);

function out(string $msg): void {
  echo $msg . "\n";
}

/**
 * Find-or-create an Individual, link it to the WordPress user of the same username, and
 * give it a primary email.
 *
 * The UFMatch row is what makes CRM_Core_Session::getLoggedInContactID() resolve - without
 * it a logged-in WordPress user has no CiviCRM identity at all, and every "own data"
 * shortcode in this plugin renders nothing.
 */
function ensureContact(array $spec): int {
  $existing = Contact::get(FALSE)
    ->addWhere('first_name', '=', $spec['firstName'])
    ->addWhere('last_name', '=', $spec['lastName'])
    ->addWhere('is_deleted', '=', FALSE)
    ->execute()->first();

  if ($existing) {
    $contactId = (int) $existing['id'];
  }
  else {
    $contactId = (int) Contact::create(FALSE)
      ->addValue('contact_type', 'Individual')
      ->addValue('first_name', $spec['firstName'])
      ->addValue('last_name', $spec['lastName'])
      ->execute()->first()['id'];
  }

  $email = Email::get(FALSE)
    ->addWhere('contact_id', '=', $contactId)
    ->addWhere('email', '=', $spec['email'])
    ->execute()->first();

  if (!$email) {
    Email::create(FALSE)
      ->addValue('contact_id', $contactId)
      ->addValue('email', $spec['email'])
      ->addValue('is_primary', TRUE)
      ->addValue('location_type_id', 1)
      ->execute();
  }

  // Link to the WordPress user created by setup-environment.sh.
  $wpUser = get_user_by('login', $spec['username']);
  if (!$wpUser) {
    throw new RuntimeException("WordPress user {$spec['username']} does not exist - run setup-environment.sh first.");
  }

  $match = UFMatch::get(FALSE)
    ->addWhere('uf_id', '=', $wpUser->ID)
    ->execute()->first();

  if (!$match) {
    UFMatch::create(FALSE)
      ->addValue('uf_id', $wpUser->ID)
      ->addValue('uf_name', $spec['username'])
      ->addValue('contact_id', $contactId)
      ->addValue('domain_id', CRM_Core_Config::domainID())
      ->execute();
  }
  elseif ((int) $match['contact_id'] !== $contactId) {
    UFMatch::update(FALSE)
      ->addWhere('id', '=', $match['id'])
      ->addValue('contact_id', $contactId)
      ->execute();
  }

  out("  contact {$spec['firstName']} {$spec['lastName']} = $contactId (wp user {$wpUser->ID})");
  return $contactId;
}

function ensureEvent(string $title, string $start, string $end): int {
  $existing = Event::get(FALSE)->addWhere('title', '=', $title)->execute()->first();

  $values = [
    'title' => $title,
    'event_type_id' => 1,
    'start_date' => $start,
    'end_date' => $end,
    'is_public' => TRUE,
    'is_active' => TRUE,
    'is_online_registration' => TRUE,
    // CiviCRM's own event form always populates this, but the column is nullable and an
    // API-created event leaves it NULL - which [ux_event_listing] cannot render (see the
    // note in events.spec.ts). Set explicitly so the seed matches what a real site holds.
    'registration_link_text' => 'Register Now',
    'summary' => "$title summary",
    'description' => "$title description",
  ];

  if ($existing) {
    $update = Event::update(FALSE)->addWhere('id', '=', $existing['id']);
    foreach ($values as $k => $v) {
      $update->addValue($k, $v);
    }
    $update->execute();
    $id = (int) $existing['id'];
  }
  else {
    $create = Event::create(FALSE);
    foreach ($values as $k => $v) {
      $create->addValue($k, $v);
    }
    $id = (int) $create->execute()->first()['id'];
  }

  out("  event \"$title\" = $id");
  return $id;
}

function ensureParticipant(int $contactId, int $eventId, int $statusId): int {
  $existing = Participant::get(FALSE)
    ->addWhere('contact_id', '=', $contactId)
    ->addWhere('event_id', '=', $eventId)
    ->execute()->first();

  if ($existing) {
    Participant::update(FALSE)
      ->addWhere('id', '=', $existing['id'])
      ->addValue('status_id', $statusId)
      ->execute();
    $id = (int) $existing['id'];
  }
  else {
    $id = (int) Participant::create(FALSE)
      ->addValue('contact_id', $contactId)
      ->addValue('event_id', $eventId)
      ->addValue('status_id', $statusId)
      ->addValue('role_id', 1)
      ->execute()->first()['id'];
  }

  out("  participant contact=$contactId event=$eventId status=$statusId = $id");
  return $id;
}

// ---------------------------------------------------------------- contacts

out('Contacts:');
$memberCid = ensureContact($users['member']);
$otherCid = ensureContact($users['otherMember']);
$privilegedCid = ensureContact($users['privileged']);

// ---------------------------------------------------------------- events

out('Events:');
// The attendance button only renders for an event that has already ended, and only while the
// participant is still Registered - so the past event drives the attendance tests and the
// future event drives cancellation.
$pastEventId = ensureEvent(
  PREFIX . ' Past Event',
  date('Y-m-d H:i:s', strtotime('-14 days')),
  date('Y-m-d H:i:s', strtotime('-14 days +3 hours'))
);
$futureEventId = ensureEvent(
  PREFIX . ' Future Event',
  date('Y-m-d H:i:s', strtotime('+30 days')),
  date('Y-m-d H:i:s', strtotime('+30 days +3 hours'))
);
// A third event nobody is registered for. Without it the member is a participant on EVERY
// seeded event, so a my_events listing and an unfiltered listing return the same count and
// a broken contact filter would be indistinguishable from a working one (D-09).
$unattendedEventId = ensureEvent(
  PREFIX . ' Unattended Event',
  date('Y-m-d H:i:s', strtotime('+45 days')),
  date('Y-m-d H:i:s', strtotime('+45 days +3 hours'))
);

out('Participants:');
// Both members are registered on the past event: that pairing is what the ownership test in
// Suite C needs - member A attempting to mark member B's participant record.
ensureParticipant($memberCid, $pastEventId, 1);
ensureParticipant($otherCid, $pastEventId, 1);
ensureParticipant($memberCid, $futureEventId, 1);

// ---------------------------------------------------------------- membership

out('Memberships:');
$membershipType = MembershipType::get(FALSE)->addWhere('is_active', '=', TRUE)->execute()->first();

if (!$membershipType) {
  // A fresh CiviCRM has no membership types at all, so one is created here rather than
  // leaving Suite F with nothing to assert. A type needs an Organization to belong to and a
  // financial type to bill against, neither of which can be assumed either.
  $org = Contact::get(FALSE)
    ->addWhere('organization_name', '=', PREFIX . ' Member Organisation')
    ->addWhere('is_deleted', '=', FALSE)
    ->execute()->first();

  if (!$org) {
    $org = Contact::create(FALSE)
      ->addValue('contact_type', 'Organization')
      ->addValue('organization_name', PREFIX . ' Member Organisation')
      ->execute()->first();
  }

  // "Member Dues" is the stock financial type for memberships; fall back to whatever is
  // available so this does not depend on the exact set a given CiviCRM version ships.
  $financialType = FinancialType::get(FALSE)
    ->addWhere('name', '=', 'Member Dues')
    ->addWhere('is_active', '=', TRUE)
    ->execute()->first()
    ?: FinancialType::get(FALSE)->addWhere('is_active', '=', TRUE)->execute()->first();

  if (!$financialType) {
    out('  WARNING: no active financial type - cannot create a membership type.');
  }
  else {
    $membershipType = MembershipType::create(FALSE)
      ->addValue('name', PREFIX . ' Membership')
      ->addValue('member_of_contact_id', $org['id'])
      ->addValue('financial_type_id', $financialType['id'])
      ->addValue('duration_unit', 'year')
      ->addValue('duration_interval', 1)
      ->addValue('period_type', 'rolling')
      ->addValue('is_active', TRUE)
      ->execute()->first();
    out("  created membership type {$membershipType['id']} (org {$org['id']}, financial type {$financialType['id']})");
  }
}

if ($membershipType) {
  $existing = Membership::get(FALSE)
    ->addWhere('contact_id', '=', $memberCid)
    ->addWhere('membership_type_id', '=', $membershipType['id'])
    ->execute()->first();

  if (!$existing) {
    Membership::create(FALSE)
      ->addValue('contact_id', $memberCid)
      ->addValue('membership_type_id', $membershipType['id'])
      ->addValue('join_date', date('Y-m-d', strtotime('-1 year')))
      ->addValue('start_date', date('Y-m-d', strtotime('-1 year')))
      ->addValue('end_date', date('Y-m-d', strtotime('+6 months')))
      ->execute();
    out("  membership for $memberCid, type {$membershipType['id']}");
  }
  else {
    out("  membership for $memberCid already present");
  }
}
else {
  out('  WARNING: no active membership type found - Suite F will have nothing to assert.');
}

// ---------------------------------------------------------------- campaign

out('Campaigns:');

// Ids default to 0 so the summary below is still complete if this section is skipped, and
// so a spec that needs a campaign fails on its own rather than taking the rest down with it.
$campaignId = 0;
$emptyCampaignId = 0;

// CiviCampaign is a core extension that setup-environment.sh enables. If it somehow is not
// enabled, the class does not exist and the whole script would fatal here - taking the ids
// handover down with it. Campaigns are one optional suite, so this degrades instead.
if (!class_exists(Campaign::class)) {
  out('  WARNING: CiviCampaign is not enabled - skipping campaigns. Suite G will not run.');
}
else {
  $campaignTitle = PREFIX . ' Campaign';
  $campaign = Campaign::get(FALSE)->addWhere('title', '=', $campaignTitle)->execute()->first();
  if (!$campaign) {
    $campaign = Campaign::create(FALSE)
      ->addValue('title', $campaignTitle)
      ->addValue('name', strtolower(PREFIX) . '_campaign')
      ->addValue('goal_revenue', 1000)
      ->addValue('start_date', date('Y-m-d', strtotime('-30 days')))
      ->addValue('end_date', date('Y-m-d', strtotime('+30 days')))
      ->addValue('is_active', TRUE)
      ->execute()->first();
  }
  $campaignId = (int) $campaign['id'];
  out("  campaign \"$campaignTitle\" = $campaignId");

  // Two completed contributions, so the thermometer has a non-zero, predictable percentage:
  // 250 of a 1000 goal = 25%.
  $existingContribs = Contribution::get(FALSE)
    ->addWhere('campaign_id', '=', $campaignId)
    ->selectRowCount()
    ->execute()->count();

  if ($existingContribs === 0) {
    $donationType = FinancialType::get(FALSE)
      ->addWhere('name', '=', 'Donation')
      ->addWhere('is_active', '=', TRUE)
      ->execute()->first()
      ?: FinancialType::get(FALSE)->addWhere('is_active', '=', TRUE)->execute()->first();

    foreach ([100, 150] as $amount) {
      Contribution::create(FALSE)
        ->addValue('contact_id', $memberCid)
        ->addValue('financial_type_id', $donationType['id'])
        ->addValue('total_amount', $amount)
        ->addValue('receive_date', date('Y-m-d H:i:s'))
        ->addValue('contribution_status_id:name', 'Completed')
        ->addValue('campaign_id', $campaignId)
        ->execute();
    }
    out('  2 completed contributions totalling 250');
  }
  else {
    out("  $existingContribs contribution(s) already present");
  }

  // An empty campaign, to prove the thermometer does not divide by zero.
  $emptyTitle = PREFIX . ' Empty Campaign';
  $empty = Campaign::get(FALSE)->addWhere('title', '=', $emptyTitle)->execute()->first();
  if (!$empty) {
    $empty = Campaign::create(FALSE)
      ->addValue('title', $emptyTitle)
      ->addValue('name', strtolower(PREFIX) . '_empty_campaign')
      ->addValue('goal_revenue', 500)
      ->addValue('is_active', TRUE)
      ->execute()->first();
  }
  $emptyCampaignId = (int) $empty['id'];
  out("  empty campaign = $emptyCampaignId");
}

// ---------------------------------------------------------------- activity

out('Activities:');
$activitySubject = PREFIX . ' Activity';
// One optional record for Suite I. Caught rather than fatal for the same reason as the
// campaign block: a missing activity type must not cost the whole suite its ids file.
try {
  $activity = Activity::get(FALSE)->addWhere('subject', '=', $activitySubject)->execute()->first();
  if (!$activity) {
    Activity::create(FALSE)
      ->addValue('activity_type_id:name', 'Meeting')
      ->addValue('subject', $activitySubject)
      ->addValue('source_contact_id', $memberCid)
      ->addValue('target_contact_id', [$memberCid])
      ->addValue('status_id:name', 'Completed')
      ->addValue('activity_date_time', date('Y-m-d H:i:s', strtotime('-7 days')))
      ->execute();
    out("  activity \"$activitySubject\" created");
  }
  else {
    out("  activity \"$activitySubject\" already present");
  }
}
catch (Throwable $e) {
  out('  WARNING: could not seed the activity - ' . $e->getMessage());
}

// ---------------------------------------------------------------- summary

// Printed for the CI log only. The ids are deliberately NOT written to a file: this script
// runs as www-data, while the repository is bind-mounted from the runner's checkout and owned
// by the runner user, so www-data cannot write into it. Everything that needs these ids reads
// them back from CiviCRM instead, looking the records up by their UXTEST names - the database
// is the one source of truth both the container and the runner can already reach.
out('Seeded:');
out('  ' . json_encode([
  'memberContactId' => $memberCid,
  'otherContactId' => $otherCid,
  'privilegedContactId' => $privilegedCid,
  'pastEventId' => $pastEventId,
  'futureEventId' => $futureEventId,
  'unattendedEventId' => $unattendedEventId,
  'campaignId' => $campaignId,
  'emptyCampaignId' => $emptyCampaignId,
]));
