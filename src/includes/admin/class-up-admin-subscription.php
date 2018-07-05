<?php
/**
 * Setup ad asking to subscribe do get discount.
 *
 * @author   UpStream
 * @category Admin
 * @package  UpStream/Admin
 * @version  1.0.0
 */

// Exit if accessed directly or already defined.
if ( ! defined( 'ABSPATH' ) || class_exists( 'UpStream_Admin_Subscription' ) ) {
    return;
}

/**
 * Class UpStream_Admin_Subscription
 */
class UpStream_Admin_Subscription {
    /**
     * Checks if it should display the notification and look for actions in the URL.
     */
    public static function check_ad() {
        if ( self::should_display() ) {
            add_filter( 'upstream_options_show_sidebar', [ self::class, 'filter_show_options_sidebar' ] );
            add_action( 'upstream_options_sidebar', [ self::class, 'render_ad' ] );
        }
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

        return ! UpStream_Options_Extensions::thereAreInstalledExtensions();
    }

    /**
     * @param $show_sidebar
     *
     * @return bool
     */
    public static function filter_show_options_sidebar( $show_sidebar ) {
        $show_sidebar = self::should_display();

        return $show_sidebar;
    }

    /**
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public static function render_ad() {
        $message = __( 'Hey, I noticed you have created 5 or more projects on %sUpStream%s - that\'s awesome! May I ask you to give it a %s5-star%s rating on WordPress? Just to help us spread the word and boost our motivation.',
            'upstream' );

        $upStream = UpStream::instance();

        echo $upStream->twig_render(
            'subscription_ad.twig',
            [
                'image_src' => get_site_url() . '/wp-content/plugins/upstream/includes/admin/assets/img/subscription-image.jpg',
            ]
        );
    }
}
