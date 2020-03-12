<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! class_exists('UpStream_Options_Projects')) :

    /**
     * CMB2 Theme Options
     *
     * @version 0.1.0
     */
    class UpStream_Options_Projects
    {

        /**
         * Array of metaboxes/fields
         *
         * @var array
         */
        public $id = 'upstream_projects';

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
            $this->title      = upstream_project_label_plural();
            $this->menu_title = $this->title;
            //$this->description = sprintf( __( '%s Description', 'upstream' ), upstream_project_label() );
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
                            'name' => __('Statuses', 'upstream'),
                            'id'   => 'status_title',
                            'type' => 'title',
                            'desc' => sprintf(
                                __(
                                    'The statuses and colors that can be used for the main status of the %s.<br>These will become available in the %s Status dropdown within the %s',
                                    'upstream'
                                ),
                                upstream_project_label(),
                                upstream_project_label(),
                                upstream_project_label()
                            ),
                        ],
                        [
                            'id'              => 'statuses',
                            'type'            => 'group',
                            'name'            => '',
                            'description'     => '',
                            'options'         => [
                                'group_title'   => __('Status {#}', 'upstream'),
                                'add_button'    => __('Add Status', 'upstream'),
                                'remove_button' => __('Remove Entry', 'upstream'),
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
                                    'name'       => __('Status Color', 'upstream'),
                                    'id'         => 'color',
                                    'type'       => 'colorpicker',
                                    'attributes' => [
                                        'data-colorpicker' => json_encode([
                                            // Iris Options set here as values in the 'data-colorpicker' array
                                            'palettes' => upstream_colorpicker_default_colors(),
                                            'width'    => 300,
                                        ]),
                                    ],
                                ],
                                [
                                    'name' => __('Status Name', 'upstream'),
                                    'id'   => 'name',
                                    'type' => 'text',
                                ],
                                [
                                    'name'    => __('Type of Status', 'upstream'),
                                    'id'      => 'type',
                                    'type'    => 'radio',
                                    'default' => 'open',
                                    'desc'    => __(
                                                     "A Status Name such as 'In Progress' or 'Overdue' would be considered Open.",
                                                     'upstream'
                                                 ) . '<br>' . __(
                                                     "A Status Name such as 'Complete' or 'Cancelled' would be considered Closed.",
                                                     'upstream'
                                                 ),
                                    'options' => [
                                        'open'   => __('Open', 'upstream'),
                                        'closed' => __('Closed', 'upstream'),
                                    ],
                                ],
                            ],
                        ],


                        [
                            'name' => __('Other Settings', 'upstream'),
                            'id'   => 'project_other_settings',
                            'type' => 'title',
                            'desc' => __('Additional settings for projects in UpStream.', 'upstream')
                        ],
                        [
                            'name'    => __('Number of Projects Per Page on Frontend', 'upstream'),
                            'id'      => 'project_number_per_page',
                            'type'    => 'text',
                            'desc'    => __('The number of projects per page to display on front end.', 'upstream'),
                            'default' => 1000,
                        ],
                    ],
                ]
            );

            return $options;
        }

        /**
         * Create ids for all existent project statuses.
         *
         * @since   1.17.0
         * @static
         */
        public static function createProjectsStatusesIds()
        {
            $continue = ! (bool)get_option('upstream:created_projects_args_ids');
            if ( ! $continue) {
                return;
            }

            $options = get_option('upstream_projects');
            if (isset($options['statuses'])) {
                $options['statuses'] = UpStream_Admin::createMissingIdsInSet($options['statuses']);

                update_option('upstream_projects', $options);

                $statuses = [];
                foreach ($options['statuses'] as $row) {
                    $statuses[$row['name']] = $row['id'];
                }

                // Update existent Milestone data across all Projects.
                global $wpdb;

                $metas = $wpdb->get_results(sprintf(
                    'SELECT `post_id`, `meta_value`
                FROM `%s`
                WHERE `meta_key` = "_upstream_project_status"',
                    $wpdb->prefix . 'postmeta'
                ));

                if (count($metas) > 0) {
                    foreach ($metas as $meta) {
                        if (empty($meta->meta_value)
                            || ! isset($statuses[$meta->meta_value])
                        ) {
                            continue;
                        }

                        $meta->meta_value = $statuses[$meta->meta_value];

                        update_post_meta($meta->post_id, '_upstream_project_status', $meta->meta_value);
                    }
                }

                update_option('upstream:created_projects_args_ids', 1);
            }
        }
    }


endif;
