(function ($) {
    'use strict';

    $(function() {
        if(wp.CiviCRM_UX.is_recur_default && !jQuery(':input[name=is_recur]').prop('disabled')) {
            jQuery('.is_recur-section :input').prop('disabled', false);
            jQuery(':input[name=is_recur]').prop('checked', 'checked');
        }
    });

})(jQuery);
