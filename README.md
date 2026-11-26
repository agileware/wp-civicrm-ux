# WordPress CiviCRM UX - A better user experience for integrating WordPress and CiviCRM

## Description

This plugin adds many useful shortcodes and provides additional functionality to improve the user experience for integrating CiviCRM with a WordPress site.

## Requirements

* WordPress 6.5 or later
* PHP 8.1 or later
* [CiviCRM](https://civicrm.org)

## Security and CiviCRM data

This plugin's shortcodes run CiviCRM queries and render CiviCRM data on behalf of the person
who authors the page. Anyone who can add or edit a post or page that contains these shortcodes
can cause CiviCRM data to be displayed, and some shortcodes (for example `[ux_cv_api4_get]`)
read data without applying CiviCRM's own permission checks.

For this reason you must trust everyone who can edit pages that use these shortcodes. They
should be internal staff who are themselves permitted to view all of the CiviCRM data the
shortcodes can expose. ***Do not grant post or page editing capabilities (for example the
Contributor, Author or Editor roles) to untrusted or public-facing users.***

## Features

* Interactive Events Calendar, Event Listing and iCal Feed shortcodes
* A CiviCRM APIv4 shortcode for building custom listings and pages from CiviCRM contacts and events
* Campaign fundraising shortcodes, including a thermometer graph and contribution honour roll
* Event registration cancellation and self-service attendance marking shortcodes
* Membership summary, status and renewal shortcodes
* A CiviCRM Data Processor listing shortcode
* Activity listing and Contact field value shortcodes
* A Self Serve Checksum form to protect CiviCRM forms behind a valid checksum link

## Documentation

| Page | What it covers |
|---|---|
| [Events Calendar](docs/event-calendar.md) | The interactive Events Calendar shortcode and its options |
| [CiviCRM APIv4 Shortcode](docs/api4-get.md) | `[ux_cv_api4_get]` for custom listings and pages of contacts and events, with examples, filtering, permissions, trouble-shooting and caching |
| [URL Parameter Shortcodes](docs/url-parameters.md) | `[urlparam]` and `[ifurlparam]`, for reading values from the page URL |
| [Campaign Shortcode](docs/campaign.md) | Campaign fundraising thermometer and contribution honour roll |
| [Event shortcodes](docs/event-shortcodes.md) | iCal feed, event listing, cancel registration, mark attendance, complete evaluation and custom button shortcodes |
| [Membership Shortcodes](docs/membership.md) | Membership summary, status and renewal shortcodes, and overriding their templates |
| [CiviCRM Data Processor Shortcode](docs/data-processor-listing.md) | `[ux_civicrm_listing]`, listing a Data Processor's output, and its permissions |
| [Contact, activity and GDPR shortcodes](docs/contact-shortcodes.md) | Activity listing, contact field values and the GDPR communication preferences link |
| [WordPress Helper Shortcodes](docs/wordpress-helpers.md) | `[ux_cf_value]` and `[ux_convert_date]` |
| [Self Serve Checksum Form](docs/self-serve-checksum.md) | Protecting CiviCRM forms behind a valid checksum link, its settings and Cloudflare Turnstile |
| [Security policy](SECURITY.md) | How to report a security vulnerability |

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


![Agileware](docs/logo/agileware-logo.png)
