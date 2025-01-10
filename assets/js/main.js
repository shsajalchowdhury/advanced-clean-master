jQuery(document).ready(function ($) {
    // Cleanup action buttons
    $('.feature button').on('click', function () {
        var action = $(this).data('action');
        var button = $(this);

        $.ajax({
            url: acmtCleanupAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'acmt_clean_action', // Updated action name
                cleanup_action: action,
                nonce: acmtCleanupAjax.nonce
            },
            beforeSend: function () {
                button.prop('disabled', true).text('Cleaning...');
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload(); // Reload to update stats
                } else {
                    alert('Failed: ' + response.data.message);
                }
            },
            error: function (xhr, status, error) {
                alert('An error occurred: ' + error);
                button.prop('disabled', false).text('Clean Now');
            }
        });
    });

    // Scheduled cleanup toggles
    $('.toggle-switch input').on('change', function () {
        var setting = $(this).attr('id'); // "daily-cleanup" or "weekly-cleanup"
        var enabled = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: acmtCleanupAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'acmt_toggle_scheduled_cleanup', // Updated action name
                schedule: setting.replace('-cleanup', ''), // "daily" or "weekly"
                enabled: enabled,
                nonce: acmtCleanupAjax.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Failed: ' + response.data.message);
                }
            },
            error: function (xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });
});
