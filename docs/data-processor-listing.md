# CiviCRM Data Processor Shortcode

This shortcode will output a [CiviCRM Data Processor](https://lab.civicrm.org/extensions/dataprocessor), in a table format. The Data Processor being used must have a API output defined and for this API output, the  API Entity must also be set.

`[ux_civicrm_listing]`
 - **dpid**: Data Processor ID
 - **limit**: the limit of result default is 0 (no limit)
 - **sort**: the order of result, either: asc (ascending) or desc (descending)
 - **autopop_user_id**: Define the parameter name sent to the Data Processor which contains the current users CiviCRM Contact ID. Text value. When nobody is logged in, the listing shows nothing.
 - **hide_fields**: comma separated list of fields to be excluded from the output.
 - **css_id**: CSS ID to assign to the table.

## Permissions

The data is read with CiviCRM permission checks, so the permission set on the Data Processor **API output** applies to the person viewing the page. For a listing shown to anonymous visitors, set the output permission to one that the anonymous role has in CiviCRM, and make sure the output only returns fields and records that are safe to publish. Anyone with that permission can read the whole output, not just the columns the listing shows. When the person viewing the page does not have the permission, the listing shows "CiviCRM API error." instead of the data.

Sites that need the behaviour of earlier versions (no permission check) can opt out with:

```php
add_filter( 'ux_civicrm_listing/check_permissions', '__return_false' );
```

***With this filter, any page using this shortcode shows the whole Data Processor output to everyone who can see the page.***

The example usage below uses a Data Processor with the ID of 4, excludes the contact_id column from display and assigns the CSS ID of member_directory to the table.

[Datatables](https://datatables.net/) can be used to add features to the table displayed on the page, as shown below. 

```
[ux_civicrm_listing dpid=4 autopop_user_id=0 hide_fields=contact_id css_id=member_directory format=datatable][/ux_civicrm_listing]

<script type="text/javascript" src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js" id="datatable-js-js"></script>

<script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('#member_directory').DataTable({
                'paging': true,
                'pageLength': 50,
                order: [[0, 'asc']],
                columns: [
                    { title: 'Member Name' },
                    { title: 'Country' },
                ],
            });
        });
</script>
```
