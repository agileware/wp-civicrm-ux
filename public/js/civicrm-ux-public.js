(function ($) {
    'use strict';

    $(function() {
        if(wp.CiviCRM_UX.is_recur_default) {
            jQuery(':input[name=is_recur]').prop('checked', 'checked')
        }
    });

})(jQuery);
