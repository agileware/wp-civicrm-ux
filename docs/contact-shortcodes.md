# Contact, activity and GDPR shortcodes

## Activity Shortcode

`[ux_activity_listing]`  
Parameters:
 - `$type`: activity type id or activity type label. Support multiple values separated by a comma without space. The default is empty.
 - `$limit`: the maximum number for the activity result. The default is 0 (unlimited).
 - `$relationship-id`: the activities of contacts which have relationships will be added into the result.
 - `$field`: the fields to return. Separated by a comma without space. The default returns contact names and activity subjects. 
 The order of the output fields will follow the order of the input fields.
 - `$format`: the display format. For example, `table` for a tabular layout
 - `$sort`: sorting field names in ascending or descending order with `ASC` or `DESC`. The default is `activity_date_time DESC`.
 
For example:  
`[ux_activity_listing type='Test' relationship-id=5 field='status,source_contact_name,activity_date_time']`

## Contact Shortcode

`[ux_contact_value]`
Parameters:
 - `id`             the contact id. Default is the current login user
 - `permission`     what permissions to check. Separated by comma. Default is 'View All Contacts'
 - `id_from_url`    if given any name, the shortcode will get contact id from url with the given name
 - `field`          what field to display **required**
 - `default`        the value to display if empty

For example: to display the External ID of a Contact, use this shortcode
`[ux_contact_value field="external_identifier"]`

## GDPR Communication Preferences Shortcode

`[ux_gdpr_url]` outputs the URL to the CiviCRM communication preferences page for the logged in contact, so you can link to it from your own pages (e.g. a "manage my email preferences" link).

This shortcode requires the [CiviCRM GDPR](https://github.com/veda-consulting/uk.co.vedaconsulting.gdpr) CiviCRM extension to be installed. If the extension is not installed, or no contact is logged in, `#` is returned instead.

Example usage:

`[ux_custom_button text="Manage my email preferences" url={{api4:...}}]`, or link to it directly, e.g. `<a href="[ux_gdpr_url]">Manage my email preferences</a>`
