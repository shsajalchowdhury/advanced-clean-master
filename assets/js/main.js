jQuery(document).ready(function($) {
    // Cleanup action buttons
    $('.feature button').on('click', function() {
        var action = $(this).data('action');
        var button = $(this);

        $.ajax({
            url: cleanupMasterAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clean_action',
                cleanup_action: action,
                nonce: cleanupMasterAjax.nonce
            },
            beforeSend: function() {
                button.prop('disabled', true).text('Cleaning...');
            },
            success: function(response) {
                alert(response.data.message);
                location.reload();
            }
        });
    });

    // Scheduled cleanup toggles
    
        $('.toggle-switch input').on('change', function () {
            let setting = $(this).attr('id'); // "daily-cleanup" or "weekly-cleanup"
            let enabled = $(this).is(':checked') ? 1 : 0;
    
            $.post(ajaxurl, {
                action: 'toggle_scheduled_cleanup',
                schedule: setting.replace('-cleanup', ''), // "daily" or "weekly"
                enabled: enabled,
                nonce: cleanupMasterAjax.nonce
            }, function (response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Failed to update the setting.');
                }
            });
        });
    
});
