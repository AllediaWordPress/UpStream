<?php
/**
 * Setup message asking for review.
 *
 * @author   UpStream
 * @category Admin
 * @package  UpStream/Admin
 * @version  1.0.0
 */

// Exit if accessed directly or already defined.
if ( ! defined( 'ABSPATH' ) || class_exists( 'UpStream_Admin_Reviews' ) ) {
    return;
}

/**
 * Class UpStream_Admin_Reviews
 */
class UpStream_Admin_Reviews {
    /**
     * Checks if it should display the notification and look for actions in the URL.
     */
    public static function check_review_notification() {
        self::handle_actions();

        if ( self::should_display() && self::found_five_projects() ) {
            add_action( 'admin_notices', [ self::class, 'render_notice' ] );

            self::reset_options();
        }
    }

    /**
     * Handles actions triggered by the links in the notification.
     */
    protected static function handle_actions() {
        // We only check GET requests
        if ( $_SERVER['REQUEST_METHOD'] !== 'GET' ) {
            return;
        }

        // Check if the URL is related to any action
        $actions = [ 'no', 'done' ];
        if ( ! isset( $_GET['post_type'] )
             || $_GET['post_type'] !== 'project'
             || ! isset( $_GET['review_action'] )
             || ! in_array( $_GET['review_action'], $actions )
             || ( defined('DOING_AJAX' ) && DOING_AJAX )
             || ( defined('DOING_CRON' ) && DOING_CRON ) ) {

            return;
        }

        update_option( 'upstream_review_status', $_GET['review_action'] );

        // Store the date for asking for the review again later.
        if ( $_GET['review_action'] === 'no' ) {
            update_option( 'upstream_review_timestamp', date( 'Y-m-d' ) );
        }

        wp_redirect( admin_url( 'edit.php?post_type=project' ) );
        exit;
    }

    /**
     * @return bool
     */
    protected static function should_display() {
        if ( (defined( 'DOING_AJAX' ) && DOING_AJAX)
             || (defined( 'DOING_CRON' ) && DOING_CRON)
             || ! is_admin() ) {

            return false;
        }

        $status = get_option( 'upstream_review_status', '' );

        if ( $status === 'done' ) {
            return false;
        }

        // Check when the user answered no. We will ask again after 30 days, if he still uses the plugin.
        if ( $status === 'no' ) {
            $date  = get_option( 'upstream_review_timestamp', date( 'Y-m-d' ) );
            $date  = strtotime( $date . ' +30 days' );
            $today = strtotime( date( 'Y-m-d' ) );

            return $date <= $today;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected static function found_five_projects() {
        $count = wp_count_posts( 'project' );

        if ( is_object( $count ) && isset( $count->publish ) ) {
            return $count->publish >= 5;
        }

        return false;
    }

    /**
     * Reset the options.
     */
    protected static function reset_options() {
        delete_option( 'upstream_review_timestamp' );
        delete_option( 'upstream_review_status' );
    }

    /**
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public static function render_notice() {
        $message = __( 'Hey, I noticed you have created 5 or more projects on %sUpStream%s - that\'s awesome! May I ask you to give it a %s5-star%s rating on WordPress? Just to help us spread the word and boost our motivation.',
            'upstream' );

        $upStream = UpStream::instance();

        echo $upStream->twig_render(
            'five_star_review.twig',
            [
                'message' => esc_html( $message ),
                'link'    => [
                    'yes'  => 'http://wordpress.org/support/plugin/upstream/reviews/#new-post',
                    'done' => admin_url( 'edit.php?post_type=project&review_action=done' ),
                    'no'   => admin_url( 'edit.php?post_type=project&review_action=no' ),
                ],
            ]
        );
    }
}
