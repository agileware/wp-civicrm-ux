#!/usr/bin/env bash
# Creates the WordPress roles/users, CiviCRM Form Processors, seed data and shortcode host
# pages that the Playwright suite needs.
#
# Assumes WordPress + CiviCRM are installed and this plugin is active. Runs with `wp`, `cv`
# and `php` on the PATH - true inside the CI WordPress container, and under `ddev exec`
# locally. Set CIVI_EXEC_PREFIX to wrap each call if running from outside that environment.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "$SCRIPT_DIR/../../.." && pwd)"
EXEC_PREFIX=${CIVI_EXEC_PREFIX:-}

run() {
  $EXEC_PREFIX "$@"
}

json_get() {
  # $1 = json file, $2 = top-level key, $3 = nested key
  run php -r 'echo json_decode(file_get_contents($argv[1]), true)[$argv[2]][$argv[3]];' "$1" "$2" "$3"
}

USERS_JSON="$SCRIPT_DIR/test-users.json"

# ---------------------------------------------------------------- roles

echo "Creating WordPress roles..."
# CRM_Core_Permission_WordPress::check() lowercases the CiviCRM permission string and replaces
# each run of non-alphanumeric characters with a single underscore (CRM_Utils_String::munge)
# before calling current_user_can(). The capability granted here must therefore be the munged
# form - "register for events" becomes register_for_events - or the check always misses.
#
# ux_member is the ordinary site member: enough to see CiviCRM, register for events and read
# their own record, but NOT to view other contacts. ux_privileged adds view_all_contacts,
# which is what the permission-gated shortcodes ([ux_contact_value] on another contact, and
# [ux_cv_api4_get entity=Contact]) actually test against.
run wp role create ux_member "UX Member (test)" --clone=subscriber >/dev/null 2>&1 || true
run wp cap add ux_member \
  access_civicrm \
  access_civievent \
  view_event_info \
  register_for_events \
  profile_view \
  access_all_custom_data

run wp role create ux_privileged "UX Privileged (test)" --clone=subscriber >/dev/null 2>&1 || true
run wp cap add ux_privileged \
  access_civicrm \
  access_civievent \
  view_event_info \
  register_for_events \
  profile_view \
  access_all_custom_data \
  view_all_contacts \
  edit_all_contacts

# ---------------------------------------------------------------- users

create_or_update_user() {
  local key="$1"
  local username password email role
  username=$(json_get "$USERS_JSON" "$key" username)
  password=$(json_get "$USERS_JSON" "$key" password)
  email=$(json_get "$USERS_JSON" "$key" email)
  role=$(json_get "$USERS_JSON" "$key" role)

  if run wp user get "$username" >/dev/null 2>&1; then
    run wp user update "$username" --user_pass="$password" --role="$role"
  else
    run wp user create "$username" "$email" --role="$role" --user_pass="$password"
  fi
}

echo "Creating WordPress users..."
create_or_update_user member
create_or_update_user otherMember
create_or_update_user privileged

# ---------------------------------------------------------------- form processors

echo "Setting a named site timezone..."
# A fresh WordPress leaves timezone_string empty and uses gmt_offset instead. Most real sites
# pick a city, so the suite matches that - and the iCal feeds only produce a VTIMEZONE
# component when a named zone is set. The empty case is covered separately by C-10b, which is
# the configuration that used to make the feeds return a 500.
run wp option update timezone_string 'Australia/Melbourne'

echo "Enabling the CiviCRM components the suite depends on..."
# CiviCRM's components are core extensions, and a fresh `cv core:install` enables only some
# of them. civi_campaign in particular is NOT enabled by default, and without it the class
# \Civi\Api4\Campaign does not exist at all - seeding fails with "Class not found" rather
# than an empty result. The others are normally on already; ext:enable is a no-op when they
# are, so listing them keeps the suite's dependencies explicit rather than assumed.
run cv ext:enable civi_event civi_member civi_contribute civi_campaign

echo "Enabling the Form Processor extension..."
# form-processor ships bundled with CiviCRM core (civicrm/ext/org.civicoop.form-processor),
# so there is nothing to download - it only needs enabling.
run cv ext:enable form-processor

echo "Importing the plugin's Form Processor definitions..."
# The attendance and cancellation features call FormProcessor.mark_event_attendance and
# FormProcessor.cancel_event_registration. The plugin does not define these in code - they
# are CiviCRM configuration, shipped with the plugin as importable exports under data/.
# Without them, those shortcodes render nothing and the REST endpoints return a 500.
run cv api3 FormProcessorInstance.Import \
  file="$PLUGIN_DIR/data/mark_event_attendance.json" import_locally=1
run cv api3 FormProcessorInstance.Import \
  file="$PLUGIN_DIR/data/cancel_event_registration.json" import_locally=1

# ---------------------------------------------------------------- civicrm seed data

echo "Seeding CiviCRM test data..."
run cv scr "$SCRIPT_DIR/seed-data.php"

# ---------------------------------------------------------------- shortcode host pages

echo "Creating shortcode host pages..."
# Each page hosts the shortcodes for one suite. Created with --porcelain suppressed because
# re-running the script must be idempotent: an existing slug is left alone.
create_page() {
  local slug="$1" title="$2" content="$3"
  if run wp post list --post_type=page --name="$slug" --field=ID | grep -q .; then
    local id
    id=$(run wp post list --post_type=page --name="$slug" --field=ID | head -n1 | tr -d '\r')
    run wp post update "$id" --post_content="$content" --post_status=publish
  else
    run wp post create \
      --post_type=page \
      --post_title="$title" \
      --post_name="$slug" \
      --post_status=publish \
      --post_content="$content"
  fi
}

create_page ux-test-event-listing 'UX Test Event Listing' \
  '[ux_event_listing limit="20"]'

create_page ux-test-event-calendar 'UX Test Event Calendar' \
  '[ux_event_fullcalendar]'

create_page ux-test-event-ical-feed 'UX Test Event iCal Feed' \
  '[ux_event_ical_feed]'

# [ux_event_markattendance] emits the wp_rest nonce and loads the JS; the button renders the
# control itself. Both are needed for the REST tests to authenticate the way the plugin does.
create_page ux-test-mark-attendance 'UX Test Mark Attendance' \
  '[ux_event_markattendance][ux_event_markattendance_button eventid="__PAST_EVENT_ID__"]'

create_page ux-test-cancel-registration 'UX Test Cancel Registration' \
  '[ux_event_cancelregistration][ux_event_cancelregistration_button eventid="__FUTURE_EVENT_ID__"]'

create_page ux-test-api4-contact 'UX Test API4 Contact' \
  '[ux_cv_api4_get entity="Contact" id="__OTHER_CONTACT_ID__"]NAME=[api4:display_name] EMAIL=[api4:email.email][/ux_cv_api4_get]'

create_page ux-test-api4-event 'UX Test API4 Event' \
  '[ux_cv_api4_get entity="Event" limit="5"]EVENT=[api4:title]|[/ux_cv_api4_get]'

create_page ux-test-api4-my-events 'UX Test API4 My Events' \
  '[ux_cv_api4_get entity="Event" my_events="1" limit="50"]MYEVENT=[api4:id]|[/ux_cv_api4_get]'

create_page ux-test-contact-value 'UX Test Contact Value' \
  'OWN=[ux_contact_value field="display_name"] OTHER_DEFAULT=[ux_contact_value id="__OTHER_CONTACT_ID__" field="display_name"] OTHER_EMPTY=[ux_contact_value id="__OTHER_CONTACT_ID__" field="display_name" permission=""]'

create_page ux-test-membership 'UX Test Membership' \
  'STATUS=[ux_membership_status] TYPE=[ux_membership_type] ID=[ux_membership_id] EXPIRY=[ux_membership_expiry]'

create_page ux-test-campaign 'UX Test Campaign' \
  'GOAL=[ux_campaign_goal_amount id="__CAMPAIGN_ID__"] RAISED=[ux_campaign_total_raised id="__CAMPAIGN_ID__"]'

create_page ux-test-self-serve-checksum 'UX Test Self Serve Checksum' \
  '[ux_self_serve_checksum]UX_PROTECTED_CONTENT[/ux_self_serve_checksum]'

create_page ux-test-utility 'UX Test Utility' \
  'DATE=[ux_convert_date timezone="Australia/Melbourne" return_format="Y-m-d"] GDPR=[ux_gdpr_url]'

create_page ux-test-activity-listing 'UX Test Activity Listing' \
  '[ux_activity_listing]'

# The placeholders above are substituted with the real seeded ids, which only exist once
# seed-data.php has run. Doing it here keeps the page content declarative and in one place.
echo "Substituting seeded ids into page content..."
run cv scr "$SCRIPT_DIR/resolve-page-placeholders.php"

echo "Flushing caches so the first test run starts cold..."
run wp transient delete --all >/dev/null 2>&1 || true

echo "Test environment ready."
