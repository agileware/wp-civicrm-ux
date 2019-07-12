# CiviCRM UX
CiviCRM UX plugin provides shortcodes for integrating CiviCRM with your website.

## General usages
### Shortcodes
#### Campaign
The Campaign shortcodes accept a CiviCRM Campaign ID as a parameter and display the fundraising goals by querying the CiviCRM Campaign and associated Contributions.
1. `[campaign-info-with-thermometer id=3]`  
 The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
 This shortcode consists of:
    * Funds raised to date;
    * Display thermometer style graph of funds and goal amount;
    * Campaign goal amount;
    * Campaign days remaining;
    * Total number of donations received;  
  This shortcode has been formatted in html with styling.
  
2. `[campaign-honour-listing id=3 display-amount=false]`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.   
This shortcode is to display the honor roll information for CiviCRM Contributions related to the CiviCRM Campaign.   
The most recent 100 Contributions related to the Campaign will be displayed.   
The most recent contributor will be on the top of the list as well.   
Display-amount can be set as true or false. The default is false.  
This shortcode has been formatted in html with styling.

3. `[campaign-thermometer id=1]`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display the thermometer style graph of funds and goal amount.

4. `[campaign-funds-raised id=3]`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display funds raised to date. For example, $ 525.00.

5. `[campaign-goal-amount id=3]`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display the goal amount of Campaign. For example, $ 2,000.00.

6. `[campaign-end-date id=3]`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display the end date of Campaign. For example, 31 June 2019.

7. `[campaign-days-remaining id=3 end-text='on-going']`  
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display days remaining of Campaign. For example, 3 days remaining.  
If the end date expires, it returns the text in 'end-text'. The text in 'end-text' can be changed to any text as well.

8. `[campaign-total-contribution-number id=3]`   
The id of shortcode is the Campaign ID which you could find in **CiviCRM Dashboard >> Campaigns >> Campaign Dashboard**.  
This shortcode without styling is to display the total number of donations received. For example, 7.
 
**Note**:   
Id for the campaign id is required.  
Shortcodes return the raw string value except first 2 shortcodes.

#### Event
Event shortcodes accepts a CiviCRM Event type as a parameter and displays the event listings.
1. `[ical-feed type="Meeting,Exhibition"]display text[/ical-feed]`  
This shortcode allows downloading the calendar of CiviCRM events.   
The type of shortcode is the Event Type which you could find in **CiviCRM Dashboard >> CiviEvent >> Event Types**.   
The type is optional. If the type is not specified, the calendar will include all types of events.  
The 'display text' can be changed to any text as well.

2. `[civicrm-event-listing type="Training"]`  
This shortcode is to display the event listing of the CiviCRM event type.   
The type of shortcode is the Event Type which you could find in **CiviCRM Dashboard >> CiviEvent >> Event Types**.   
The event listing displays the start date, the end date, the event name, the registration link, the brief description of event and the link for more information.   
The type is optional. If the type is not specified, there will be an event listing of all types of events.  
This shortcode has been formatted in html with styling.

3. `[civicrm-upcoming-events count=5 type="Meeting"]`  
This shortcode is to display the upcoming event listing of the CiviCRM event type.   
The type of shortcode is the Event Type which you could find in **CiviCRM Dashboard >> CiviEvent >> Event Types**.   
The type is optional. If the type is not specified, there will be an event listing of all types of events.   
The value of 'count' decides how many events will be displayed in upcoming event listings.  
This shortcode has been formatted in html with styling.

### Activity
`[civicrm-activities-listing]`  
Parameters:
 - `$type`: activity type id or activity type label. Support multiple values separated by a comma without space. The default is empty.
 - `$limit`: the maximum number for the activity result. The default is 0 (unlimited).
 - `$relationship-id`: the activities of contacts which have relationships will be added into the result.
 - `$field`: the fields to return. Separated by a comma without space. The default returns contact names and activity subjects. 
 The order of the output fields will follow the order of the input fields.
 - `$format`: the display format. For example, `table` for a tabular layout
 - `$sort`: sorting field names in ascending or descending order with `ASC` or `DESC`. The default is `activity_date_time DESC`.
 
For example:  
`[civicrm-activities-listing type='Test' relationship-id=5 field='status,source_contact_name,activity_date_time']`

### REST API
#### iCal feed
 - ICalFeed/event
 - ICalFeed/manage
 
##### Parameters
`type`: filter for event type  
For example, https://example.com/wp-json/ICalFeed/manage?hash=some&type=Meeting,Exhibition

##### Note
 - Using this feed with Google calendar may get issue with its [long refresh period](https://webapps.stackexchange.com/a/6315).

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