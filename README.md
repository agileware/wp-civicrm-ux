# CiviCRM UX
Tags: civicrm, shortcode, caldera-form  
Requires at least: 5.1  
Tested up to: 5.2.2  
Stable tag: 5.2.2  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

## Description
Agileware CiviCRM plugin all in one. This plugin adds many useful shortcodes and provides additional functionality to improve the user experience.

Tested with CiviCRM 5.15.0

## General usages
### Shortcodes
#### Campaign
 - `[campaign-info-with-thermometer id=3]`
    * Formatted html with styling
    * Will hide thermometer, goal, remaining days and donations if they are empty.
 - `[campaign-honour-listing id=3 display-amount=false]`
    * Formatted html with styling
    * Display-amount true or false. false is default.
 - `[campaign-thermometer id=1]`
 - `[campaign-funds-raised id=3]`
 - `[campaign-goal-amount id=3]`
 - `[campaign-end-date id=3]`
 - `[campaign-days-remaining id=3 end-text='on-going']`
    * Return 'n days remaining'
    * If the end date is past, return the text in `end-text`. Default is 'on-going'
 - `[campaign-total-contribution-number id=3]`  
 
Note:  
Id for the campaign id is required.  
Shortcodes return raw string value except first two

#### Event
 - `[ical-feed type="Meeting,Exhibition"]display text[/ical-feed]`
 - `[civicrm-event-listing type="Training"]`
 - `[civicrm-upcoming-events count=5 type="Meeting"]`
 
Note:  
Type is optional.  
Shortcodes return formatted html with styling.

### Activity
`[civicrm-activities-listing]`  
Parameters:
 - `type`: activity type id. Support multiple values separated by comma without space. Default empty
 - `limit`: the max number for result. Default 0 (unlimited)
 - `relationship-id`: by supplying this parameter, related contacts' activities will be added into the result.
 - `field`:fields to return. Separated by comma without space. Use `contact_name` for contact name. The order of the output fields will be the same as here.
 - `format`: set to `table` for a tabular layout
 - `sort`: field name with `ASC` or `DESC`. Default `activity_date_time DESC`
 
example:  
```
[civicrm-activities-listing type='Test' relationship-id=5 field='custom_60,custom_61,custom_67,custom_74,custom_77,custom_57,custom_78,custom_79,custom_80,custom_81,custom_82,custom_83,custom_84']
```

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
1. Within the file, create a class which extends `Abstract_Civicrm_Ux_SHORTCODE`.
1. Implement all functions defined in the interface. It will be easy if you are using PhpStorm.

### How to add REST API route
1. Create a php file in **rest** directory.
1. Within the file, create a class which extends `Abstract_Civicrm_Ux_REST`.
1. Implement all functions defined in the interface. It will be easy if you are using PhpStorm.

### CSS and JavaScript
All css files should go to `public/css` or `admin/css`. If you create new css files, make sure you enqueue them.
The same as javascript files. They should be in `public/js` or `admin/js`.


About the Authors
------

This WordPress plugin was developed by the team at [Agileware](https://agileware.com.au).

[Agileware](https://agileware.com.au) provide a range of CiviCRM services including:

  * CiviCRM migration
  * CiviCRM integration
  * CiviCRM extension development
  * CiviCRM support
  * CiviCRM hosting
  * CiviCRM remote training services

Support your Australian [CiviCRM](https://civicrm.org) developers, [contact Agileware](https://agileware.com.au/contact) today!


![Agileware](logo/agileware-logo.png)