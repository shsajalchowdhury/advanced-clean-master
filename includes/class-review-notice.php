<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class ACMT_Review_Notice {
    private $install_date;
    private $reminder_date;
    private $plugin_name = 'Advanced Clean Master';

    public function __construct() {
        $this->install_date = get_option('acmt_install_date', false);
        $this->reminder_date = get_option('acmt_reminder_date', false);

        if (!$this->install_date) {
            $this->install_date = current_time('timestamp');
            update_option('acmt_install_date', $this->install_date);
        }

        add_action('admin_notices', array($this, 'display_review_notice'));
        add_action('wp_ajax_acmt_dismiss_review', array($this, 'dismiss_review_notice'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_notice_script'));
    }

    public function enqueue_notice_script() {
        wp_enqueue_script('acmt-review-notice', plugin_dir_url(dirname(__FILE__)) . 'assets/js/review-notice.js', array('jquery'), '1.0.8', true);
        wp_localize_script('acmt-review-notice', 'acmtReview', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acmt_review_nonce')
        ));
    }

    public function should_show_notice() {
        // Don't show if already dismissed or reminded
        if (get_option('acmt_review_dismissed')) {
            return false;
        }

        // Show after 7 days of installation
        $days_since_install = (current_time('timestamp') - $this->install_date) / DAY_IN_SECONDS;
        if ($days_since_install < 7) {
            return false;
        }

        // If reminded, check if 30 days have passed
        if ($this->reminder_date) {
            $days_since_reminder = (current_time('timestamp') - $this->reminder_date) / DAY_IN_SECONDS;
            if ($days_since_reminder < 30) {
                return false;
            }
        }

        return true;
    }

    public function display_review_notice() {
        if (!$this->should_show_notice()) {
            return;
        }

        $plugin_icon = plugin_dir_url(dirname(__FILE__)) . 'assets/icons/icon-128x128.png';
        ?>
        <div class="notice notice-info acmt-review-notice is-dismissible">
            <div class="acmt-review-notice-content">
                <img src="<?php echo esc_url($plugin_icon); ?>" alt="<?php echo esc_attr($this->plugin_name); ?>" class="acmt-plugin-icon">
                <div class="acmt-review-text">
                    <h3><?php
                        // translators: %s is the plugin name
                        printf(esc_html__('Enjoying %s?', 'advanced-clean-master'), esc_html($this->plugin_name));
                    ?></h3>
                    <p>
                        <?php
                        // translators: %s is the plugin name
                        printf(
                            esc_html__('Thank you for using %s! If you find it helpful, please take a moment to rate it on WordPress.org. Your feedback helps us improve and grow!', 'advanced-clean-master'),
                            '<strong>' . esc_html($this->plugin_name) . '</strong>'
                        );
                        ?>
                    </p>
                    <div class="acmt-review-actions">
                        <a href="https://wordpress.org/support/plugin/advanced-clean-master/reviews/#new-post" class="button button-primary" target="_blank">
                            <?php esc_html_e('Rate Now', 'advanced-clean-master'); ?>
                        </a>
                        <button type="button" class="button button-secondary acmt-remind-later">
                            <?php esc_html_e('Remind Me Later', 'advanced-clean-master'); ?>
                        </button>
                        <button type="button" class="button-link acmt-dismiss-permanently">
                            <?php esc_html_e('Never Show Again', 'advanced-clean-master'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function dismiss_review_notice() {
        check_ajax_referer('acmt_review_nonce', 'nonce');

        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';

        if ($type === 'remind') {
            update_option('acmt_reminder_date', current_time('timestamp'));
        } elseif ($type === 'dismiss') {
            update_option('acmt_review_dismissed', true);
        }

        wp_send_json_success();
    }
}
