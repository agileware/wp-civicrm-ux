(function ($) {
    'use strict';

    $(function() {
        if(wp.CiviCRM_UX.is_recur_default && !jQuery(':input[name=is_recur]').prop('disabled')) {
            jQuery('.is_recur-section :input').prop('disabled', false);
            jQuery(':input[name=is_recur]').prop('checked', 'checked');
        }
        if(wp.CiviCRM_UX.is_autorenew_default && !jQuery(':input[name=auto_renew]').prop('disabled')) {
            jQuery('.auto_renew_section :input').prop('disabled', false);
            jQuery(':input[name=auto_renew]').prop('checked', 'checked');
        }
    });

})(jQuery);
