# WordPress CiviCRM UX - A better user experience for integrating WordPress and CiviCRM

## Description

This plugin adds many useful shortcodes, some new Caldera Forms smart tags and provides additional functionality to improve the user experience for integrating CiviCRM with a WordPress site.


## Usage

You can find the [documentation](USAGE.md) or in the WordPress Settings page.

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
