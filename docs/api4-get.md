# CiviCRM APIv4 Shortcode

The CiviCRM APIv4 is directly accessible using the `[ux_cv_api4_get]` WordPress shortcode. You can use the CiviCRM APIv4 Explorer to understand how to query CiviCRM using the APIv4.

This shortcode only queries the **Contact** and **Event** entities. These are the two it is designed and documented for, and the two it can show safely. Other entities are not available: a query for any other entity (for example `entity=Contribution`) renders nothing. A site that genuinely needs another entity can add it with the `ux_cv_api4_get/allowed_entities` filter, but should do so only for an entity it is sure is safe to display, having read the Permissions note below.

Below are some examples of how the `[ux_cv_api4_get]` WordPress shortcode can be used.

## Event Listing Example 1

Example code to list single Event Type in ascending order.

The following example code can be inserted into a WordPress page when using the Code Editor mode. This code demonstrates:
* How to apply styles and layout to the listing.
* How to output an CiviCRM Custom Field for an Event. In this example, the field is Event_Extra.Event_Feature_Image which is used to record and display a featured image for the Event.

```
<!-- wp:shortcode -->
[ux_cv_api4_get entity=Event is_public=1 is_active=1 event_type_id=15 start_date=&gt;=:today sort=start_date:ASC]
<!-- /wp:shortcode -->

<!-- wp:heading {"level":3,"className":"ui-widget-header event-title"} -->
<h3 class="ui-widget-header event-title"><a href="https://example.org.au/event-registration/?id={{api4:id}}" target="_blank" rel="noreferrer noopener nofollow">[api4:title]</a></h3>
<!-- /wp:heading -->

<!-- wp:group {"className":"event-info-group"} -->
<div class="wp-block-group event-info-group"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[api4:Event_Extra.Event_Feature_Image:img]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Date:</strong> [api4:start_date:%B %E, %Y]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:shortcode -->
[api4:summary]
<!-- /wp:shortcode --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:buttons {"contentJustification":"left"} -->
<div class="wp-block-buttons is-content-justification-left"><!-- wp:button {"className":"is-style-outline","fontSize":"normal"} -->
<div class="wp-block-button has-custom-font-size is-style-outline has-normal-font-size"><a class="wp-block-button__link" href="https://example.org.au/event-registration/?id={{api4:id}}" target="_blank" rel="noreferrer noopener">Register</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:shortcode -->
[/ux_cv_api4_get]
<!-- /wp:shortcode -->
```

## Event Listing Example 2

Example code to list multiple Event Types. Same as above, except changes the event_type_id parameter: `event_type_id=IN:18,19`

The following example code can be inserted into a WordPress page when using the Code Editor mode. This code demonstrates how to apply styles and layout to the listing.

```
<!-- wp:shortcode -->
[ux_cv_api4_get entity=Event is_public=1 is_active=1 event_type_id=IN:18,19 start_date=&gt;=:today sort=start_date:ASC]
<!-- /wp:shortcode -->

<!-- wp:heading {"level":3,"className":"ui-widget-header event-title"} -->
<h3 class="ui-widget-header event-title"><a href="https://example.org.au/event-registration/?id={{api4:id}}" target="_blank" rel="noreferrer noopener nofollow">[api4:title]</a></h3>
<!-- /wp:heading -->

<!-- wp:group {"className":"event-info-group"} -->
<div class="wp-block-group event-info-group"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[api4:Event_Extra.Event_Feature_Image:img]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Date:</strong> [api4:start_date:%B %E, %Y]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:shortcode -->
[api4:summary]
<!-- /wp:shortcode --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:buttons {"contentJustification":"left"} -->
<div class="wp-block-buttons is-content-justification-left"><!-- wp:button {"className":"is-style-outline","fontSize":"normal"} -->
<div class="wp-block-button has-custom-font-size is-style-outline has-normal-font-size"><a class="wp-block-button__link" href="https://example.org.au/event-registration/?id={{api4:id}}" target="_blank" rel="noreferrer noopener">Register</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:shortcode -->
[/ux_cv_api4_get]
<!-- /wp:shortcode -->
```

## Event Information / Event Registration Page Example

Example code to display Event Information and the Event Registration form. This page will use the Event ID as passed in via the URL to load the related Event Information.

The Event in this example has CiviCRM Custom Fields as follows:
1. Event Feature Image
2. Presenter Heading
3. Presenter Information
4. Presenter Image

The standard CiviCRM Event Registration form is shown at the bottom of the page.

The following example code can be inserted into a WordPress page when using the Code Editor mode. This code demonstrates how to apply styles and layout to the listing.

```
<!-- wp:group {"className":"clearfix"} -->
<div class="wp-block-group clearfix"><!-- wp:shortcode -->
[ux_cv_api4_get entity=Event id=id]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[api4:Event_Extra.Event_Feature_Image:img]
<!-- /wp:shortcode -->

<!-- wp:paragraph -->
<p>[api4:summary]</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[api4:description]
<!-- /wp:shortcode -->

<!-- wp:heading {"level":3} -->
<h3>[api4:Presenter.Presenter_Heading]</h3>
<!-- /wp:heading -->

<!-- wp:group {"className":"alignright"} -->
<div class="wp-block-group alignright"><!-- wp:shortcode -->
[api4:Presenter.Presenter_Image:img]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:shortcode -->
[api4:Presenter.Presenter_Information]
<!-- /wp:shortcode -->

<!-- wp:list {"className":"clear-float"} -->
<ul class="clear-float"><li><strong>Starts:</strong> [api4:start_date]</li><li><strong>Finishes:</strong> [api4:end_date]</li></ul>
<!-- /wp:list -->

<!-- wp:shortcode -->
[/ux_cv_api4_get]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->

<!-- wp:heading {"level":3} -->
<h3>Registration Details</h3>
<!-- /wp:heading -->

<!-- wp:group {"className":"crm-hide-title"} -->
<div class="wp-block-group crm-hide-title"><!-- wp:shortcode -->
[civicrm component="event" action=register set_title=1]
<!-- /wp:shortcode --></div>
<!-- /wp:group -->
```

## Additional usage information

Implicit joins using Api4 are supported, e.g. `[api4:loc_block_id.address_id.street_address]`

Multi-value fields are output as a comma separated list where possible.

Limited format support is available:

* Date fields can have their format specified with the same format as [CiviCRM's date display](https://docs.civicrm.org/user/en/latest/initial-set-up/dates/), e.g. `[api4:start_date:%B %E, %Y]`
* File upload fields can be output as images with width, height, and alt text specified, e.g. `[api4:My_Custom_Field_Group.Image_Upload:img:800x300:alt=A picture]`
* A line break tag can be output with fields only when they contain data with `:br`, e.g. `[api4:My_Custom_Field_Group.Optional_Field:br]`

Field values are escaped and shown as plain text, except rich text fields. Fields that use the rich text editor in CiviCRM (for example the event `description`, or a custom field with the *Rich Text Editor* input type) are output as HTML, filtered through WordPress's `wp_kses_post` so scripts and other unsafe markup are removed. To output HTML from any other field, such as a *Note* (TextArea) custom field that holds HTML, add `:html`, e.g. `[api4:My_Custom_Field_Group.Notes:html]`. Shortcodes inside a field value are never run.

### Filtering

Specifying a non-numeric `id` attribute to the `[ux_cv_api4_get]` shortcode instructs it to use that value parameter in the GET request as the ID, e.g. `[ux_cv_api4_get entity=Event is_public=1 is_active=1 id=event_id]` with the url `http://example.org/event/?event_id=20` will result in the event with id 20 being fetched for the shortcode.

You can define custom filters through the GET request query parameters by adding `?<parameter>` to the shortcode attribute value for replacement.
You can further define a default value for the attribute filter by adding `??<parameter>` to the shortcode attribute value.

For example:

`[ux_cv_api4_get entity=Event cache_results=false my_events=true participant_status_id=?participant_status_id??IN:1,2,7,8,14,15,17 title=LIKE:%?keyword% is_public=1 is_active=1 sort=start_date:DESC?sort limit=0]`

- If the `participant_status_id` query parameter is not present in the URL, the shortcode attribute will default to the value after the `??`.
- If the `participant_status_id` query parameter is present in the URL, the shortcode attribute value will be replaced by the query parameter value.
- If the `keyword` query parameter is present in the URL, the value of the keyword in the shortcode attribute will be replaced by the query parameter value. e.g. `LIKE:%?my-query%`

For now, a form can be built using a Custom HTML WordPress block. Use the HTML ID `wp-civicrm-ux-filter-form` on the form element to enable JavaScript handling from `wp-civicrm-ux-filter.js`.

At this stage, only select lists and the `[urlparam]` shortcode are supported for filters. See [URL Parameter Shortcodes](url-parameters.md) for the full set of attributes it supports.

- Map the name of the select element to the desired query parameter.
- Set the option values to the value to pass through to the shortcode attribute.

Example usage:
```
<form id="wp-civicrm-ux-filter-form">
   <label for="search-keyword">Keywords</label>
   [urlparam htmltag="input" type="text" name="keyword" id="search-keyword" attr="value" param="keyword" /]
   <label for"participant_status_id">Filter events</label>
   <select name="participant_status_id">
      <option value="">All Events</option>
      <option value="1">Upcoming / Mark Attendance</option>
      <option value="17">Incomplete Evaluations</option>
   </select>
   <label for"sort">Sort by</label>
   <select name="sort">
      <option value="start_date:DESC">Date Descending</option>
      <option value="start_date:ASC">Date Ascending</option>
   </select>
   <a class="button" href="?keyword=">Reset</a>
</form>
```


## Permissions

`Contact` queries run with CiviCRM's permission checks **on**, so they return only the records the person viewing the page is allowed to see. `Event` queries run with permission checks **off**, because an events listing or calendar is meant to show public events to anonymous visitors, who usually do not have the *access CiviEvent* permission. Add `is_public=1 is_active=1` to an Event query yourself to limit it to public, active events. The shortcode does not add those conditions for you, so without them it returns every event, including non-public and inactive ones.

`check_permissions=0` turns the checks off for a query (and `check_permissions=1` turns them on). Use `check_permissions=0` deliberately: it returns **every** matching record to whoever can view the page, so only use it where that is intended, and keep such pages visible only to the audience that should see the data. If you allow a further entity through the `ux_cv_api4_get/allowed_entities` filter, it defaults to checks on. See the security note at the top of this guide about who should be allowed to edit pages that use these shortcodes.

## CiviCRM API trouble-shooting

If the CiviCRM API shortcode is not evaluating and returning a **blank** result, check the CiviCRM log file for errors. An error message along with a CiviCRM backtrace will be shown.

A blank result can also mean the viewer does not have permission to see the records. Verify the permissions by running the same query as that user with the WP or CV CLI (below). To confirm whether permissions are the cause, you can temporarily add `check_permissions=0`.

Using the **wp** CLI:
```wp --user=<WP User Name> cv api4 Contact.get +w id=<Contact ID>```

Using the **cv** CLI:
```cv --user=<WP User Name> api4 Contact.get +w id=<Contact ID>```

For Access Control, grant all users the CiviCRM permission: ```CiviCRM: access all custom data``` as this is required to view Custom Fields for Contacts. 

## CiviCRM API caching

CiviCRM API responses are cached as a WordPress transient with a minimum lifetime of 4 hours. With the exception when the page URL includes the ?reset parameter, these requests are not cached at all. This is particularly useful for Events and Contribution pages, so that the latest changes are always shown.

If the API request is returning the incorrect results or if you have changed the parameters and are not seeing the correct results, then try flushing the WordPress transient cache.

```wp transient delete --all```

Alternatively, add the optional shortcode parameter *`cache_results=false`* to bypass caching. This is useful if you do not want to add the ?reset parameter to the URL. For example, `[ux_cv_api4_get entity=Event cache_results=false id=id]`.
