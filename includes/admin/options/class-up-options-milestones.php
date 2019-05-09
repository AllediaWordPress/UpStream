<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! class_exists('UpStream_Options_Milestones')) :

    /**
     * CMB2 Theme Options
     *
     * @version 0.1.0
     */
    class UpStream_Options_Milestones
    {

        /**
         * Array of metaboxes/fields
         *
         * @var array
         */
        public $id = 'upstream_milestones';

        /**
         * Page title
         *
         * @var string
         */
        protected $title = '';

        /**
         * Menu Title
         *
         * @var string
         */
        protected $menu_title = '';

        /**
         * Menu Title
         *
         * @var string
         */
        protected $description = '';

        /**
         * Holds an instance of the object
         *
         * @var Myprefix_Admin
         **/
        public static $instance = null;

        /**
         * Constructor
         *
         * @since 0.1.0
         */
        public function __construct()
        {
            // Set our title
            $this->title      = upstream_milestone_label_plural();
            $this->menu_title = $this->title;
            //$this->description = sprintf( __( '%s Description', 'upstream' ), upstream_milestone_label() );
        }

        /**
         * Returns the running object
         *
         * @return Myprefix_Admin
         **/
        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Add the options metabox to the array of metaboxes
         *
         * @since  0.1.0
         */
        public function options()
        {
            $options = apply_filters(
                $this->id . '_option_fields',
                [
                    'id'         => $this->id, // upstream_milestones
                    'title'      => $this->title,
                    'menu_title' => $this->menu_title,
                    'desc'       => $this->description,
                    'show_on'    => ['key' => 'options-page', 'value' => [$this->id],],
                    'show_names' => true,
                    'fields'     => [
                        [
                            'name' => upstream_milestone_label_plural(),
                            'id'   => 'milestone_title',
                            'type' => 'title',
                        ],
                        [
                            'name'        => 'Milestone Categories',
                            'id'          => 'enable_milestone_categories',
                            'type'        => 'radio',
                            'description' => '',
                            'options'     => [
                                '1' => __('Enabled', 'upstream'),
                                '0' => __('Disabled', 'upstream'),
                            ],
                            'default'     => '0',
                        ],
                    ],
                ]
            );

            return $options;
        }
    }

endif;
