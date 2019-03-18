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
                            'desc' => sprintf(
                                __(
                                    'Create your %1s and choose their colors. You can create an unlimited number and they can be used across any and all %2s.<br>They will appear in the %3s dropdown within each %4s.<br><strong>Tip:</strong> We think it works best to keep %5s colors to various shades of the one color, to help keep things looking neat and organized.',
                                    'upstream'
                                ),
                                upstream_milestone_label_plural(),
                                upstream_project_label_plural(),
                                upstream_milestone_label(),
                                upstream_project_label(),
                                upstream_milestone_label()
                            ),
                        ],
                        [
                            'id'              => 'milestones',
                            'type'            => 'group',
                            'name'            => '',
                            'description'     => '',
                            'options'         => [
                                'group_title'   => sprintf('%s #{#}', upstream_milestone_label()),
                                'add_button'    => sprintf(__('Add %s', 'upstream'), upstream_milestone_label()),
                                'remove_button' => sprintf(__('Remove %s', 'upstream'), upstream_milestone_label()),
                                'sortable'      => true, // beta
                            ],
                            'sanitization_cb' => ['UpStream_Admin', 'onBeforeSave'],
                            'fields'          => [
                                [
                                    'name' => __('Hidden', 'upstream'),
                                    'id'   => 'id',
                                    'type' => 'hidden',
                                ],
                                [
                                    'name' => __('Color', 'upstream'),
                                    'id'   => 'color',
                                    'type' => 'colorpicker',
                                ],
                                [
                                    'name' => __('Title', 'upstream'),
                                    'id'   => 'title',
                                    'type' => 'text',
                                ],
                            ],
                        ],

                    ],
                ]
            );

            return $options;
        }
    }

endif;
