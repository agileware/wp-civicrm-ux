# CiviCRM UX
CiviCRM UX plugin provides shortcodes for integrating CiviCRM with your website.

## General usages
### Shortcodes
#### Campaign

The Campaign shortcodes accept a CiviCRM Campaign ID as a parameter and display the fundraising goals by querying the CiviCRM Campaign and associated Contributions.
1. `[ux_campaign_info_thermometer id=3]`  
 The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
 This shortcode consists of:
    * Funds raised to date;
    * Display thermometer style graph of funds and goal amount;
    * Campaign goal amount;
    * Campaign days remaining;
    * Total number of donations received;  
  This shortcode has been formatted in html with styling.
  
2. `[ux_campaign_honour_listing id=3 display-amount=false]`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.   
This shortcode is to display the honor roll information for CiviCRM Contributions related to the CiviCRM Campaign.   
The most recent 100 Contributions related to the Campaign will be displayed.   
The most recent contributor will be on the top of the list as well.   
Display-amount can be set as true or false. The default is false.  
This shortcode has been formatted in html with styling.

3. `[ux_campaign_thermometer id=1]`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display the thermometer style graph of funds and goal amount.

4. `[ux_campaign_total_raised id=3]`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display funds raised to date. For example, $ 525.00.

5. `[ux_campaign_goal_amount id=3]`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display the goal amount of Campaign. For example, $ 2,000.00.

6. `[ux_campaign_end_date id=3]`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display the end date of Campaign. For example, 31 June 2019.

7. `[ux_campaign_days_remaining id=3 end-text='on-going']`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display days remaining of Campaign. For example, 3 days remaining.  
If the end date expires, it returns the text in 'end-text'. The text in 'end-text' can be changed to any text as well.

8. `[ux_campaign_number_contributions id=3]`   
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display the total number of donations received. For example, 7.
 
**Note**:   
Id for the campaign id is required.  
Shortcodes return the raw string value except first 2 shortcodes.

#### Event

Event shortcodes accepts a CiviCRM Event type as a parameter and displays the event listings.
1. `[ux_event_ical_feed type="Meeting,Exhibition"]display text[/ical-feed]`  
This shortcode allows downloading the calendar of CiviCRM events.   
The type of shortcode is the Event Type which you could find in **CiviCRM Dashboard >> CiviEvent >> Event Types**.   
The type is optional. If the type is not specified, the calendar will include all types of events.  
The 'display text' can be changed to any text as well.

2. `[ux_event_listing type="Training"]`  
This shortcode is to display the event listing of the CiviCRM event type.   
The type of shortcode is the Event Type which you could find in **CiviCRM Dashboard >> CiviEvent >> Event Types**.   
The event listing displays the start date, the end date, the event name, the registration link, the brief description of event and the link for more information.   
The type is optional. If the type is not specified, there will be an event listing of all types of events.  
This shortcode has been formatted in html with styling.

3. `[ux_event_upcoming count=5 type="Meeting"]`  
This shortcode is to display the upcoming event listing of the CiviCRM event type.   
The type of shortcode is the Event Type which you could find in **CiviCRM Dashboard >> CiviEvent >> Event Types**.   
The type is optional. If the type is not specified, there will be an event listing of all types of events.   
The value of 'count' decides how many events will be displayed in upcoming event listings.  
This shortcode has been formatted in html with styling.

##### Parameters
`type`: filter for event type  
For example, https://example.com/wp-json/ICalFeed/manage?hash=some&type=Meeting,Exhibition

##### Note
 - Using this feed with Google calendar may get issue with its [long refresh period](https://webapps.stackexchange.com/a/6315).
 
### REST API

@TODO Documentation in this section is incomplete

#### iCal feed
 - ICalFeed/event
 - ICalFeed/manage

### Membership

1. `[ux_membership_expiry]`  
Return a HTML tag with the membership expiry date of the login user.

1. `[ux_membership_id]`  
Return the membership id of the login user.

1. `[ux_membership_join_url`  
Return the join form URL. The URL can be configured in the settings page.

1. `[ux_membership_renewal_date]`  
Return the renewal date of the membership for the login user

1. `[ux_membership_renewal_url]`  
Return the renewal form URL. The URL can be configured in the settings page.

1. `[ux_membership_status]`  
Return the membership status of the login user.

1. `[ux_membership_summary]`  
Return the membership summary of the login user.

1. `[ux_membership_type]`  
Return the membership type of the login user.

#### CiviCRM Data List using the CiviCRM Data Processor

`[ux_civicrm_listing]`
 - **dpid**: data processor id
 - **limit**: the limit of result default is 0(no limit)
 - **sort**: the order of result
 - **autopop_user_id**: get the logged in user id and pass it to the parameter. State the parameter name, like *contact_id*
 - **format**: default is table
 
This shortcode requires the [CiviCRM Data Processor](https://lab.civicrm.org/extensions/dataprocessor) extension to be installed on the CiviCRM site.

#### Activity

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

#### Contact

`[ux_contact_value]`
Parameters:
 - `id`             the contact id. Default is the current login user
 - `permission`     what permissions to check. Separated by comma. Default is 'View All Contacts'
 - `id_from_url`    if given any name, the shortcode will get contact id from url with the given name
 - `field`          what field to display **required**
 - `default`        the value to display if empty

#### WordPress helper shortcodes

@TODO Documentation in this section is incomplete

`[ux_cf_value]`  
 - `type`
 - `id`
 - `field`
 - `single`
 - `default` not used yet.

This shortcode is designed for developer. The first four attributes will be passed to [`get_metadata`](https://developer.wordpress.org/reference/functions/get_metadata/).

`[ux_convert_date]`
 - `return_timezone` the 'to' timezone
 - `timezone` the 'from' timezone

The date format for both input and output is `d/m/Y g:ia`
 
### Caldera magic tags

1. `{contact:related_subtype}`  
Return the sub-type of the related contact.  
This magic tag is designed for a specific website (the relationship type is hardcoded). You can change the code if you know what you are doing.

1. `{contact:subtype}`  
Return all sub-types of the login user.

1. `{member:membership}`  
Return all memberships of the login user.

1. `{member:membership_type}`  
Return the membership type of the login user. Also work with checksum.

1. `{member:membership_value}`  
Return the price field value id of the login user's membership.

1. `{member:renewal}`  
Return 0 if there is no membership for the login user; 1 if the membership of the login user is going to expire in three months.

1. `{user:roles}`  
Return the user roles of the logged in user, each role is comma separated

## Development

### How to add shortcode
1. Create a php file in **shortcodes** directory.
2. Within the file, create a class which implements `iCivicrm_Ux_Shortcode`.
3. Implement all functions defined in the interface. It is recommended to use PhpStorm.

### How to add REST API route
1. Create a php file in **rest** directory.
2. Within the file, create a class which implement `iCivicrm_Ux_REST`.
3. Implement all functions defined in the interface. It is recommended to use PhpStorm.

### CSS and JavaScript
All css files should be in `public/css` or `admin/css`. If the new css files are created, please make sure to enqueue them.
All javascript files should be in `public/js` or `admin/js`.
