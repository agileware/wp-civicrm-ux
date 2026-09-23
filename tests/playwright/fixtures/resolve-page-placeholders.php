<?php
/**
 * Substitutes the seeded CiviCRM ids into the shortcode host pages.
 *
 * setup-environment.sh creates those pages with readable placeholders (__PAST_EVENT_ID__ and
 * friends) because the shortcode content is easier to review that way than as a shell string
 * full of command substitutions. The real ids only exist once seed-data.php has run, so this
 * runs afterwards and rewrites them in place.
 *
 * Ids are looked up from CiviCRM by the UXTEST names seed-data.php used, rather than handed
 * over in a file: this runs as www-data and the repository is owned by the runner user, so
 * nothing here can write to disk.
 *
 * Run with `cv scr`, which bootstraps both CiviCRM and WordPress.
 */

use Civi\Api4\Campaign;
use Civi\Api4\Contact;
use Civi\Api4\Event;

const PREFIX = 'UXTEST';

function firstId(string $entity, array $where): int {
  $row = civicrm_api4($entity, 'get', [
    'checkPermissions' => FALSE,
    'where' => $where,
    'select' => ['id'],
    'limit' => 1,
  ])->first();

  return $row ? (int) $row['id'] : 0;
}

function contactIdByName(string $first, string $last): int {
  return firstId('Contact', [
    ['first_name', '=', $first],
    ['last_name', '=', $last],
    ['is_deleted', '=', FALSE],
  ]);
}

$map = [
  '__PAST_EVENT_ID__' => firstId('Event', [['title', '=', PREFIX . ' Past Event']]),
  '__FUTURE_EVENT_ID__' => firstId('Event', [['title', '=', PREFIX . ' Future Event']]),
  '__MEMBER_CONTACT_ID__' => contactIdByName('Uxtest', 'Member'),
  '__OTHER_CONTACT_ID__' => contactIdByName('Uxtest', 'Othermember'),
  // Campaigns are optional - seed-data.php skips them when CiviCampaign is unavailable, and
  // a 0 here simply means the campaign suite has nothing to point at.
  '__CAMPAIGN_ID__' => class_exists(Campaign::class)
    ? firstId('Campaign', [['title', '=', PREFIX . ' Campaign']]) : 0,
  '__EMPTY_CAMPAIGN_ID__' => class_exists(Campaign::class)
    ? firstId('Campaign', [['title', '=', PREFIX . ' Empty Campaign']]) : 0,
];

foreach ($map as $placeholder => $id) {
  if ($id === 0 && !str_contains($placeholder, 'CAMPAIGN')) {
    throw new RuntimeException("Could not resolve $placeholder - did seed-data.php run?");
  }
  echo "  $placeholder = $id\n";
}

$pages = get_posts([
  'post_type' => 'page',
  'post_status' => 'publish',
  'numberposts' => -1,
]);

$updated = 0;
foreach ($pages as $page) {
  if (!str_starts_with($page->post_name, 'ux-test-')) {
    continue;
  }

  $replaced = str_replace(array_keys($map), array_values($map), $page->post_content);

  if ($replaced !== $page->post_content) {
    wp_update_post([
      'ID' => $page->ID,
      'post_content' => $replaced,
    ]);
    echo "  resolved placeholders in {$page->post_name}\n";
    $updated++;
  }
}

echo "Resolved placeholders in $updated page(s).\n";
