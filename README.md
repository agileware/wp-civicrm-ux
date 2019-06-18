# Agileware CiviCRM Utilities
Tags: civicrm, shortcode, caldera-form  
Requires at least: 5.1  
Tested up to: 5.1  
Stable tag: 5.1  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

## Description
Agileware CiviCRM plugin all in one.

## General usages
### Shortcodes
#### Campaign
 - `[campaign-info-with-thermometer id=3]`
    * Formatted html with styling
 - `[campaign-honour-listing id=3]`
    * Formatted html with styling
 - `[campaign-funds-raised id=3]`
 - `[campaign-goal-amount id=3]`
 - `[campaign-end-date id=3]`
 - `[campaign-day-remaining id=3]`
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

### REST API
 - ICalFeed/event
 - ICalFeed/manage

## Development
### How to add shortcode
1. Create a php file in **shortcodes** directory.
1. Within the file, create a class which implement `iAgileware_Civicrm_Utilities_Shortcode`.
1. Implement all functions defined in the interface. It will be easy if you are using PhpStorm.

### How to add REST API route
1. Create a php file in **rest** directory.
1. Within the file, create a class which implement `iAgileware_Civicrm_Utilities_REST`.
1. Implement all functions defined in the interface. It will be easy if you are using PhpStorm.

### CSS and JavaScript
All css files should go to `public/css` or `admin/css`. If you create new css files, make sure you enqueue them.
The same as javascript files. They should be in `public/js` or `admin/js`.
