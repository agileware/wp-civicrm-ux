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
 - [campaign-info-with-thermometer id=3]
    * Formatted html with styling
 - [campaign-honour-listing id=3]
    * Formatted html with styling
 - [campaign-found-raised id=3]
 - [campaign-goal-amount id=3]
 - [campaign-end-date id=3]
 - [campaign-day-remaining id=3]
 - [campaign-total-contribution-number id=3]  
 
Note:  
Id for the campaign id is required.  
Shortcodes return raw string value except first two

#### Event
 - [ical-feed types="Meeting,Exhibition"]
 - [civicrm-event-listing types="Training"]
 
Note:  
Type is optional.  
Shortcodes return formatted html with styling.

## Development
### How to add shortcode
1. Create a php file in shortcodes directory.
1. Within the file, create a class which implement `iAgileware_Civicrm_Utilities_Shortcode`.
1. Implement there functions defined in the interface. It will be easy if you are using PhpStorm.
