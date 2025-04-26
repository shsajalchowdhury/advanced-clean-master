<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class ACMT_Cleanup {

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_acmt_clean_action', array( $this, 'handle_ajax_cleanup' ) );
        add_action( 'wp_ajax_acmt_toggle_scheduled_cleanup', array( $this, 'toggle_scheduled_cleanup' ) );

        // Scheduled cleanup cron jobs
        add_action( 'acmt_daily_event', array( $this, 'run_scheduled_cleanup' ) );
        add_action( 'acmt_weekly_event', array( $this, 'run_scheduled_cleanup' ) );
    }

    /**
     * Add Admin Menu for Cleanup Tool
     */
    public function add_admin_menu() {
        add_menu_page(
            'Advanced Clean Master',
            'Advanced Clean Master',
            'manage_options',
            'acmt-clean-master',
            array( $this, 'cleanup_tool_page' ),
            'dashicons-trash',
            25
        );
    }

    /**
     * Enqueue Admin Assets (CSS & JS)
     */
    public function enqueue_assets() {
        wp_enqueue_style( 'acmt-styles', ACMT_URL . 'assets/css/styles.css', array(), '1.0.0' );
        wp_enqueue_script( 'acmt-js', ACMT_URL . 'assets/js/main.js', array( 'jquery' ), '1.0.0', true );
        wp_localize_script( 'acmt-js', 'acmtCleanupAjax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'acmt_cleanup_action_nonce' ),
        ) );
    }

    /**
     * Render Admin Dashboard Page
     */
    public function cleanup_tool_page() {
        $stats = $this->get_cleanup_stats(); // Fetch stats
        include ACMT_PATH . 'views/cleanup-tool-dashboard.php'; // Include view file
    }

    /**
     * Handle AJAX Cleanup Actions
     */
    public function handle_ajax_cleanup() {
    
        check_ajax_referer( 'acmt_cleanup_action_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized request.' ) );
            return;
        }
    
        if ( isset( $_POST['cleanup_action'] ) ) {
            $action = sanitize_text_field( wp_unslash( $_POST['cleanup_action'] ) );
        } else {
            wp_send_json_error( array( 'message' => 'Invalid request. Action not specified.' ) );
            return;
        }
        

        $count = 0;
        switch ( $action ) {
            case 'clean_drafts':
                $count = $this->clean_drafts();
                break;
            case 'clean_trashed_posts':
                $count = $this->clean_trashed_posts();
                break;
            case 'clean_unapproved_comments':
                $count = $this->clean_unapproved_comments();
                break;
            case 'clean_orphaned_media':
                $count = $this->clean_orphaned_media();
                break;
            case 'clean_post_revisions':
                $count = $this->clean_post_revisions();
                break;
            case 'clean_transients':
                $count = $this->clean_transients();
                break;
            case 'clean_spam_comments':
                $count = $this->clean_spam_comments();
                break;
            case 'optimize_database':
                $count = $this->optimize_database();
                break;
            default:
                wp_send_json_error( array( 'message' => 'Invalid action.' ) );
        }

        $this->insert_acmt_log( ucfirst( str_replace( '_', ' ', $action ) ), $count );
        wp_send_json_success( array( 'message' => "Cleaned {$count} items successfully." ) );
    }

    /**
     * Scheduled Cleanup Toggle
     */
    public function toggle_scheduled_cleanup() {
        check_ajax_referer('acmt_cleanup_action_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized request.'));
            return;
        }

        // Validate and sanitize 'schedule'
        if (isset($_POST['schedule'])) {
            $schedule = sanitize_text_field(wp_unslash($_POST['schedule']));
            if (!in_array($schedule, array('daily', 'weekly'))) {
                wp_send_json_error(array('message' => 'Invalid schedule type.'));
                return;
            }
        } else {
            wp_send_json_error(array('message' => 'Schedule not specified.'));
            return;
        }

        // Validate and sanitize 'enabled'
        if (isset($_POST['enabled'])) {
            $enabled = filter_var(wp_unslash($_POST['enabled']), FILTER_VALIDATE_BOOLEAN);
        } else {
            wp_send_json_error(array('message' => 'Enabled status not specified.'));
            return;
        }

        $hook = ($schedule === 'daily') ? 'acmt_daily_event' : 'acmt_weekly_event';
        $option = "acmt_clean_master_{$schedule}";

        if ($enabled) {
            // Clear any existing schedule first to prevent duplicates
            wp_clear_scheduled_hook($hook);
            
            // Schedule the new event
            $start_time = strtotime('tomorrow 00:00:00'); // Start at midnight
            if ($schedule === 'weekly') {
                $start_time = strtotime('next monday 00:00:00'); // Start next Monday at midnight
            }
            
            $scheduled = wp_schedule_event($start_time, $schedule, $hook);
            
            if ($scheduled === false) {
                wp_send_json_error(array('message' => 'Failed to schedule cleanup event.'));
                return;
            }
            
            update_option($option, 1);
            wp_send_json_success(array(
                'message' => sprintf(
                    '%s cleanup scheduled successfully. First cleanup will run at %s.', 
                    ucfirst($schedule),
                    wp_date('Y-m-d H:i:s', $start_time)
                )
            ));
        } else {
            wp_clear_scheduled_hook($hook);
            update_option($option, 0);
            wp_send_json_success(array('message' => ucfirst($schedule) . ' cleanup disabled successfully.'));
        }
    }
    

    /**
    * Get Cleanup Stats
    */
    private function get_cleanup_stats() {
        global $wpdb;
        
        // Get count of database tables
        $tables_count = $wpdb->get_var("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE '{$wpdb->prefix}%'");
        
        // Check if database was recently optimized (within last 24 hours)
        $last_optimization = get_option( 'acmt_last_db_optimization', 0 );
        $db_recently_optimized = ( time() - $last_optimization ) < DAY_IN_SECONDS;
        
        return array(
        'drafts'              => count( get_posts( array( 'post_status' => 'draft', 'numberposts' => -1 ) ) ),
        'trashed'             => count( get_posts( array( 'post_status' => 'trash', 'numberposts' => -1 ) ) ),
        'unapproved_comments' => wp_count_comments()->moderated,
        'orphaned_media'      => $this->get_orphaned_media_count(),
        'post_revisions'      => count( get_posts( array( 'post_type' => 'revision', 'numberposts' => -1 ) ) ),
        'transients'          => $this->get_transient_count(),
        'spam_comments'       => wp_count_comments()->spam,
        'database_tables'     => absint($tables_count),
        'db_optimized'        => $db_recently_optimized,
    );
    }
    private function clean_drafts() {
        return $this->delete_posts_by_status( 'draft' );
    }

    private function clean_trashed_posts() {
        return $this->delete_posts_by_status( 'trash' );
    }

    private function clean_unapproved_comments() {
        // Fetch all unapproved comments
        $unapproved_comments = get_comments( array(
            'status' => 'hold', // "hold" is the status for unapproved comments
            'number' => 0,      // Get all matching comments
        ) );
    
        // Delete each unapproved comment
        $deleted_count = 0;
        foreach ( $unapproved_comments as $comment ) {
            if ( wp_delete_comment( $comment->comment_ID, true ) ) {
                $deleted_count++;
            }
        }
    
        return $deleted_count; // Return the number of deleted comments
    }
    

/**
 * Clean Orphaned Media
 */
    private function clean_orphaned_media() {
        // Cache key for orphaned media
        $cache_key = 'orphaned_media_ids';
        $attachments = wp_cache_get( $cache_key );

        if ( false === $attachments ) {
            // Use get_posts() instead of direct SQL query
            $attachments = get_posts( array(
                'post_type'   => 'attachment',
                'post_parent' => 0,
                'fields'      => 'ids',
                'numberposts' => -1,
            ) );

            // Cache the results to avoid redundant queries
            wp_cache_set( $cache_key, $attachments );
        }

        if ( empty( $attachments ) ) {
            return 0;
        }

        // Ensure all attachment IDs are integers
        $attachments = array_map( 'intval', $attachments );

        // Used attachments in content
        $used_in_content_cache_key = 'used_in_content_ids';
        $used_in_content = wp_cache_get( $used_in_content_cache_key );

        if ( false === $used_in_content ) {
            $used_in_content = array();

            // Search for attachments in post content
            foreach ( $attachments as $attachment_id ) {
                $file = esc_sql( get_post_meta( $attachment_id, '_wp_attached_file', true ) );

                if ( ! empty( $file ) ) {
                    $query = new WP_Query( array(
                        's'           => $file,
                        'post_type'   => 'any',
                        'fields'      => 'ids',
                        'numberposts' => -1,
                    ) );

                    if ( ! empty( $query->posts ) ) {
                        $used_in_content[] = $attachment_id;
                    }
                }
            }

            // Cache the result
            wp_cache_set( $used_in_content_cache_key, $used_in_content );
        }

        // Used attachments as featured images
        $used_as_featured_cache_key = 'used_as_featured_ids';
        $used_as_featured = wp_cache_get( $used_as_featured_cache_key );

        if ( false === $used_as_featured ) {
            $used_as_featured = get_posts( array(  
                'post_type'   => 'any',
                'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- meta_query is necessary for retrieving posts with specific metadata.
                    array(
                        'key'     => '_thumbnail_id',
                        'value'   => $attachments,
                        'compare' => 'IN',
                    ),
                ),
                'fields'      => 'ids',
                'numberposts' => -1,
                'no_found_rows' => true, // Optimize query
            ) );
        
            // Cache the result
            wp_cache_set( $used_as_featured_cache_key, $used_as_featured );
        }        

        // Merge all used attachments and ensure IDs are integers
        $used_attachments = array_map( 'intval', array_unique( array_merge( $used_in_content, $used_as_featured ) ) );

        // Find orphaned attachments
        $orphaned_attachments = array_diff( $attachments, $used_attachments );

        // Delete only the counted orphaned attachments
        $deleted_count = 0;
        if ( ! empty( $orphaned_attachments ) ) {
            foreach ( $orphaned_attachments as $attachment_id ) {
                if ( get_post_type( $attachment_id ) === 'attachment' ) { // Verify it's an attachment
                    wp_delete_attachment( $attachment_id, true );
                    $deleted_count++;
                }
            }
        }

        return $deleted_count;
    }

    private function clean_post_revisions() {
        return $this->delete_posts_by_status( 'revision' );
    }

    private function clean_spam_comments() {
        // Check for cached spam comments
        $cache_key = 'spam_comment_ids';
        $spam_comments = wp_cache_get( $cache_key );
    
        if ( false === $spam_comments ) {
            // Use get_comments() to retrieve spam comments
            $spam_comments = get_comments( array(
                'status' => 'spam',
                'fields' => 'ids',
                'number' => 0, // Fetch all spam comments
            ) );
    
            // Cache the spam comment IDs
            wp_cache_set( $cache_key, $spam_comments );
        }
    
        // Delete spam comments if any exist
        if ( ! empty( $spam_comments ) ) {
            foreach ( $spam_comments as $comment_id ) {
                wp_delete_comment( $comment_id, true );
            }
        }
    
        return count( $spam_comments );
    }
    

    private function delete_posts_by_status( $status ) {
        $posts = get_posts( array( 'post_status' => $status, 'numberposts' => -1 ) );
        foreach ( $posts as $post ) {
            wp_delete_post( $post->ID, true );
        }
        return count( $posts );
    }

    /**
    * Get Count of Orphaned Media
    */
    private function get_orphaned_media_count() {
        // Cache key for orphaned media count
        $cache_key = 'orphaned_media_count';
        $orphaned_media_count = wp_cache_get( $cache_key );
        if ( false !== $orphaned_media_count ) {
            return $orphaned_media_count;
        }
    
        // Step 1: Fetch all attachment IDs without a post_parent
        $attachments = get_posts( array(
            'post_type'   => 'attachment',
            'post_parent' => 0,
            'fields'      => 'ids',
            'numberposts' => -1,
        ) );
    
        if ( empty( $attachments ) ) {
            wp_cache_set( $cache_key, 0 ); // Cache the result
            return 0;
        }
    
        // Ensure all attachment IDs are integers
        $attachments = array_map( 'intval', $attachments );
    
        // Step 2: Find attachments used in post content
        $used_in_content = array();
        foreach ( $attachments as $attachment_id ) {
            // Get the file path or URL
            $file = get_post_meta( $attachment_id, '_wp_attached_file', true );
            if ( ! empty( $file ) ) {
                // Search for the file in post content
                $query = new WP_Query( array(
                    's'           => basename( $file ), // Search for the file name
                    'post_type'   => 'any',
                    'fields'      => 'ids',
                    'numberposts' => -1,
                    'no_found_rows' => true, // Optimize query
                ) );
    
                if ( ! empty( $query->posts ) ) {
                    $used_in_content[] = $attachment_id;
                }
    
                // Clean up the WP_Query instance
                wp_reset_postdata();
            }
        }
    
        // Step 3: Find attachments used as featured images
        $used_as_featured = get_posts( array(
            'post_type'   => 'any',
            'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- meta_query is necessary for retrieving posts with specific metadata.
                array(
                    'key'     => '_thumbnail_id',
                    'value'   => $attachments,
                    'compare' => 'IN',
                ),
            ),
            'fields'      => 'meta_value',
            'numberposts' => -1,
            'no_found_rows' => true, // Optimize query
        ) );
    
        // Fix: Handle cases where objects might appear in the arrays
        $used_in_content_ids = array_map( function( $item ) {
            return is_object( $item ) && isset( $item->ID ) ? intval( $item->ID ) : intval( $item );
        }, $used_in_content );
    
        $used_as_featured_ids = array_map( function( $item ) {
            return is_object( $item ) && isset( $item->ID ) ? intval( $item->ID ) : intval( $item );
        }, $used_as_featured );
    
        // Merge and ensure all IDs are integers
        $used_attachments = array_map( 'intval', array_merge( $used_in_content_ids, $used_as_featured_ids ) );
    
        // Step 4: Identify unused (orphaned) attachments
        $orphaned_attachments = array_diff( $attachments, $used_attachments );
    
        // Cache the orphaned media count
        $orphaned_media_count = count( $orphaned_attachments );
        wp_cache_set( $cache_key, $orphaned_media_count );
    
        return $orphaned_media_count;
    }
    


    /**
     * Get Count of Expired Transients
     */
    private function get_transient_count() {
        // Define a cache key
        $cache_key = 'expired_transient_count';

        // Attempt to retrieve the count from the cache
        $cached_count = wp_cache_get( $cache_key );
        if ( false !== $cached_count ) {
            return (int) $cached_count;
        }

        global $wpdb;

        // Query to count expired transients
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$wpdb->options} 
            WHERE option_name LIKE %s 
            AND option_value < %d",
            $wpdb->esc_like('_transient_timeout_') . '%',
            time()
        ) );

        // Cache the count for 1 hour (3600 seconds)
        $count = absint($count);
        wp_cache_set( $cache_key, $count, '', 3600 );

        return $count;
    }


    /**
     * Clean Expired Transients
    */
    private function clean_transients() {
        global $wpdb;

        // First, get the names of expired transients
        $expired_transients = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT REPLACE(option_name, '_transient_timeout_', '_transient_') 
                FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_') . '%',
                time()
            )
        );

        if (empty($expired_transients)) {
            return 0;
        }

        // Delete the transient timeout entries
        $deleted_timeouts = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_') . '%',
                time()
            )
        );

        // Delete the corresponding transient value entries
        $in_clause = "'" . implode("','", array_map('esc_sql', $expired_transients)) . "'";
        $deleted_values = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name IN ($in_clause)"
        );

        return absint($deleted_timeouts);
    }




    private function insert_acmt_log( $action, $count ) {
        // Cache key to prevent duplicate inserts
        $cache_key = 'insert_acmt_log' . md5( $action . $count );

        // Check if this log entry already exists in cache
        if ( wp_cache_get( $cache_key ) ) {
            return;
        }

        global $wpdb;

        // Sanitize inputs
        $cleanup_type = sanitize_text_field( $action );
        $cleaned_count = absint( $count );
        $cleaned_on = current_time( 'mysql' );

        // Insert the log into the database
        
            $wpdb->insert( //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->prefix . 'acmt_logs',
            array(
                'cleanup_type'  => $cleanup_type,
                'cleaned_count' => $cleaned_count,
                'cleaned_on'    => $cleaned_on,
            ),
            array(
                '%s', // Data type for 'cleanup_type'
                '%d', // Data type for 'cleaned_count'
                '%s', // Data type for 'cleaned_on'
            )
        );

        // Cache the action to prevent duplicate inserts
        wp_cache_set( $cache_key, true, '', 3600 ); // Cache for 1 hour
    }




/**
 * Calculate Total Space Saved
 */
    public function calculate_total_space_saved() {
        global $wpdb;

        // Step 1: Fetch total space saved from cleanup logs
        $cache_key = 'acmt_space_saved';
        $total_space_saved = wp_cache_get( $cache_key );

        if ( false === $total_space_saved ) {
            $total_space_saved = (int) get_option( 'acmt_space_saved', 0 );

            // Step 2: Calculate orphaned media space saved
            $orphaned_media_space = 0;

            // Use get_posts() instead of direct query
            $media_ids = get_posts( array(
                'post_type'   => 'attachment',
                'post_parent' => 0,
                'fields'      => 'ids',
                'numberposts' => -1,
            ) );

            foreach ( $media_ids as $attachment_id ) {
                $file = get_post_meta( $attachment_id, '_wp_attached_file', true );
                $file_path = wp_get_upload_dir()['basedir'] . '/' . $file;
                if ( file_exists( $file_path ) ) {
                    $orphaned_media_space += filesize( $file_path );
                }
            }

            // Add orphaned media space saved to total space saved
            $total_space_saved += $orphaned_media_space;

            // Step 3: Save the updated value to the database and cache
            update_option( 'acmt_space_saved', $total_space_saved );
            wp_cache_set( $cache_key, $total_space_saved, '', 3600 ); // Cache for 1 hour
        }

        return $total_space_saved; // Return space in bytes
    }

    /**
     * Run Scheduled Cleanup
     */
    public function run_scheduled_cleanup() {
        // Array of cleanup actions and their methods
        $cleanup_actions = array(
            'Drafts' => 'clean_drafts',
            'Trashed Posts' => 'clean_trashed_posts',
            'Unapproved Comments' => 'clean_unapproved_comments',
            'Orphaned Media' => 'clean_orphaned_media',
            'Post Revisions' => 'clean_post_revisions',
            'Transients' => 'clean_transients',
            'Spam Comments' => 'clean_spam_comments',
            'Database Optimization' => 'optimize_database'
        );

        // Run each cleanup action and log the results
        foreach ($cleanup_actions as $action_name => $method) {
            if (method_exists($this, $method)) {
                $count = $this->$method();
                if ($count > 0) {
                    $this->insert_acmt_log($action_name, $count);
                }
            }
        }

        // Update last run time
        update_option('acmt_last_cleanup_run', current_time('timestamp'));
    }

    /**
     * Optimize Database Tables
     * 
     * @return int Number of tables optimized
     */
    private function optimize_database() {
        global $wpdb;
        
        // Get all tables with the WordPress prefix
        $tables = $wpdb->get_results( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );
        
        if ( empty( $tables ) ) {
            return 0;
        }
        
        $optimized_count = 0;
        
        foreach ( $tables as $table ) {
            $table_name = reset( $table );
            
            // Skip the logs table to avoid optimization during active use
            if ( $table_name === $wpdb->prefix . 'acmt_logs' ) {
                continue;
            }
            
            // Run the optimize table query
            $result = $wpdb->query( "OPTIMIZE TABLE {$table_name}" );
            
            if ( false !== $result ) {
                $optimized_count++;
            }
        }
        
        // Store the last optimization time to prevent immediate re-optimization
        update_option( 'acmt_last_db_optimization', current_time( 'timestamp' ) );
        
        return $optimized_count;
    }
}
