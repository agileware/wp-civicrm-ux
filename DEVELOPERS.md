# CiviCRM UX Developer Tips

## How to add a shortcode
1. Create a php file in the **shortcodes** directory.
1. Within the file, create a class which extends `Abstract_Civicrm_Ux_SHORTCODE`.
1. Implement all functions defined in the interface. It will be easy if you are using PhpStorm.

## How to add REST API route
1. Create a php file in the **rest** directory.
1. Within the file, create a class which extends `Abstract_Civicrm_Ux_REST`.
1. Implement all functions defined in the interface. It will be easy if you are using PhpStorm.

## CSS and JavaScript
All css files should go to `public/css` or `admin/css`. If you create new css files, make sure you enqueue them.
The same as javascript files. They should be in `public/js` or `admin/js`.