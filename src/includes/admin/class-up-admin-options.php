<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'UpStream_Admin_Options' ) ) :

    /**
     * CMB2 Theme Options
     *
     * @version 0.1.0
     */
    class UpStream_Admin_Options {

        /**
         * Array of metaboxes/fields
         *
         * @var array
         */
        public $option_metabox = [];

        /**
         * Array of metaboxes/fields
         *
         * @var array
         */
        public $metabox_id = '';

        /**
         * Options Page title
         *
         * @var string
         */
        protected $title = '';

        /**
         * Options Page title
         *
         * @var string
         */
        protected $menu_title = '';

        /**
         * Options Tab Pages
         *
         * @var array
         */
        public $options_pages = [];

        /**
         * Holds an instance of the object
         *
         * @var Myprefix_Admin
         **/
        private static $instance = null;

        /**
         * Constructor
         *
         * @since 0.1.0
         */
        private function __construct() {
            // Set our title
            $this->menu_title = __( 'UpStream', 'upstream' );
            $this->title      = __( 'UpStream Project Manager', 'upstream' );
        }

        /**
         * Returns the running object
         *
         * @return Myprefix_Admin
         **/
        public static function get_instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
                self::$instance->hooks();
            }

            return self::$instance;
        }

        /**
         * Initiate our hooks
         *
         * @since 0.1.0
         */
        public function hooks() {
            add_action( 'admin_init', [ $this, 'init' ] );
            add_action( 'admin_menu', [ $this, 'add_options_pages' ] );
        }

        /**
         * Register our setting to WP
         *
         * @since  0.1.0
         */
        public function init() {
            $option_tabs = self::option_fields();
            foreach ( $option_tabs as $index => $option_tab ) {
                register_setting( $option_tab['id'], $option_tab['id'] );
            }

        }

        public function add_options_pages() {

            $option_tabs = self::option_fields();

            foreach ( $option_tabs as $index => $option_tab ) {
                if ( $index == 0 ) {

                    $this->options_pages[] = add_menu_page( $this->title, $this->menu_title, 'manage_upstream',
                        $option_tab['id'], [ $this, 'admin_page_display' ], 'dashicons-arrow-up-alt'
                    ); //Link admin menu to first tab

                    add_submenu_page( $option_tabs[0]['id'], $this->menu_title, $option_tab['menu_title'],
                        'manage_upstream', $option_tab['id'],
                        [ $this, 'admin_page_display' ] ); //Duplicate menu link for first submenu page
                } else {
                    $this->options_pages[] = add_submenu_page( $option_tabs[0]['id'], $this->menu_title,
                        $option_tab['menu_title'], 'manage_upstream', $option_tab['id'],
                        [ $this, 'admin_page_display' ] );
                }
            }

            foreach ( $this->options_pages as $page ) {
                // Include CMB CSS in the head to avoid FOUC
                add_action( "admin_print_styles-{$page}", [ 'CMB2_hookup', 'enqueue_cmb_css' ] );
            }
        }

        /**
         * Admin page markup. Mostly handled by CMB2
         *
         * @since  0.1.0
         */
        public function admin_page_display() {

            $option_tabs = apply_filters( 'upstream_option_metaboxes', self::option_fields() ); //get all option tabs
            $tab_forms   = [];

            /**
             * Filter to return a boolean value to say if it should display or not a sidebar.
             *
             * @var bool
             */
            $show_sidebar = apply_filters( 'upstream_options_show_sidebar', false );
            ?>

            <div class="wrap upstream_options <?php echo $show_sidebar ? 'upstream_with_sidebar container' : ''; ?>">

                <h2><?php echo $this->title; ?></h2>

                <div class="row">
                    <div class="<?php echo $show_sidebar ? 'col-md-8' : 'col-md-12'; ?>">
                        <!-- Options Page Nav Tabs -->
                        <h2 class="nav-tab-wrapper">
                            <?php foreach ( $option_tabs as $option_tab ) :
                                $tab_slug = $option_tab['id'];
                                $nav_class = 'nav-tab';
                                if ( $tab_slug == $_GET['page'] ) {
                                    $nav_class   .= ' nav-tab-active'; //add active class to current tab
                                    $tab_forms[] = $option_tab; //add current tab to forms to be rendered
                                }
                                ?>
                                <a class="<?php echo esc_attr( $nav_class ); ?>"
                                   href="<?php esc_url( menu_page_url( $tab_slug ) ); ?>"><?php esc_attr_e( $option_tab['title'],
                                        'upstream' ); ?></a>
                            <?php endforeach; ?>
                        </h2>
                        <!-- End of Nav Tabs -->

                        <?php foreach ( $tab_forms as $tab_form ) : //render all tab forms (normaly just 1 form) ?>
                            <div id="<?php esc_attr_e( $tab_form['id'] ); ?>" class="cmb-form group">
                                <div class="metabox-holder">
                                    <div class="postbox pad">
                                        <h3 class="title"><?php //esc_html_e($tab_form['title'], 'upstream'); ?></h3>
                                        <div class="desc"><?php echo $tab_form['desc'] ?></div>
                                        <?php cmb2_metabox_form( $tab_form, $tab_form['id'] ); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ( $show_sidebar ) : ?>
                        <div class="col-md-3">
                            <?php do_action( 'upstream_options_sidebar' ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
        }


        /**
         * Add the options metabox to the array of metaboxes
         *
         * @since  0.1.0
         */
        public function option_fields() {
            // hook in our save notices
            //add_action( "cmb2_save_options-page_fields_{$this->metabox_id}", array( $this, 'settings_notices' ), 10, 2 );

            // Only need to initiate the array once per page-load
            if ( ! empty( $this->option_metabox ) ) {
                return $this->option_metabox;
            }

            $general_options        = new UpStream_Options_General();
            $this->option_metabox[] = $general_options->options();

            $project_options        = new UpStream_Options_Projects();
            $this->option_metabox[] = $project_options->options();

            $milestone_options      = new UpStream_Options_Milestones();
            $this->option_metabox[] = $milestone_options->options();

            if ( ! upstream_disable_tasks() ) {
                $task_options           = new UpStream_Options_Tasks();
                $this->option_metabox[] = $task_options->options();
            }

            if ( ! upstream_disable_bugs() ) {
                $bug_options            = new UpStream_Options_Bugs();
                $this->option_metabox[] = $bug_options->options();
            }

            $ext_options            = new UpStream_Options_Extensions();
            $this->option_metabox[] = $ext_options->getOptions();

            return apply_filters( 'upstream_option_metaboxes', $this->option_metabox );

        }

        /**
         * Public getter method for retrieving protected/private variables
         *
         * @since  0.1.0
         *
         * @param  string $field Field to retrieve
         *
         * @return mixed          Field value or exception is thrown
         */
        public function __get( $field ) {

            // Allowed fields to retrieve
            if ( in_array( $field, [ 'key', 'fields', 'title', 'options_pages' ], true ) ) {
                return $this->{$field};
            }
            if ( 'option_metabox' === $field ) {
                return $this->option_fields();
            }

            throw new Exception( 'Invalid property: ' . $field );
        }

    }

    /**
     * Helper function to get/return the UpStream_Admin_Options object
     *
     * @since  0.1.0
     * @return UpStream_Admin_Options object
     */
    function upstream_admin_options() {
        return UpStream_Admin_Options::get_instance();
    }


    // Get it started
    upstream_admin_options();


endif;
