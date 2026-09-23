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

out('Participants:');
// Both members are registered on the past event: that pairing is what the ownership test in
// Suite C needs - member A attempting to mark member B's participant record.
ensureParticipant($memberCid, $pastEventId, 1);
ensureParticipant($otherCid, $pastEventId, 1);
ensureParticipant($memberCid, $futureEventId, 1);

// ---------------------------------------------------------------- membership

out('Memberships:');
$membershipType = MembershipType::get(FALSE)->addWhere('is_active', '=', TRUE)->execute()->first();
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
  foreach ([100, 150] as $amount) {
    Contribution::create(FALSE)
      ->addValue('contact_id', $memberCid)
      ->addValue('financial_type_id', 1)
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
out("  empty campaign = {$empty['id']}");

// ---------------------------------------------------------------- activity

out('Activities:');
$activitySubject = PREFIX . ' Activity';
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

// ---------------------------------------------------------------- handover

// Written where the placeholder resolver and the specs can both read it, so neither has to
// re-derive ids that this script already knows.
$ids = [
  'memberContactId' => $memberCid,
  'otherContactId' => $otherCid,
  'privilegedContactId' => $privilegedCid,
  'pastEventId' => $pastEventId,
  'futureEventId' => $futureEventId,
  'campaignId' => $campaignId,
  'emptyCampaignId' => (int) $empty['id'],
];

file_put_contents(__DIR__ . '/seeded-ids.json', json_encode($ids, JSON_PRETTY_PRINT) . "\n");
out('Wrote seeded-ids.json:');
out('  ' . json_encode($ids));
