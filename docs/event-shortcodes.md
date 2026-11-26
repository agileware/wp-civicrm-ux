# Event shortcodes

## Event iCal Feed Shortcode

Event iCal Feed Shortcode `[ux_event_ical_feed]` generates a custom iCal Calendar and returns a URL to download the iCal file.

- *`type`* optional parameter to filter Events by Event Type (using Event Type Label). For example: `[ux_event_ical_feed type="Meeting,Exhibition"]Click here to download an iCal feed of meeting and exhibitions[/ical-feed]`. If `type` is not specified, then will include all Event Types.

### Direct feed URL

The shortcode links to a hash-protected feed at `/wp-json/ICalFeed/manage?hash=...&type=...`, which only works with a valid hash. There is also a public feed of all events (no hash required) at `/wp-json/ICalFeed/event`, which also accepts an optional `type` parameter, e.g. `https://example.com/wp-json/ICalFeed/event?type=Meeting,Exhibition`.

- Subscribing to either feed with Google Calendar may be affected by Google's [long refresh period](https://webapps.stackexchange.com/a/6315) for external calendars.

## Event Listing Shortcode

This shortcode will display upcoming CiviCRM events which have a start date of today or a future date. `[ux_event_listing days=5 type="Training"]`  
The event listing displays the start date, the end date, the event name, the registration link, the brief description of event and the link for more information. This shortcode has been formatted in html with styling.
- *`type`* optional parameter to filter Events by Event Type (using Event Type Label). If `type` is not specified, then will include all Event Types.
- *`days`* optional parameter to limit the upcoming events to only those with a event start date in the X number of days.

## Event Cancel Registration Shortcodes

These shortcodes enable users to cancel their registration for an event. This requires the [Form Processor](https://lab.civicrm.org/extensions/form-processor) CiviCRM extension to handle the participant status update. You can then handle associated Contributions, trigger an email to be sent, and generate Activities using the Form Processor. It is recommended to import the one provided in the [data directory](https://github.com/agileware/wp-civicrm-ux/tree/master/data) of this plugin as a starting point.

The following shortcodes *must* be used together:

- *`[ux_event_cancelregistration]`* provides a WordPress nonce for authentication
- *`[ux_event_cancelregistration_button text="My Button Text" eventid={{api4:id}}]`* outputs a button with the given text for the given event id. It also outputs a confirmation modal dialog for the given event id. You could provide the id, for example, via `[ux_cv_api4_get]` as shown here, which is perfect for generated listings, or hardcode it in for specific event pages. It will not render for events the current logged in user does not have an active registration for.

## Event Mark Attendance Shortcodes

These shortcodes provide a way to optionally allow users to mark their own attendance for an event. This requires the [Form Processor](https://lab.civicrm.org/extensions/form-processor) CiviCRM extension to handle the participant status update. It is recommended to import the one provided in the [data directory](https://github.com/agileware/wp-civicrm-ux/tree/master/data) of this plugin as a starting point.

The following shortcodes *must* be used together:

- *`[ux_event_markattendance]`* provides a WordPress nonce for authentication
- *`[ux_event_markattendance_button text="My Button Text" eventid={{api4:id}} attended_status={{status_id}} not_attended_status={{status_id}}]`* outputs a button with the given text for the given event id. It also outputs a confirmation modal dialog for the given event id. You could provide the id, for example, via `[ux_cv_api4_get]` as shown here, which is perfect for generated listings, or hardcode it in for specific event pages. You can customise which status (by ID) to set when the user has selected they have attended using `attended_status`, and which to set when they have not attended using `not_attended_status`. The button will not render for events the current logged in user does not have an active registration for, and has not passed.

## Event Complete Evaluation Shortcode

`[ux_event_completeevaluation_button]` outputs a button linking to an event evaluation form, for use in listings or on specific event pages. The button is only rendered once the event has finished, and only for the logged in user's own participant record for that event.

- *`text`* the button text. Defaults to "Complete Evaluation".
- *`event_id`* the CiviCRM Event ID. You could provide this, for example, via `[ux_cv_api4_get]`.
- *`participant_status`* the participant status ID the logged in user must currently have for the event in order for the button to render, e.g. an "Attended" status.
- *`url`* the target URL for the evaluation form. You can populate this dynamically, for example by using `[ux_cv_api4_get]`.
- *`fallback_url`* optional. Used instead of `url` if `url` is empty.
- *`css_classes`* optionally include additional css classes.
- *`icon_classes`* optionally override the default icon css classes (`fa fa-pencil-square-o`).

The Participant ID, Event ID and Event Title are automatically appended as `pid`, `eid` and `event_title` query parameters to the target URL.

Example usage:

`[ux_event_completeevaluation_button event_id={{api4:id}} participant_status=2 url={{api4:Evaluation_survey.Evaluation_link}} fallback_url="/event-evaluation-survey/"]`

## Custom Button Shortcode

`[ux_custom_button]` Outputs a button to a given link. Can be used to add a button that is shown conditionally, as long as the link is available, or if a fallback exists. This can be useful for directing users to a specific page, e.g. a survey form for a particular event, or a fallback page if one doesn't exist. This is useful in listings, or on specific event pages.

Example usage:

`[ux_custom_button text="Complete Evaluation" url={{api4:Evaluation_survey.Evaluation_link}} fallback_url="/event-evaluation-survey/" url_params="?pid={{api4:participant.id}}&eid={{api4:id}}&event_title={{api4:title}}" css_classes="my-class"]`

- *`text`* Defines the button text.
- *`url`* The target URL. You can populate this dynamically, for example by using `[ux_cv_api4_get]`.
- *`fallback_url`* Optional. A fallback in case the URL is null. This can also be populated dynamically.
- *`url_params`* Optionally declare parameters as a string to append to either the URL or the Fallback URL.
- *`css_classes`* Optionally include additional css classes.


### Troubleshooting

- Ensure the REST API link tag from `rest_output_link_wp_head()` is correctly generated. If you cannot find it in the page source code, it may be being stripped by another plugin.
- After canceling a registration from a listing created using `[ux_cv_api4_get]`, the page will reload. If the changes are not reflected in your event listing, the results of the API call may have been cached. See [CiviCRM API caching](api4-get.md#civicrm-api-caching) for advice.
