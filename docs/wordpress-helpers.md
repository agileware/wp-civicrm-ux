# WordPress Helper Shortcodes

These general purpose shortcodes are not specific to CiviCRM, and are provided as convenient helpers for building pages.

## `[ux_cf_value]`

Outputs a WordPress custom field (post meta, user meta, etc.) value, using WordPress's [`get_metadata`](https://developer.wordpress.org/reference/functions/get_metadata/) function.

- `type` the metadata type, e.g. `post`, `user`, `comment`, `term`. Defaults to `post`.
- `id` the object ID to read metadata from. Defaults to the current post's ID.
- `field` **required**, the metadata field/key name to read.
- `single` whether to return a single value or an array. Defaults to `true`.
- `default` *not currently used.*

Example usage:

`[ux_cf_value type="post" field="my_custom_field"]`

## `[ux_convert_date]`

Converts a date/time string between timezones. Wrap the date string to convert as the shortcode content.

- `timezone` the timezone the input date/time is in, e.g. `Australia/Melbourne`.
- `return_timezone` the timezone to convert the date/time to, e.g. `UTC`.
- `return_format` optional [PHP date format](https://www.php.net/manual/en/datetime.format.php) for the output. Defaults to `d/m/Y g:ia`.

The input date format must be `d/m/Y g:ia`, e.g. `25/12/2024 6:00pm`.

Example usage:

`[ux_convert_date timezone="Australia/Melbourne" return_timezone="UTC"]25/12/2024 6:00pm[/ux_convert_date]`
