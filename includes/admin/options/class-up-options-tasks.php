<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! class_exists('UpStream_Options_Tasks')) :

    /**
     * CMB2 Theme Options
     *
     * @version 0.1.0
     */
    class UpStream_Options_Tasks
    {

        /**
         * Array of metaboxes/fields
         *
         * @var array
         */
        public $id = 'upstream_tasks';

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
            $this->title      = upstream_task_label_plural();
            $this->menu_title = $this->title;
            //$this->description = sprintf( __( '%s Description', 'upstream' ), upstream_task_label() );
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
                    'id'         => $this->id, // upstream_tasks
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
                                    'The statuses and colors that can be used for the status of %s.<br>These will become available in the %s Status dropdown within each %s',
                                    'upstream'
                                ),
                                upstream_task_label_plural(),
                                upstream_task_label(),
                                upstream_task_label()
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
                                [
                                    'name'    => __('Percentage', 'upstream'),
                                    'id'      => 'percent',
                                    'type'    => 'select',
                                    'options' => [
                                        '0'   => __('0%'),
                                        '5'   => __('5%'),
                                        '10'  => __('10%'),
                                        '15'  => __('15%'),
                                        '20'  => __('20%'),
                                        '25'  => __('25%'),
                                        '30'  => __('30%'),
                                        '35'  => __('35%'),
                                        '40'  => __('40%'),
                                        '45'  => __('45%'),
                                        '50'  => __('50%'),
                                        '55'  => __('55%'),
                                        '60'  => __('60%'),
                                        '65'  => __('65%'),
                                        '70'  => __('70%'),
                                        '75'  => __('75%'),
                                        '80'  => __('80%'),
                                        '85'  => __('85%'),
                                        '90'  => __('90%'),
                                        '95'  => __('95%'),
                                        '100' => __('100%'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );

            return $options;
        }

        /**
         * Create ids for all existent tasks statuses.
         *
         * @since   1.17.0
         * @static
         */
        public static function createTasksStatusesIds()
        {
            $continue = ! (bool)get_option('upstream:created_tasks_args_ids');
            if ( ! $continue) {
                return;
            }

            $tasks = get_option('upstream_tasks');
            if (isset($tasks['statuses'])) {
                $tasks['statuses'] = UpStream_Admin::createMissingIdsInSet($tasks['statuses']);

                update_option('upstream_tasks', $tasks);

                $tasks = $tasks['statuses'];

                // Update existent Tasks status across all Projects.
                global $wpdb;

                $metas = $wpdb->get_results(sprintf(
                    'SELECT `post_id`, `meta_value`
                FROM `%s`
                WHERE `meta_key` = "_upstream_project_tasks"',
                    $wpdb->prefix . 'postmeta'
                ));

                if (count($metas) > 0) {
                    $getTaskStatusIdByTitle = function ($needle) use (&$tasks) {
                        foreach ($tasks as $task) {
                            if ($needle === $task['name']) {
                                return $task['id'];
                            }
                        }

                        return false;
                    };

                    $replaceTaskStatusWithItsId = function ($task) use (&$getTaskStatusIdByTitle) {
                        if (isset($task['status'])) {
                            $taskId = $getTaskStatusIdByTitle($task['status']);
                            if ($taskId !== false) {
                                $task['status'] = $taskId;
                            }
                        }

                        return $task;
                    };

                    foreach ($metas as $meta) {
                        if (empty($meta->meta_value)) {
                            continue;
                        }

                        $projectId = (int)$meta->post_id;

                        $data = array_filter(maybe_unserialize((string)$meta->meta_value));
                        $data = array_map($replaceTaskStatusWithItsId, $data);

                        update_post_meta($projectId, '_upstream_project_tasks', $data);
                    }
                }

                update_option('upstream:created_tasks_args_ids', 1);
            }
        }
    }


endif;
