# WordPress CiviCRM UX - A better user experience for integrating WordPress and CiviCRM

## Description

This plugin adds many useful shortcodes and provides additional functionality to improve the user experience for integrating CiviCRM with a WordPress site.

## Requirements

* WordPress 6.5 or later
* PHP 8.1 or later
* [CiviCRM](https://civicrm.org)

## Security — who may edit pages

This plugin's shortcodes run CiviCRM queries and render CiviCRM data on behalf of the person
who authors the page. Anyone who can add or edit a post or page that contains these shortcodes
can cause CiviCRM data to be displayed, and some shortcodes (for example `[ux_cv_api4_get]`)
read data without applying CiviCRM's own permission checks.

For this reason you must trust everyone who can edit pages that use these shortcodes. They
should be internal staff who are themselves permitted to view all of the CiviCRM data the
shortcodes can expose. Do not grant post or page editing capabilities (for example the
Contributor, Author or Editor roles) to untrusted or public-facing users.

## Features

* Interactive Events Calendar, Event Listing and iCal Feed shortcodes
* A CiviCRM APIv4 shortcode for building custom listings and pages from any CiviCRM entity
* Campaign fundraising shortcodes, including a thermometer graph and contribution honour roll
* Event registration cancellation and self-service attendance marking shortcodes
* Membership summary, status and renewal shortcodes
* A CiviCRM Data Processor listing shortcode
* Activity listing and Contact field value shortcodes
* A Self Serve Checksum form to protect CiviCRM forms behind a valid checksum link

## Usage

For more details on how to use this plugin, please read the [documentation](USAGE.md).

## For Developers

Please read the [contributing guide](CONTRIBUTING.md) for tips on how to maintain and extend this project.


## About the Authors

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
