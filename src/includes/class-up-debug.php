<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    return;
}

/**
 * UpStream_Debug Class
 *
 * @since 1.0.0
 */
class UpStream_Debug {
    const FILE = 'debug-upstream.log';

    const PAGE_SLUG = 'upstream_debug_log';

    const ACTION_DELETE_LOG = 'delete_log';

    protected static $path;

    protected static $initialized = false;

    protected static $messages = [];

    /**
     * Write the given message into the log file.
     *
     * @param $message
     */
    public static function write( $message, $id = null ) {
        if ( ! static::$initialized ) {
            static::init();
        }

        // Make sure we have a string to write.
        if ( ! is_string( $message ) ) {
            $message = print_r( $message, true );
        }

        // Prepend the id, if set.
        if ( ! empty( $id ) ) {
            $message = $id . ' --> ' . $message;
        }

        // Add the timestamp to the message.
        $message = sprintf( '[%s] %s', date( 'Y-m-d H:i:s T O' ), $message ) . "\n";

        error_log( $message, 3, static::$path );
    }

    /**
     * Get things going
     *
     * @since 1.0.0
     */
    public static function init() {
        if ( ! static::is_enabled() ) {
            return;
        }

        static::$path = str_replace( '//', '/', WP_CONTENT_DIR . '/' . static::FILE );

        // Admin bar.
        add_action( 'admin_bar_menu', [ 'UpStream_Debug', 'admin_bar_menu' ], 99 );

        // Admin menu.
        add_action( 'admin_menu', [ 'UpStream_Debug', 'admin_menu' ] );

        static::$initialized = true;
    }

    /**
     * Returns true if debug is enabled in the UpStream settings.
     *
     * @return bool
     */
    public static function is_enabled() {
        $option = get_option( 'upstream_general' );

        return array_key_exists( 'debug', $option )
               && ! empty( $option['debug'] )
               && (int) $option['debug'][0] === 1;
    }

    public static function admin_bar_menu() {
        global $wp_admin_bar;

        $args = [
            'id'    => 'upstream_debug',
            'title' => __( 'UpStream Debug Log', 'upstream' ),
            'href'  => admin_url( 'admin.php?page=' . static::PAGE_SLUG ),
        ];

        $wp_admin_bar->add_menu( $args );
    }

    public static function admin_menu() {
        // Admin menu.
        add_submenu_page(
            admin_url( 'admin.php?page=' . static::PAGE_SLUG ),
            __( 'Debug Log' ),
            __( 'Debug Log' ),
            'activate_plugins',
            'upstream_debug_log',
            [ 'UpStream_Debug', 'view_log_page' ]
        );
    }

    public static function view_log_page() {
        static::handle_actions();

        $is_log_found = file_exists( static::$path );
        $content      = '';

        $debug_data = [
            'php'       => [
                'date_default_timezone_get' => date_default_timezone_get(),
                'date(e)'                   => date( 'e' ),
                'date(T)'                   => date( 'T' ),
            ],
            'wordpress' => [
                'date_format'     => get_option( 'date_format' ),
                'time_format'     => get_option( 'time_format' ),
                'timezone_string' => get_option( 'timezone_string' ),
                'gmt_offset'      => get_option( 'gmt_offset' ),
            ],
        ];

        $context = [
            'label'         => [
                'title'             => __( 'UpStream Debug Log', 'upstream' ),
                'file_info'         => __( 'File info', 'upstream' ),
                'path'              => __( 'Path', 'upstream' ),
                'log_content'       => __( 'Log content', 'upstream' ),
                'size'              => __( 'Size', 'upstream' ),
                'creation_time'     => __( 'Created on', 'upstream' ),
                'modification_time' => __( 'Modified on', 'upstream' ),
                'delete_file'       => __( 'Delete file', 'upstream' ),
                'debug_data'        => __( 'Debug data', 'upstream' ),
                'log_file'          => __( 'Log File', 'upstream' ),
            ],
            'message'       => [
                'log_not_found'       => __( 'Log file not found.', 'upstream' ),
                'contact_support_tip' => __( 'If you see any error or look for information regarding UpStream, please don\'t hesitate to contact the support team. E-mail us:',
                    'upstream' ),
                'click_to_delete'     => __( 'Click to delete the log file. Be careful, this operation can not be undone. ',
                    'upstream' ),
            ],
            'contact_email' => 'help@upstreamplugin.com',
            'link_delete'   => admin_url(
                sprintf( 'admin.php?page=%s&action=%s&_wpnonce=%s',
                    static::PAGE_SLUG,
                    static::ACTION_DELETE_LOG,
                    wp_create_nonce( static::ACTION_DELETE_LOG )
                )
            ),
            'is_log_found'  => $is_log_found,
            'file'          => [
                'path'              => static::$path,
                'size'              => $is_log_found ? round( filesize( static::$path ) / 1024, 2 ) : 0,
                'modification_time' => $is_log_found ? date( 'Y-m-d H:i:s T O', filemtime( static::$path ) ) : '',
                'content'           => $is_log_found ? file_get_contents( static::$path ) : '',
            ],
            'debug_data'    => print_r( $debug_data, true ),
            'messages'      => static::$messages,
        ];

        echo UpStream()->twig_render( 'view_log.twig', $context );
    }

    protected static function handle_actions() {
        // Are we on the correct page?
        if ( ! array_key_exists( 'page', $_GET ) || $_GET['page'] !== static::PAGE_SLUG ) {
            return;
        }

        // Do we have an action?
        if ( ! array_key_exists( 'action', $_GET ) || empty( $_GET['action'] ) ) {
            return;
        }

        $action = preg_replace( '/[^a-z0-9_\-]/i', '', $_GET['action'] );

        // Do we have a nonce?
        if ( ! array_key_exists( '_wpnonce', $_GET ) || empty( $_GET['_wpnonce'] ) ) {
            static::$messages[] = __( 'Action nonce not found.', 'upstream' );

            return;
        }

        // Check the nonce.
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], $action ) ) {
            static::$messages[] = __( 'Invalid action nonce.', 'upstream' );

            return;
        }

        if ( $action === static::ACTION_DELETE_LOG ) {
            if ( file_exists( static::$path ) ) {
                unlink( static::$path );
            }
        }

        wp_redirect( admin_url( 'admin.php?page=' . static::PAGE_SLUG ) );
    }
}
