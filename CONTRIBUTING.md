# Contributing to CiviCRM UX

This guide is for developers who want to help maintain or extend this plugin. If you just want to *use* the plugin's shortcodes, see the [User Guide](USAGE.md) instead.

## Architecture overview

The plugin bootstraps from `civicrm-ux.php`, which loads `includes/class-civicrm-ux.php`. Shortcodes and REST API routes are not registered by hand — they are auto-discovered:

- `Civicrm_Ux_Shortcode_Manager` (`includes/class-civicrm-ux-shortcode-manager.php`) recursively scans the `shortcodes/` directory for every class that extends `Abstract_Civicrm_Ux_Shortcode`, instantiates it, and on the `init` hook calls `add_shortcode( $instance->get_shortcode_name(), [$instance, 'shortcode_callback'] )`.
- `Civicrm_Ux_REST_Manager` (`includes/class-civicrm-ux-rest-manager.php`) does the same for the `rest/` directory, for classes extending `Abstract_Civicrm_Ux_REST`, calling `register_rest_route()` for each.
- Both managers extend `Abstract_Civicrm_Ux_Module_Manager` (`includes/abstract-class/class-abstract-civicrm-ux-module-manager.php`), which uses `Civicrm_Ux_Loader::load()` (`includes/class-civicrm-ux-loader.php`) to recursively find and instantiate the matching classes. This means **a new shortcode or REST route only needs to be added as a file in the right directory** — there is no separate registration step to remember.
- Every managed class implements `iCivicrm_Ux_Managed_Instance` (`includes/interface/interface-civicrm-ux-managed-instance.php`), which just requires a constructor accepting the manager instance.

## Directory structure

- `shortcodes/` — one class per shortcode, grouped into subdirectories by feature area (`event/`, `campaign/`, `membership/`, `contact/`, `civicrm/`, `wordpress/`, `gdpr/`, `self-serve-checksum/`).
- `rest/` — one class per REST API route.
- `includes/` — plugin bootstrap, the module manager/loader infrastructure, and shared utility classes (`includes/utils/`).
- `includes/abstract-class/` — the abstract base classes described above.
- `includes/interface/` — shared interfaces.
- `templates/` — front-end template parts, overridable from a theme (see the Membership Shortcodes section of [USAGE.md](USAGE.md) for how theme overrides work).
- `admin/` and `public/` — admin settings screens and front-end assets (`css`/`js` subdirectories, plus `partials/` for admin views).
- `packaged/` — bundled third-party code that isn't installed via Composer, e.g. `packaged/url-params/` (the `[urlparam]`/`[ifurlparam]` shortcodes) and `packaged/PHP81_BC/`. Treat these as vendored — avoid modifying them in place; update at the source instead if a fix is needed.
- `data/` — example CiviCRM Form Processor exports referenced from the User Guide.

## How to add a shortcode

1. Create a PHP file under `shortcodes/`, in the subdirectory matching its feature area (or a new one if it doesn't fit an existing area).
2. Define a class that `extends Abstract_Civicrm_Ux_Shortcode` (`includes/abstract-class/class-abstract-civicrm-ux-shortcode.php`).
3. Implement:
   - `get_shortcode_name()` — returns the shortcode tag, e.g. `'ux_my_shortcode'`.
   - `shortcode_callback( $atts = [], $content = null, $tag = '' )` — returns the HTML output.
4. Normalise attribute keys with `array_change_key_case()`, apply defaults with `shortcode_atts()`, and sanitise every attribute (`sanitize_text_field()`, `absint()`, `sanitize_url()`, etc.) before use — follow the existing shortcodes in `shortcodes/` as a pattern.
5. Document the new shortcode in [USAGE.md](USAGE.md).

## How to add a REST API route

1. Create a PHP file under `rest/`.
2. Define a class that `extends Abstract_Civicrm_Ux_REST` (`includes/abstract-class/class-abstract-civicrm-ux-rest.php`).
3. Implement:
   - `get_route()` — the REST namespace, e.g. `'civicrm_ux'`.
   - `get_endpoint()` — the endpoint path, which may include regex parameters, e.g. `'my-endpoint/(?P<id>[\d]+)'`.
   - `get_method()` — the HTTP method, e.g. `WP_REST_Server::READABLE`.
   - `rest_api_callback( $data )` — handles the request and returns the response.
4. Override `check_permissions()` if the default (`is_user_logged_in()`) isn't appropriate — for example, a publicly accessible feed, or one gated by a hash/token instead of a login.

## CSS and JavaScript

- Front-end CSS/JS belongs in `public/css` and `public/js`; admin-only CSS/JS belongs in `admin/css` and `admin/js`.
- Any new CSS/JS file must be enqueued (see the existing `wp_enqueue_style()`/`wp_enqueue_script()` calls in the plugin) — a file sitting in these directories unused won't be loaded automatically.

## Dependencies

- PHP dependencies are managed with Composer (`composer.json`) — currently just `sabre/vobject`, used to generate the iCal feeds.
- There is no JavaScript build step or `package.json`; front-end JS is written and served as-is from `public/js`.
- There is no automated test suite in this repository — verify changes manually against a CiviCRM/WordPress site before releasing.

## Versioning and releases

- The plugin version lives in the `Version:` header of `civicrm-ux.php`. Bump it as part of any release.
- Updates are distributed via the GitHub-based updater (`includes/class-civicrm-ux-upgrader.php`), configured against the `agileware/wp-civicrm-ux` repository — there is no separate `readme.txt` or changelog file to keep in sync.
