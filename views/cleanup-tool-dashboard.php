<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="advanced-clean-master-container">
    <!-- Header Section -->
    <div class="header">
    <h1><?php esc_html_e( 'Advanced Clean Master', 'advanced-clean-master' ); ?></h1>
    <p><?php esc_html_e( 'Optimize your WordPress site with advanced cleanup features.', 'advanced-clean-master' ); ?></p>
    </div>

    <!-- Total Space Saved -->
    <div class="space-saved">
        <h2>Total Space Saved: <strong><?php echo esc_html( size_format( $this->calculate_total_space_saved() ) ); ?></strong></h2>
    </div>

    <!-- Cleanup Features -->
    <div class="features-grid">
        <!-- Clean Drafts -->
        <div class="feature">
            <div class="icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/drafts.svg' ); ?>" alt="Clean Drafts">
            </div>
            <h2>Clean Drafts (<?php echo intval( $stats['drafts'] ); ?>)</h2>
            <p>Remove unused or outdated draft posts</p>
            <button class="clean-now-btn" 
                    data-action="clean_drafts" 
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'acmt_cleanup_action_nonce' ) ); ?>" 
                    data-count="<?php echo intval( $stats['drafts'] ); ?>" 
                    <?php echo ( intval( $stats['drafts'] ) === 0 ) ? 'disabled title="No drafts to clean."' : ''; ?>>
                <?php echo ( intval( $stats['drafts'] ) === 0 ) ? 'No Items' : 'Clean Now'; ?>
            </button>
        </div>

        <!-- Clean Trashed Posts -->
        <div class="feature">
            <div class="icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/trashed-post.svg' ); ?>" alt="Clean Trashed Posts">
            </div>
            <h2>Clean Trashed Posts (<?php echo intval( $stats['trashed'] ); ?>)</h2>
            <p>Delete posts that are still in the trash.</p>
            <button class="clean-now-btn" 
                    data-action="clean_trashed_posts" 
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'acmt_cleanup_action_nonce' ) ); ?>" 
                    data-count="<?php echo intval( $stats['trashed'] ); ?>" 
                    <?php echo ( intval( $stats['trashed'] ) === 0 ) ? 'disabled' : ''; ?>>
                <?php echo ( intval( $stats['trashed'] ) === 0 ) ? 'No Items' : 'Clean Now'; ?>
            </button>
        </div>

        <!-- Clean Unapproved Comments -->
        <div class="feature">
            <div class="icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/comments.svg' ); ?>" alt="Clean Unapproved Comments">
            </div>
            <h2>Clean Unapproved Comments (<?php echo intval( $stats['unapproved_comments'] ); ?>)</h2>
            <p>Remove unapproved comments to reduce database bloat.</p>
            <button class="clean-now-btn" 
                    data-action="clean_unapproved_comments" 
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'acmt_cleanup_action_nonce' ) ); ?>" 
                    data-count="<?php echo intval( $stats['unapproved_comments'] ); ?>" 
                    <?php echo ( intval( $stats['unapproved_comments'] ) === 0 ) ? 'disabled' : ''; ?>>
                <?php echo ( intval( $stats['unapproved_comments'] ) === 0 ) ? 'No Items' : 'Clean Now'; ?>
            </button>
        </div>

        <!-- Clean Orphaned Media -->
        <div class="feature">
            <div class="icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/orphaned-media.svg' ); ?>" alt="Clean Orphaned Media">
            </div>
            <h2>Clean Orphaned Media (<?php echo intval( $stats['orphaned_media'] ); ?>)</h2>
            <p>Delete unused media files that are not linked to content.</p>
            <button class="clean-now-btn" 
                    data-action="clean_orphaned_media" 
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'acmt_cleanup_action_nonce' ) ); ?>" 
                    data-count="<?php echo intval( $stats['orphaned_media'] ); ?>" 
                    <?php echo ( intval( $stats['orphaned_media'] ) === 0 ) ? 'disabled' : ''; ?>>
                <?php echo ( intval( $stats['orphaned_media'] ) === 0 ) ? 'No Items' : 'Clean Now'; ?>
            </button>
        </div>

        <!-- Clean Post Revisions -->
        <div class="feature">
            <div class="icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/revisions.svg' ); ?>" alt="Clean Post Revisions">
            </div>
            <h2>Clean Post Revisions (<?php echo intval( $stats['post_revisions'] ); ?>)</h2>
            <p>Remove old revisions to optimize your database.</p>
            <button class="clean-now-btn" 
                    data-action="clean_post_revisions" 
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'acmt_cleanup_action_nonce' ) ); ?>" 
                    data-count="<?php echo intval( $stats['post_revisions'] ); ?>" 
                    <?php echo ( intval( $stats['post_revisions'] ) === 0 ) ? 'disabled' : ''; ?>>
                <?php echo ( intval( $stats['post_revisions'] ) === 0 ) ? 'No Items' : 'Clean Now'; ?>
            </button>
        </div>

        <!-- Clean Transients -->
        <div class="feature">
            <div class="icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/transients.svg' ); ?>" alt="Clean Transients">
            </div>
            <h2>Clean Transients (<?php echo intval( $stats['transients'] ); ?>)</h2>
            <p>Clear expired or unused transients to reduce database clutter.</p>
            <button class="clean-now-btn" 
                    data-action="clean_transients" 
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'acmt_cleanup_action_nonce' ) ); ?>" 
                    data-count="<?php echo intval( $stats['transients'] ); ?>" 
                    <?php echo ( intval( $stats['transients'] ) === 0 ) ? 'disabled' : ''; ?>>
                <?php echo ( intval( $stats['transients'] ) === 0 ) ? 'No Items' : 'Clean Now'; ?>
            </button>
        </div>

        <!-- Clean Spam Comments -->
        <div class="feature">
            <div class="icon">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/icons/spam.svg' ); ?>" alt="Clean Spam Comments">
            </div>
            <h2>Clean Spam Comments (<?php echo intval( $stats['spam_comments'] ); ?>)</h2>
            <p>Delete spam comments detected by Akismet or other filters.</p>
            <button class="clean-now-btn" 
                    data-action="clean_spam_comments" 
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'acmt_cleanup_action_nonce' ) ); ?>" 
                    data-count="<?php echo intval( $stats['spam_comments'] ); ?>" 
                    <?php echo ( intval( $stats['spam_comments'] ) === 0 ) ? 'disabled' : ''; ?>>
                <?php echo ( intval( $stats['spam_comments'] ) === 0 ) ? 'No Items' : 'Clean Now'; ?>
            </button>
        </div>
    </div>

    <div class="scheduled-cleanup">
        <h2>Scheduled Cleanup Settings</h2>

        <!-- Enable Daily Cleanup -->
        <div class="card-container">
            <div class="setting">
            <label for="daily-cleanup" class="cleanup-label">
                <strong>Enable Daily Cleanup</strong>
            </label>
            <label class="toggle-switch">
                <input type="checkbox" id="daily-cleanup" <?php checked( get_option( 'acmt_daily_event' ), '1' ); ?>>
                <span class="slider"></span>
            </label>
            </div>
            <div class="summary">Perform automatic cleanup daily to maintain site performance.</div>
        </div>

        <!-- Enable Weekly Cleanup -->
        <div class="card-container">
            <div class="setting">
            <label for="weekly-cleanup" class="cleanup-label">
                <strong>Enable Weekly Cleanup</strong>
            </label>
            <label class="toggle-switch">
                <input type="checkbox" id="weekly-cleanup" <?php checked( get_option( 'acmt_weekly_event' ), '1' ); ?>>
                <span class="slider"></span>
            </label>
            </div>
            <div class="summary">Perform automatic cleanup weekly to reduce database bloat.</div>
        </div>
    </div>

    <!-- Cleanup Logs -->
    <div class="cleanup-logs">
        <h2><?php esc_html_e( 'Cleanup Logs', 'advanced-clean-master' ); ?></h2>
        <table>
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e( 'Date', 'advanced-clean-master' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Action', 'advanced-clean-master' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Details', 'advanced-clean-master' ); ?></th>
            </tr>
        </thead>
            <tbody>
            <?php
                global $wpdb;

                // Define a unique cache key
                $cache_key = 'acmt_logs_latest_';

                // Try to retrieve cached results
                $logs = wp_cache_get($cache_key);

                if ( $logs === false ) {
                    // Cache miss: Perform the database query
                
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is necessary for retrieving logs, and caching is implemented below.
                    $logs = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}acmt_logs ORDER BY cleaned_on DESC LIMIT %d",
                            10
                        )
                    );
                
                    // Cache the results if logs are found
                    if ( ! empty( $logs ) ) {
                        wp_cache_set( $cache_key, $logs, '', 3600 ); // Cache for 1 hour
                    }
                }                      

                // Output the logs in a table
                if (!empty($logs)) {
                    foreach ($logs as $log) {
                        echo '<tr>
                                <td>' . esc_html( gmdate('Y-m-d', strtotime($log->cleaned_on)) ) . '</td>
                                <td>' . esc_html($log->cleanup_type) . '</td>
                                <td>' . esc_html("Removed {$log->cleaned_count} items") . '</td>
                            </tr>';
                    }
                } else {
                    echo '<tr><td colspan="3">No logs available.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
