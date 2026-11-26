# Membership Shortcodes

1. `[ux_membership]`
Outputs a table for memberships information. Designed to wrap around `[ux_membership_row]` shortcodes to build a summary of memberships data for a given user. Override the shortcode-membership template part in your theme to customise.

1. `[ux_membership_row]`
Outputs a table row of membership data. Checks for the logged in contact's cid, or cid and checksum in the URL parameters. If multiple memberships are found for the contact based on the filters provided, each membership will output a row. Override the shortcode-membership-row template part in your theme to customise. Uses the following parameters:
- *`membership_type`* Optional: A comma separated list of membership type IDs to filter by.
- *`membership_status`* Optional: A comma separated list of membership status names to filter by.
- *`expiration_offset`* Optional: A relative date format string to filter membership end_date by. For example, if set to "30 days", will filter memberships by those whose `end_date` is before today + 30 days.
- *`renewal_url`* Optional: A URL to a page the user should be directed to in order to process their membership renewal. This URL will have the contact's cid, maybe their checksum if they are not logged in, and the membership id appended to it. This allows you to define separate membership renewal forms based on the other filter criteria above.

```
[ux_membership_row membership_type="1,5" membership_status="Current,Grace,Expired" expiration_offset="30 days" renewal_url="/civicrm/contribute/transact/?reset=1&id=1"]
```

## Use template parts to override output

Membership Shortcodes support the use of [Template Parts](https://developer.wordpress.org/themes/templates/template-parts/) to override the output, so you can customise, style and precisely control how the information is shown on your website.

The following Template Parts are supported:

- membership
- membership-row
- membership-no-results
- membership-no-access

To override the output of the shortcode when there are no membership records to display, create a Template Part file by:

1. Copy the template file: ```wp-civicrm-ux/templates/shortcode/shortcode-membership-no-results.php```
2. Create the new file: ```wp-content/themes/theme-name/template-parts/shortcode/shortcode-membership-no-results.php```
3. Customise as required

## Renewing a membership inherited by relationship

When linking to a CiviCRM Contribution Page to renew a membership inherited by relationship, it is important to be aware that the contact must have a permissioned relationship with the related contact. This enables the contact to renew the membership on behalf of the primary member, typically an organisation. If there is no permissioned relationship then a new organisation will be entered and the unsupervised duplicate matching rules for organisations will be used. A new membership instead of a membership renewal may also be created. [See the CiviCRM documentation for more details](https://docs.civicrm.org/user/en/latest/membership/defining-memberships)

## Other shortcodes

These shortcodes below show **the most recent** membership only, and which one they show follows the rules below, so read them before relying on these shortcodes.
### Which membership is shown

- **Logged-in users only.** The shortcodes use the contact of the logged-in user. They do not accept a `cid` and checksum link, and they show nothing to a visitor who is not logged in (`[ux_membership_id]` shows `not found`).
- **The most recent membership is the one with the latest end date.** The type, status, expiry date, renewal date, summary and ID all come from that one membership, whichever order the memberships were created in.
  - If two memberships end on the same date, the one with a current status wins, so a cancelled or pending application for the same period does not hide the member's active membership. If they still tie, the one with the higher membership ID wins.
  - A membership with no end date counts as the most recent if its status is current (for example a lifetime membership, which never ends). A membership with no end date and no current status (for example a pending or cancelled application that never started) counts as the oldest.
- **The status, expiry and summary are only shown when the most recent membership has a current status.** A status is current when *Current Member?* is ticked on it in CiviCRM. In a default CiviCRM, New, Current and Grace are current; Pending, Expired, Cancelled and Deceased are not. When the most recent membership is not current, they are **empty** even if the member has an older membership that is current. The type and the renewal date are shown for any status.
- **If the contact has no membership at all**, nothing is shown.

| Situation | Type | Status | Expiry | Renewal date | Summary |
|---|---|---|---|---|---|
| The most recent membership has a current status | Shown | Shown | Shown | Shown | Shown |
| The most recent membership is Expired, Cancelled or any other non-current status | Shown | Empty | Empty | Shown (a date in the past) | Empty |
| A lifetime membership (current, with no end date) | Shown | Shown | Empty (it never ends) | Empty | Shown, without a date |
| No memberships, or not logged in | Empty | Empty | Empty | Empty | Empty |

If you need to show every membership with its real status and expiry date, whatever the status is, use `[ux_membership]` with `[ux_membership_row]` instead.

### The shortcodes

1. `[ux_membership_expiry]` - Return a HTML tag with the sentence "Your membership expires on *date*", using the date format configured in CiviCRM. Empty unless the most recent membership has a current status and an end date. It never shows an expiry date for an ended membership.
2. `[ux_membership_id]` - Return the ID of the most recent membership, which is the same membership the other shortcodes describe. Shows `not found` if there is no membership or the visitor is not logged in.
3. `[ux_membership_join_url]` - Return the join form URL as plain text (not a link), as set in *Settings > CiviCRM UX*, under *Membership Summary*, in *URL for the Membership Join page*. It does not depend on the contact's memberships.
4. `[ux_membership_renewal_date]` - Return the renewal date: the membership's end date minus the number of days set in *The number of days to show the renewal notice and URL*, in the same settings area. It is calculated from the most recent membership, so if that membership is not current it shows a date in the past. It is empty for a membership with no end date.
5. `[ux_membership_renewal_url]` - Return the renewal form URL as plain text (not a link), as set in *Settings > CiviCRM UX*, in *URL for the Membership Renewal page*. It does not depend on the contact's memberships.
6. `[ux_membership_status]` - Return the label of the membership status. Empty unless the most recent membership has a current status, so it is empty for Expired and Cancelled memberships.
7. `[ux_membership_summary]` - Return a sentence summarising the membership. Empty unless the most recent membership has a current status. The wording depends on the date:
   - for a membership with no end date: "*type* member - *status*"
   - before the renewal notice period starts: "*type* member - *status*, next renewal date *date*"
   - during the renewal notice period (from the renewal date until the end date): "*type* member - *status*. Your membership expires on *date*", followed by a "Click here to renew your membership" link to the renewal URL
   - after the end date, while the status is still current (for example Grace): "*type* member - *status*.*end date*", followed by the same renewal link
8. `[ux_membership_type]` - Return the name of the type of the most recent membership. This is the type's name, which can differ from the label shown in `[ux_membership_row]`. It is shown for any status, including Expired and Cancelled.
