<?php
/**
 * Substitutes the seeded CiviCRM ids into the shortcode host pages.
 *
 * setup-environment.sh creates those pages with readable placeholders (__PAST_EVENT_ID__ and
 * friends) because the shortcode content is easier to review that way than as a shell string
 * full of command substitutions. The real ids only exist once seed-data.php has run, so this
 * runs afterwards and rewrites them in place.
 *
 * Run with `cv scr`, which bootstraps both CiviCRM and WordPress.
 */

$idsFile = __DIR__ . '/seeded-ids.json';
if (!file_exists($idsFile)) {
  throw new RuntimeException("$idsFile is missing - run seed-data.php first.");
}

$ids = json_decode(file_get_contents($idsFile), TRUE);

$map = [
  '__PAST_EVENT_ID__' => $ids['pastEventId'],
  '__FUTURE_EVENT_ID__' => $ids['futureEventId'],
  '__OTHER_CONTACT_ID__' => $ids['otherContactId'],
  '__MEMBER_CONTACT_ID__' => $ids['memberContactId'],
  '__CAMPAIGN_ID__' => $ids['campaignId'],
  '__EMPTY_CAMPAIGN_ID__' => $ids['emptyCampaignId'],
];

$pages = get_posts([
  'post_type' => 'page',
  'post_status' => 'publish',
  'numberposts' => -1,
  's' => 'ux-test',
  'name' => '',
]);

// get_posts()'s 's' search is a loose match, so filter on the slug prefix these pages share.
$updated = 0;
foreach ($pages as $page) {
  if (strpos($page->post_name, 'ux-test-') !== 0) {
    continue;
  }

  $content = $page->post_content;
  $replaced = str_replace(array_keys($map), array_values($map), $content);

  if ($replaced !== $content) {
    wp_update_post([
      'ID' => $page->ID,
      'post_content' => $replaced,
    ]);
    echo "  resolved placeholders in {$page->post_name}\n";
    $updated++;
  }
}

echo "Resolved placeholders in $updated page(s).\n";

// A page revision keeps the pre-substitution content, and [ux_cv_api4_get] folds the current
// revision id into its transient key - so any cache warmed before this point is unreachable
// now. Clearing anyway keeps the first test run's behaviour obvious rather than incidental.
if (function_exists('delete_expired_transients')) {
  delete_expired_transients(TRUE);
}
