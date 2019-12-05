<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! class_exists('UpStream_Options_Import')) :

    /**
     * CMB2 Theme Options
     *
     * @version 0.1.0
     */
    class UpStream_Options_Import
    {

        /**
         * Array of metaboxes/fields
         *
         * @var array
         */
        public $id = 'upstream_import';

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
            $this->title      = __('Import', 'upstream');
            $this->menu_title = $this->title;
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
                    'id'         => $this->id,
                    'title'      => $this->title,
                    'menu_title' => $this->menu_title,
                    'desc'       => $this->description,
                    'show_on'    => ['key' => 'options-page', 'value' => [$this->id],],
                    'show_names' => true,
                    'fields'     => [
                        [
                            'before_row' => sprintf(
                                '<h3>%s</h3><p>%s</p>',
                                __('Import from File', 'upstream'),
                                __('', 'upstream')
                            ),
                            'name'    => __('File to Import', 'upstream'),
                            'id'      => 'import_file',
                            'type'    => 'file',
                            'desc'    => __(
                                '',
                                'upstream'
                            ),
                        ],
                        [
                            'name'    => __('Perform Import', 'upstream'),
                            'id'      => 'perform_import',
                            'type'    => 'up_button',
                            'label'   => __('Perform Import', 'upstream'),
                            'desc'    => __(
                                '',
                                'upstream'
                            ),
                            'onclick' => 'upstream_import_file(event);',
                            'nonce'   => wp_create_nonce('upstream_import_file'),
                        ],

                    ],
                ]
            );

            return $options;
        }
    }

endif;
