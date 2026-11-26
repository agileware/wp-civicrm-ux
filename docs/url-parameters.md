# URL Parameter Shortcodes

These shortcodes read values from the current page's URL query string (or a submitted form field of the same name), and are commonly used together with `[ux_cv_api4_get]` to build filter forms, as shown in the [Filtering](api4-get.md#filtering) section of the [CiviCRM APIv4 Shortcode](api4-get.md) page.

## `[urlparam]`

Outputs the value of a URL query parameter, optionally formatted, or wraps it in an HTML attribute or tag.

- *`param`* the name of the query parameter to read. Accepts a comma separated list; the first parameter found with a value is used.
- *`default`* the value to output if the parameter is not present in the URL.
- *`dateformat`* optional [PHP date format](https://www.php.net/manual/en/datetime.format.php) to apply if the parameter value can be parsed as a date.
- *`attr`* optional HTML attribute name to wrap the value in, e.g. `attr="value"` outputs `value="..."`.
- *`htmltag`* optional HTML tag name to render, using `attr` as one of its attributes. Any additional shortcode attributes not listed above are passed through as extra attributes on the tag.

Example, an input field that is pre-filled from the `keyword` URL parameter:

`[urlparam htmltag="input" type="text" name="keyword" id="search-keyword" attr="value" param="keyword"]`

## `[ifurlparam]`

Conditionally renders the shortcode content depending on whether a URL query parameter is present.

- *`param`* the name of the query parameter to check for. Accepts a comma separated list.
- *`is`* optional value to compare the parameter against. If provided, the content is only rendered when the parameter's value matches.
- *`empty`* when set, the content is rendered only when the parameter is **not** present in the URL, instead of when it is.

Example, only show content when the `keyword` parameter is present:

`[ifurlparam param="keyword"]Showing results for your search.[/ifurlparam]`
