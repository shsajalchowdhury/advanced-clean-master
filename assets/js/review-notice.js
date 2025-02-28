jQuery(document).ready(function($) {
    // Handle "Remind Me Later" click
    $('.acmt-remind-later').on('click', function() {
        dismissReviewNotice('remind');
    });

    // Handle "Never Show Again" click
    $('.acmt-dismiss-permanently').on('click', function() {
        dismissReviewNotice('dismiss');
    });

    // Handle the default WordPress notice dismissal
    $(document).on('click', '.acmt-review-notice .notice-dismiss', function() {
        dismissReviewNotice('dismiss');
    });

    function dismissReviewNotice(type) {
        $.ajax({
            url: acmtReview.ajax_url,
            type: 'POST',
            data: {
                action: 'acmt_dismiss_review',
                type: type,
                nonce: acmtReview.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.acmt-review-notice').fadeOut();
                }
            }
        });
    }
});
