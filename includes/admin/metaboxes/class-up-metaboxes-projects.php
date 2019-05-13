<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! class_exists('UpStream_Metaboxes_Projects')) :


    /**
     * CMB2 Theme Options
     *
     * @version 0.1.0
     */
    class UpStream_Metaboxes_Projects
    {


        /**
         * Post type
         *
         * @var string
         */
        public $type = 'project';

        /**
         * Metabox prefix
         *
         * @var string
         */
        public $prefix = '_upstream_project_';

        public $project_label = '';

        /**
         * Holds an instance of the object
         *
         * @var Myprefix_Admin
         **/
        public static $instance = null;

        /**
         * Indicates if comments section is enabled.
         *
         * @since   1.13.0
         * @access  private
         * @static
         *
         * @var     bool $allowProjectComments
         */
        private static $allowProjectComments = true;

        public function __construct()
        {
            $this->project_label = upstream_project_label();

            do_action('upstream_admin_notices_errors');

            // Ensure WordPress can generate and display custom slugs for the project by making it public temporarily fast.
            add_action('edit_form_before_permalink', [$this, 'makeProjectTemporarilyPublic']);
            // Ensure the made public project are non-public as it should.
            add_action('edit_form_after_title', [$this, 'makeProjectPrivateOnceAgain']);

            add_action('cmb2_render_comments', [$this, 'renderCommentsField'], 10, 5);

            // Prevent action being hooked twice.
            global $wp_filter;
            if ( ! isset($wp_filter['cmb2_render_select2'])) {
                // Add select2 field type.
                add_action('cmb2_render_select2', [$this, 'renderSelect2Field'], 10, 5);
            }

            if ( ! isset($wp_filter['cmb2_sanitize_select2'])) {
                // Add select2 field type sanitization callback.
                add_action('cmb2_sanitize_select2', [$this, 'sanitizeSelect2Field'], 10, 5);
            }
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

                if (upstream_post_id() > 0) {
                    self::$instance->overview();
                }

                if ( ! upstream_disable_milestones()) {
                    self::$instance->milestones();
                }

                if ( ! upstream_disable_tasks()) {
                    self::$instance->tasks();
                }

                if ( ! upstream_disable_bugs()) {
                    self::$instance->bugs();
                }

                if ( ! upstream_disable_files()) {
                    self::$instance->files();
                }

                self::$instance->details();
                self::$instance->sidebar_low();

                self::$allowProjectComments = upstreamAreProjectCommentsEnabled();

                if (self::$allowProjectComments) {
                    self::$instance->comments();
                }

                do_action('upstream_details_metaboxes');
            }

            return self::$instance;
        }

        /* ======================================================================================
                                                OVERVIEW
           ====================================================================================== */
        /**
         * Add the metaboxes
         *
         * @since  0.1.0
         */
        public function overview()
        {
            $areMilestonesDisabled      = upstream_are_milestones_disabled();
            $areMilestonesDisabledAtAll = upstream_disable_milestones();
            $areTasksDisabled           = upstream_are_tasks_disabled();
            $areBugsDisabled            = upstream_are_bugs_disabled();

            if (( ! $areMilestonesDisabled && $areMilestonesDisabledAtAll) || ! $areTasksDisabled || ! $areBugsDisabled) {
                $metabox = new_cmb2_box([
                    'id'           => $this->prefix . 'overview',
                    'title'        => $this->project_label . __(' Overview', 'upstream') .
                                      '<span class="progress align-right"><progress value="' . upstream_project_progress() . '" max="100"></progress> <span>' . upstream_project_progress() . '%</span></span>',
                    'object_types' => [$this->type],
                ]);

                //Create a default grid
                $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

                $columnsList = [];

                if ( ! $areMilestonesDisabled && ! $areMilestonesDisabledAtAll) {
                    $columnsList[] = $metabox->add_field([
                        'name'  => '<span>' . upstream_count_total(
                                'milestones',
                                upstream_post_id()
                            ) . '</span> ' . upstream_milestone_label_plural(),
                        'id'    => $this->prefix . 'milestones',
                        'type'  => 'title',
                        'after' => 'upstream_output_overview_counts',
                    ]);
                }

                if ( ! upstream_disable_tasks()) {
                    if ( ! $areTasksDisabled) {
                        $grid2         = $metabox->add_field([
                            'name'  => '<span>' . upstream_count_total(
                                    'tasks',
                                    upstream_post_id()
                                ) . '</span> ' . upstream_task_label_plural(),
                            'desc'  => '',
                            'id'    => $this->prefix . 'tasks',
                            'type'  => 'title',
                            'after' => 'upstream_output_overview_counts',
                        ]);
                        $columnsList[] = $grid2;
                    }
                }

                if ( ! $areBugsDisabled) {
                    $grid3         = $metabox->add_field([
                        'name'  => '<span>' . upstream_count_total(
                                'bugs',
                                upstream_post_id()
                            ) . '</span> ' . upstream_bug_label_plural(),
                        'desc'  => '',
                        'id'    => $this->prefix . 'bugs',
                        'type'  => 'title',
                        'after' => 'upstream_output_overview_counts',
                    ]);
                    $columnsList[] = $grid3;
                }

                //Create now a Grid of group fields
                $row = $cmb2Grid->addRow();
                $row->addColumns($columnsList);
            }
        }


        /* ======================================================================================
                                                MILESTONES
           ====================================================================================== */
        /**
         * Add the metaboxes
         *
         * @since  0.1.0
         */
        public function milestones()
        {
            $areMilestonesDisabled      = upstream_are_milestones_disabled();
            $areMilestonesDisabledAtAll = upstream_disable_milestones();
            $userHasAdminPermissions    = upstream_admin_permissions('disable_project_milestones');

            if ($areMilestonesDisabledAtAll || ($areMilestonesDisabled && ! $userHasAdminPermissions)) {
                return;
            }

            $label        = upstream_milestone_label();
            $label_plural = upstream_milestone_label_plural();

            $metabox = new_cmb2_box([
                'id'           => $this->prefix . 'milestones',
                'title'        => '<span class="dashicons dashicons-flag"></span> ' . esc_html($label_plural),
                'object_types' => [$this->type],
            ]);

            //Create a default grid
            $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

            /*
             * Outputs some hidden data for dynamic use.
             */
            $metabox->add_field([
                'id'          => $this->prefix . 'hidden',
                'type'        => 'title',
                'description' => '',
                'after'       => 'upstream_admin_output_milestone_hidden_data',
                'attributes'  => [
                    'class'        => 'hidden',
                    'data-publish' => upstream_admin_permissions('publish_project_milestones'),
                ],
            ]);

            if ( ! $areMilestonesDisabled) {
                $group_field_id = $metabox->add_field([
                    'id'           => $this->prefix . 'milestones',
                    'type'         => 'group',
                    'description'  => '',
                    'permissions'  => 'delete_project_milestones', // also set on individual row level
                    'before_group' => $this->getMilestonesFiltersHtml(),
                    'options'      => [
                        'group_title'   => esc_html($label) . " {#}",
                        'add_button'    => sprintf(__("Add %s", 'upstream'), esc_html($label)),
                        'remove_button' => sprintf(__("Delete %s", 'upstream'), esc_html($label)),
                        'sortable'      => upstream_admin_permissions('sort_project_milestones'),
                    ],
                ]);

                $fields = [];

                $fields[0] = [
                    'id'         => 'id',
                    'type'       => 'text',
                    'before'     => 'upstream_add_field_attributes',
                    'attributes' => [
                        'class' => 'hidden',
                    ],
                ];

                $allowComments = upstreamAreCommentsEnabledOnMilestones();
                if ($allowComments) {
                    $fields[0]['before_row'] = '
                    <div class="up-c-tabs-header nav-tab-wrapper nav-tab-wrapper">
                      <a href="#" class="nav-tab nav-tab-active up-o-tab up-o-tab-data" role="tab" data-target=".up-c-tab-content-data">' . __(
                            'Data',
                            'upstream'
                        ) . '</a>
                      <a href="#" class="nav-tab up-o-tab up-o-tab-comments" role="tab" data-target=".up-c-tab-content-comments">' . __('Comments') . '</a>
                    </div>
                    <div class="up-c-tabs-content">
                      <div class="up-o-tab-content up-c-tab-content-data is-active">';
                }

                $fields[1] = [
                    'id'         => 'created_by',
                    'type'       => 'text',
                    'attributes' => [
                        'class' => 'hidden',
                    ],
                ];
                $fields[2] = [
                    'id'         => 'created_time',
                    'type'       => 'text',
                    'attributes' => [
                        'class' => 'hidden',
                    ],
                ];


                // start row
                $fields[10] = [
                    'name'        => esc_html($label),
                    'id'          => 'milestone',
                    'type'        => 'text',
                    'permissions' => 'milestone_milestone_field',
                    'before'      => 'upstream_add_field_attributes',
                    'attributes'  => [
                        'class' => 'milestone',
                    ],
                ];

                $indexAssignedTo = 11;
                if ( ! upstream_disable_milestone_categories()) {
                    // Start row.
                    $fields[11] = [
                        'name'             => upstream_milestone_category_label(),
                        'id'               => 'categories',
                        'type'             => 'select2',
                        'permissions'      => 'milestone_milestone_field',
                        'before'           => 'upstream_add_field_attributes',
                        'show_option_none' => true,
                        'options_cb'       => 'upstream_admin_get_milestone_categories',
                    ];
                    // Move the Assigned To field to a next line.
                    $indexAssignedTo = 20;
                }

                $fields[$indexAssignedTo] = [
                    'name'             => __('Assigned To', 'upstream'),
                    'id'               => 'assigned_to',
                    'type'             => 'select2',
                    'permissions'      => 'milestone_assigned_to_field',
                    'before'           => 'upstream_add_field_attributes',
                    'show_option_none' => true,
                    'options_cb'       => 'upstream_admin_get_all_project_users',
                ];


                // start row
                $fields[30] = [
                    'name'        => __("Start Date", 'upstream'),
                    'id'          => 'start_date',
                    'type'        => 'up_timestamp',
                    'date_format' => 'Y-m-d',
                    'permissions' => 'milestone_start_date_field',
                    'before'      => 'upstream_add_field_attributes',
                    'escape_cb'   => ['UpStream_Admin', 'escapeCmb2TimestampField'],
                    'attributes'  => [
                        //'data-validation'     => 'required',
                    ],
                ];
                $fields[31] = [
                    'name'        => __("End Date", 'upstream'),
                    'id'          => 'end_date',
                    'type'        => 'up_timestamp',
                    'date_format' => 'Y-m-d',
                    'permissions' => 'milestone_end_date_field',
                    'before'      => 'upstream_add_field_attributes',
                    'escape_cb'   => ['UpStream_Admin', 'escapeCmb2TimestampField'],
                    'attributes'  => [
                        //'data-validation'     => 'required',
                    ],
                ];

                // start row
                $fields[40] = [
                    'name'        => __("Notes", 'upstream'),
                    'id'          => 'notes',
                    'type'        => 'wysiwyg',
                    'permissions' => 'milestone_notes_field',
                    'before'      => 'upstream_add_field_attributes',
                    'options'     => [
                        'media_buttons' => true,
                        'textarea_rows' => 5,
                    ],
                    'escape_cb'   => 'applyOEmbedFiltersToWysiwygEditorContent',
                ];

                if ($allowComments) {
                    $fields[50] = [
                        'name'      => '&nbsp;',
                        'id'        => 'comments',
                        'type'      => 'comments',
                        'after_row' => '</div><div class="up-o-tab-content up-c-tab-content-comments"></div></div>',
                    ];
                }

                // set up the group grid plugin
                $cmb2GroupGrid = $cmb2Grid->addCmb2GroupGrid($group_field_id);

                // define nuber of rows
                $rows = apply_filters('upstream_milestone_metabox_rows', 4);

                // filter the fields & sort numerically
                $fields = apply_filters('upstream_milestone_metabox_fields', $fields);
                ksort($fields);

                // loop through ordered fields and add them to the group
                if ($fields) {
                    foreach ($fields as $key => $value) {
                        $fields[$key] = $metabox->add_group_field($group_field_id, $value);
                    }
                }

                // loop through number of rows
                for ($i = 0; $i < $rows; $i++) {

                    // add each row
                    $row[$i] = $cmb2GroupGrid->addRow();

                    // this is our hidden row that must remain as is
                    if ($i == 0) {
                        $row[0]->addColumns([$fields[0], $fields[1], $fields[2]]);
                    } else {

                        // this allows up to 4 columns in each row
                        $array = [];
                        if (isset($fields[$i * 10])) {
                            $array[] = $fields[$i * 10];
                        }
                        if (isset($fields[$i * 10 + 1])) {
                            $array[] = $fields[$i * 10 + 1];
                        }
                        if (isset($fields[$i * 10 + 2])) {
                            $array[] = $fields[$i * 10 + 2];
                        }
                        if (isset($fields[$i * 10 + 3])) {
                            $array[] = $fields[$i * 10 + 3];
                        }

                        // Ignore empty rows
                        if (empty($array)) {
                            continue;
                        }

                        // add the fields as columns
                        // probably don't need this to be filterable but will leave it for now
                        $row[$i]->addColumns(
                            apply_filters("upstream_milestone_row_{$i}_columns", $array)
                        );
                    }
                }
            }

            if ($userHasAdminPermissions) {
                $metabox->add_field([
                    'id'          => $this->prefix . 'disable_milestones',
                    'type'        => 'checkbox',
                    'description' => __('Disable Milestones for this project', 'upstream'),
                ]);
            }
        }

        /**
         * Return HTML of all admin filters for Milestones.
         *
         * @return  string
         * @since   1.15.0
         * @access  private
         *
         */
        private function getMilestonesFiltersHtml()
        {
            $users         = upstream_admin_get_all_project_users();
            $prefix        = 'milestones-filter-';
            $currentUserId = get_current_user_id();

            ob_start(); ?>
            <div class="up-c-filters">
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'milestone'; ?>"><?php echo upstream_milestone_label(); ?></label>
                    <input type="text" id="<?php echo $prefix . 'milestone'; ?>" class="up-o-filter"
                           data-column="milestone" data-trigger_on="keyup" data-compare-operator="contains">
                </div>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'assignee'; ?>" class="up-s-mb-2"><?php _e(
                            'Assignee',
                            'upstream'
                        ); ?></label>
                    <select id="<?php echo $prefix . 'assignee'; ?>" class="up-o-filter o-select2"
                            data-column="assigned_to" data-placeholder="" data-compare-operator="contains" multiple>
                        <option></option>
                        <option value="<?php echo $currentUserId; ?>"><?php _e('Me', 'upstream'); ?></option>
                        <option value="__none__"><?php _e('Nobody', 'upstream'); ?></option>
                        <optgroup label="<?php _e('Users'); ?>">
                            <?php foreach ($users as $userId => $userName): ?>
                                <?php if ((int)$userId === $currentUserId) {
                                    continue;
                                } ?>
                                <option value="<?php echo $userId; ?>"><?php echo $userName; ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'start_date'; ?>"><?php _e('Start Date', 'upstream'); ?></label>
                    <input type="text" id="<?php echo $prefix . 'start_date'; ?>" class="up-o-filter up-o-filter-date"
                           data-column="start_date" data-compare-operator=">=">
                </div>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'end_date'; ?>"><?php _e('End Date', 'upstream'); ?></label>
                    <input type="text" id="<?php echo $prefix . 'end_date'; ?>" class="up-o-filter up-o-filter-date"
                           data-column="end_date" data-compare-operator="<=">
                </div>
            </div>
            <?php
            $html = ob_get_contents();
            ob_clean();

            return $html;
        }

        /**
         * Return HTML of all admin filters for Tasks.
         *
         * @return  string
         * @since   1.15.0
         * @access  private
         *
         */
        private function getTasksFiltersHtml()
        {
            $users         = upstream_admin_get_all_project_users();
            $prefix        = 'tasks-filter-';
            $currentUserId = get_current_user_id();
            $statuses      = get_option('upstream_tasks');
            $statuses      = $statuses['statuses'];
            $projectId     = upstream_post_id();

            $milestones = [];
            $rowset     = \UpStream\Milestones::getInstance()->getMilestonesFromProject($projectId, true);
            foreach ($rowset as $data) {
                if ( ! isset($data['id'])
                     || ! isset($data['created_by'])
                     || ! isset($data['milestone'])
                ) {
                    continue;
                }

                $milestones[$data['id']] = $data['milestone'];
            }
            unset($data, $rowset);

            ob_start(); ?>
            <div class="up-c-filters">
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'title'; ?>"><?php _e('Title', 'upstream'); ?></label>
                    <input type="text" id="<?php echo $prefix . 'title'; ?>" class="up-o-filter" data-column="title"
                           data-trigger_on="keyup" data-compare-operator="contains">
                </div>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'assignee'; ?>" class="up-s-mb-2"><?php _e(
                            'Assignee',
                            'upstream'
                        ); ?></label>
                    <select id="<?php echo $prefix . 'assignee'; ?>" class="up-o-filter o-select2"
                            data-column="assigned_to" multiple data-placeholder="" data-compare-operator="contains">
                        <option></option>
                        <option value="<?php echo $currentUserId; ?>"><?php _e('Me', 'upstream'); ?></option>
                        <option value="__none__"><?php _e('Nobody', 'upstream'); ?></option>
                        <optgroup label="<?php _e('Users'); ?>">
                            <?php foreach ($users as $userId => $userName): ?>
                                <?php if ((int)$userId === $currentUserId) {
                                    continue;
                                } ?>
                                <option value="<?php echo $userId; ?>"><?php echo $userName; ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'status'; ?>" class="up-s-mb-2"><?php _e(
                            'Status',
                            'upstream'
                        ); ?></label>
                    <select id="<?php echo $prefix . 'status'; ?>" class="up-o-filter o-select2" data-column="status"
                            data-placeholder="" multiple data-compare-operator="contains">
                        <option></option>
                        <option value="__none__"><?php _e('None', 'upstream'); ?></option>
                        <optgroup label="<?php _e('Statuses', 'upstream'); ?>">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo $status['name']; ?>"><?php echo $status['name']; ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <?php
                if (upstream_are_milestones_disabled()
                    && upstream_disable_milestones()): ?>
                    <div class="up-c-filter">
                        <label for="<?php echo $prefix . 'milestone'; ?>"
                               class="up-s-mb-2"><?php echo upstream_milestone_label(); ?></label>
                        <select id="<?php echo $prefix . 'milestone'; ?>" class="up-o-filter o-select2"
                                data-column="milestone" data-placeholder="" multiple data-compare-operator="contains">
                            <option></option>
                            <option value="__none__"><?php _e('None', 'upstream'); ?></option>
                            <optgroup label="<?php echo upstream_milestone_label_plural(); ?>">
                                <?php foreach ($milestones as $milestoneId => $milestoneTitle): ?>
                                    <option value="<?php echo $milestoneId; ?>"><?php echo $milestoneTitle; ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'start_date'; ?>"><?php _e('Start Date', 'upstream'); ?></label>
                    <input type="text" id="<?php echo $prefix . 'start_date'; ?>" class="up-o-filter up-o-filter-date"
                           data-column="start_date" data-compare-operator=">=">
                </div>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'end_date'; ?>"><?php _e('End Date', 'upstream'); ?></label>
                    <input type="text" id="<?php echo $prefix . 'end_date'; ?>" class="up-o-filter up-o-filter-date"
                           data-column="end_date" data-compare-operator="<=">
                </div>
            </div>
            <?php
            $html = ob_get_contents();
            ob_clean();

            return $html;
        }

        /**
         * Return HTML of all admin filters for Bugs.
         *
         * @return  string
         * @since   1.15.0
         * @access  private
         *
         */
        private function getBugsFiltersHtml()
        {
            $users         = upstream_admin_get_all_project_users();
            $prefix        = 'bugs-filter-';
            $currentUserId = get_current_user_id();
            $bugsSettings  = get_option('upstream_bugs');
            $statuses      = $bugsSettings['statuses'];
            $severities    = $bugsSettings['severities'];
            unset($bugsSettings);

            ob_start(); ?>
            <div class="up-c-filters">
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'title'; ?>"><?php _e('Title', 'upstream'); ?></label>
                    <input type="text" id="<?php echo $prefix . 'title'; ?>" class="up-o-filter" data-column="title"
                           data-trigger_on="keyup" data-compare-operator="contains">
                </div>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'assignee'; ?>" class="up-s-mb-2"><?php _e(
                            'Assignee',
                            'upstream'
                        ); ?></label>
                    <select id="<?php echo $prefix . 'assignee'; ?>" class="up-o-filter o-select2"
                            data-column="assigned_to" data-placeholder="" multiple data-compare-operator="contains">
                        <option></option>
                        <option value="<?php echo $currentUserId; ?>"><?php _e('Me', 'upstream'); ?></option>
                        <option value="__none__"><?php _e('Nobody', 'upstream'); ?></option>
                        <optgroup label="<?php _e('Users'); ?>">
                            <?php foreach ($users as $userId => $userName): ?>
                                <?php if ((int)$userId === $currentUserId) {
                                    continue;
                                } ?>
                                <option value="<?php echo $userId; ?>"><?php echo $userName; ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'severity'; ?>" class="up-s-mb-2"><?php _e(
                            'Severities',
                            'upstream'
                        ); ?></label>
                    <select id="<?php echo $prefix . 'severity'; ?>" class="up-o-filter o-select2"
                            data-column="severity" data-placeholder="" multiple data-compare-operator="contains">
                        <option></option>
                        <option value="__none__"><?php _e('None', 'upstream'); ?></option>
                        <optgroup label="<?php _e('Severities', 'upstream'); ?>">
                            <?php foreach ($severities as $severity): ?>
                                <option
                                        value="<?php echo $severity['name']; ?>"><?php echo $severity['name']; ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'status'; ?>" class="up-s-mb-2"><?php _e(
                            'Status',
                            'upstream'
                        ); ?></label>
                    <select id="<?php echo $prefix . 'status'; ?>" class="up-o-filter o-select2" data-column="status"
                            data-placeholder="" multiple data-compare-operator="contains">
                        <option></option>
                        <option value="__none__"><?php _e('None', 'upstream'); ?></option>
                        <optgroup label="<?php _e('Statuses', 'upstream'); ?>">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo $status['name']; ?>"><?php echo $status['name']; ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'due_date'; ?>"><?php _e('Due Date', 'upstream'); ?></label>
                    <input type="text" id="<?php echo $prefix . 'due_date'; ?>" class="up-o-filter up-o-filter-date"
                           data-column="due_date" data-compare-operator="<=">
                </div>
            </div>
            <?php
            $html = ob_get_contents();
            ob_clean();

            return $html;
        }

        /**
         * Return HTML of all admin filters for Files.
         *
         * @return  string
         * @since   1.15.0
         * @access  private
         *
         */
        private function getFilesFiltersHtml()
        {
            $users         = upstream_admin_get_all_project_users();
            $prefix        = 'files-filter-';
            $currentUserId = get_current_user_id();

            ob_start(); ?>
            <div class="up-c-filters">
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'title'; ?>"><?php _e('Title', 'upstream'); ?></label>
                    <input type="text" id="<?php echo $prefix . 'title'; ?>" class="up-o-filter" data-column="title"
                           data-trigger_on="keyup" data-compare-operator="contains">
                </div>
                <div class="up-c-filter">
                    <label for="<?php echo $prefix . 'uploaded_by'; ?>" class="up-s-mb-2"><?php _e(
                            'Uploaded by',
                            'upstream'
                        ); ?></label>
                    <select id="<?php echo $prefix . 'uploaded_by'; ?>" class="up-o-filter o-select2"
                            data-column="created_by" data-placeholder="" multiple data-compare-operator="contains">
                        <option></option>
                        <option value="<?php echo $currentUserId; ?>"><?php _e('Me', 'upstream'); ?></option>
                        <option value="__none__"><?php _e('Nobody', 'upstream'); ?></option>
                        <optgroup label="<?php _e('Users'); ?>">
                            <?php foreach ($users as $userId => $userName): ?>
                                <?php if ((int)$userId === $currentUserId) {
                                    continue;
                                } ?>
                                <option value="<?php echo $userId; ?>"><?php echo $userName; ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
            </div>
            <?php
            $html = ob_get_contents();
            ob_clean();

            return $html;
        }

        /* ======================================================================================
                                                TASKS
           ====================================================================================== */
        /**
         * Add the metaboxes
         *
         * @since  0.1.0
         */
        public function tasks()
        {
            $areTasksDisabled        = upstream_are_tasks_disabled();
            $userHasAdminPermissions = upstream_admin_permissions('disable_project_tasks');

            if (upstream_disable_tasks() || ($areTasksDisabled && ! $userHasAdminPermissions)) {
                return;
            }

            $label        = upstream_task_label();
            $label_plural = upstream_task_label_plural();

            $metabox = new_cmb2_box([
                'id'           => $this->prefix . 'tasks',
                'title'        => '<span class="dashicons dashicons-admin-tools"></span> ' . esc_html($label_plural),
                'object_types' => [$this->type],
            ]);

            //Create a default grid
            $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

            /*
             * Outputs some hidden data for dynamic use.
             */
            $metabox->add_field([
                'id'          => $this->prefix . 'hidden',
                'type'        => 'title',
                'description' => '',
                'after'       => 'upstream_admin_output_task_hidden_data',
                'attributes'  => [
                    'class'        => 'hidden',
                    'data-empty'   => upstream_empty_group('tasks'),
                    'data-publish' => upstream_admin_permissions('publish_project_tasks'),
                ],
            ]);

            $group_field_id = $metabox->add_field([
                'id'           => $this->prefix . 'tasks',
                'type'         => 'group',
                'description'  => '',
                'permissions'  => 'delete_project_tasks', // also set on individual row level
                'options'      => [
                    'group_title'   => esc_html($label) . " {#}",
                    'add_button'    => sprintf(__("Add %s", 'upstream'), esc_html($label)),
                    'remove_button' => sprintf(__("Delete %s", 'upstream'), esc_html($label)),
                    'sortable'      => upstream_admin_permissions('sort_project_tasks'), // beta
                ],
                'before_group' => $this->getTasksFiltersHtml(),
            ]);

            if ( ! $areTasksDisabled) {
                $fields = [];

                $fields[0] = [
                    'id'          => 'id',
                    'type'        => 'text',
                    'before'      => 'upstream_add_field_attributes',
                    'permissions' => '',
                    'attributes'  => [
                        'class' => 'hidden',
                    ],
                ];

                $allowComments = upstreamAreCommentsEnabledOnTasks();
                if ($allowComments) {
                    $fields[0]['before_row'] = '
                    <div class="up-c-tabs-header nav-tab-wrapper nav-tab-wrapper">
                      <a href="#" class="nav-tab nav-tab-active up-o-tab up-o-tab-data" role="tab" data-target=".up-c-tab-content-data">' . __(
                            'Data',
                            'upstream'
                        ) . '</a>
                      <a href="#" class="nav-tab up-o-tab up-o-tab-comments" role="tab" data-target=".up-c-tab-content-comments">' . __('Comments') . '</a>
                    </div>
                    <div class="up-c-tabs-content">
                      <div class="up-o-tab-content up-c-tab-content-data is-active">';
                }

                $fields[1] = [
                    'id'         => 'created_by',
                    'type'       => 'text',
                    'attributes' => [
                        'class' => 'hidden',
                    ],
                ];
                $fields[2] = [
                    'id'         => 'created_time',
                    'type'       => 'text',
                    'attributes' => [
                        'class' => 'hidden',
                    ],
                ];

                // start row
                $fields[10] = [
                    'name'        => __('Title', 'upstream'),
                    'id'          => 'title',
                    'type'        => 'text',
                    'permissions' => 'task_title_field',
                    'before'      => 'upstream_add_field_attributes',
                    'attributes'  => [
                        'class' => 'task-title',
                        //'data-validation'     => 'required',
                    ],
                ];

                $fields[11] = [
                    'name'             => __('Assigned To', 'upstream'),
                    'id'               => 'assigned_to',
                    'type'             => 'select2',
                    'permissions'      => 'task_assigned_to_field',
                    'before'           => 'upstream_add_field_attributes',
                    'show_option_none' => true,
                    'options_cb'       => 'upstream_admin_get_all_project_users',
                ];

                // start row
                $fields[20] = [
                    'name'             => __("Status", 'upstream'),
                    'id'               => 'status',
                    'type'             => 'select',
                    'permissions'      => 'task_status_field',
                    'before'           => 'upstream_add_field_attributes',
                    'show_option_none' => true,  // ** IMPORTANT - do not enforce a value in this field.
                    // An row with no value here is considered to be a deleted row.
                    'options_cb'       => 'upstream_admin_get_task_statuses',
                    'attributes'       => [
                        'class' => 'task-status',
                    ],
                ];

                $fields[21] = [
                    'name'        => __("Progress", 'upstream'),
                    'id'          => 'progress',
                    'type'        => 'select',
                    'permissions' => 'task_progress_field',
                    'before'      => 'upstream_add_field_attributes',
                    'options_cb'  => 'upstream_get_percentages_for_dropdown',
                    'attributes'  => [
                        'class' => 'task-progress',
                    ],
                ];

                // start row
                $fields[30] = [
                    'name'        => __("Start Date", 'upstream'),
                    'id'          => 'start_date',
                    'type'        => 'up_timestamp',
                    'date_format' => 'Y-m-d',
                    'permissions' => 'task_start_date_field',
                    'before'      => 'upstream_add_field_attributes',
                    'escape_cb'   => ['UpStream_Admin', 'escapeCmb2TimestampField'],
                ];
                $fields[31] = [
                    'name'        => __("End Date", 'upstream'),
                    'id'          => 'end_date',
                    'type'        => 'up_timestamp',
                    'date_format' => 'Y-m-d',
                    'permissions' => 'task_end_date_field',
                    'before'      => 'upstream_add_field_attributes',
                    'escape_cb'   => ['UpStream_Admin', 'escapeCmb2TimestampField'],
                ];

                $fields[40] = [
                    'name'        => __("Notes", 'upstream'),
                    'id'          => 'notes',
                    'type'        => 'wysiwyg',
                    'permissions' => 'task_notes_field',
                    'before'      => 'upstream_add_field_attributes',
                    'options'     => [
                        'media_buttons' => true,
                        'textarea_rows' => 5,
                    ],
                    'escape_cb'   => 'applyOEmbedFiltersToWysiwygEditorContent',
                ];

                if ( ! upstream_are_milestones_disabled() && ! upstream_disable_milestones()) {
                    $fields[41] = [
                        'name'             => '<span class="dashicons dashicons-flag"></span> ' . esc_html(upstream_milestone_label()),
                        'id'               => 'milestone',
                        'desc'             =>
                            __(
                                'Selecting a milestone will count this task\'s progress toward that milestone as well as overall project progress.',
                                'upstream'
                            ),
                        'type'             => 'select',
                        'permissions'      => 'task_milestone_field',
                        'before'           => 'upstream_add_field_attributes',
                        'show_option_none' => true,
                        'options_cb'       => 'upstream_admin_get_project_milestones',
                        'attributes'       => [
                            'class' => 'task-milestone',
                        ],
                    ];
                } else {
                    $fields[41] = [
                        'id'          => "milestone",
                        'type'        => "text",
                        'permissions' => 'task_milestone_field',
                        'attributes'  => [
                            'class' => "hidden",
                        ],
                    ];
                }

                if ($allowComments) {
                    $fields[50] = [
                        'name'      => '&nbsp;',
                        'id'        => 'comments',
                        'type'      => 'comments',
                        'after_row' => '</div><div class="up-o-tab-content up-c-tab-content-comments"></div></div>',
                    ];
                }

                // set up the group grid plugin
                $cmb2GroupGrid = $cmb2Grid->addCmb2GroupGrid($group_field_id);

                // define nuber of rows
                $rows = apply_filters('upstream_task_metabox_rows', 10);

                // filter the fields & sort numerically
                $fields = apply_filters('upstream_task_metabox_fields', $fields);
                ksort($fields);

                // loop through ordered fields and add them to the group
                if ($fields) {
                    foreach ($fields as $key => $value) {
                        $fields[$key] = $metabox->add_group_field($group_field_id, $value);
                    }
                }

                // loop through number of rows
                for ($i = 0; $i < 7; $i++) {

                    // add each row
                    $row[$i] = $cmb2GroupGrid->addRow();

                    // this is our hidden row that must remain as is
                    if ($i == 0) {
                        $row[0]->addColumns([$fields[0], $fields[1], $fields[2]]);
                    } else {

                        // this allows up to 4 columns in each row
                        $array = [];
                        if (isset($fields[$i * 10])) {
                            $array[] = $fields[$i * 10];
                        }
                        if (isset($fields[$i * 10 + 1])) {
                            $array[] = $fields[$i * 10 + 1];
                        }
                        if (isset($fields[$i * 10 + 2])) {
                            $array[] = $fields[$i * 10 + 2];
                        }
                        if (isset($fields[$i * 10 + 3])) {
                            $array[] = $fields[$i * 10 + 3];
                        }

                        if (empty($array)) {
                            continue;
                        }
                        // add the fields as columns
                        $row[$i]->addColumns(
                            apply_filters("upstream_task_row_{$i}_columns", $array)
                        );
                    }
                }
            }

            if ($userHasAdminPermissions) {
                $metabox->add_field([
                    'id'          => $this->prefix . 'disable_tasks',
                    'type'        => 'checkbox',
                    'description' => __('Disable Tasks for this project', 'upstream'),
                ]);
            }
        }


        private static $commentsFieldsNonce = false;

        private static $itemsCommentsSectionCache = [];

        public static function renderCommentsField($field, $escapedValue, $object_id, $objectType, $fieldType)
        {
            if ( ! self::$commentsFieldsNonce) {
                echo '<input type="hidden" id="project_all_items_comments_nonce" value="' . wp_create_nonce('project.get_all_items_comments') . '">';
                self::$commentsFieldsNonce = true;
            }

            $field_id = $field->args['id'];

            if ( ! isset(self::$itemsCommentsSectionCache[$field_id])) {
                $editorIdentifier = $field_id . '_editor';

                preg_match('/^_upstream_project_([a-z]+)_([0-9]+)_comments/i', $field_id, $matches);

                echo '<div class="c-comments" data-type="' . rtrim($matches[1], "s") . '"></div>';

                printf(
                    '<input type="hidden" id="%s" value="%s">',
                    $field_id . '_add_comment_nonce',
                    wp_create_nonce('upstream:project.' . $matches[1] . '.add_comment')
                );

                wp_editor("", $editorIdentifier, [
                    'media_buttons' => true,
                    'textarea_rows' => 5,
                    'textarea_name' => $editorIdentifier,
                ]);

                self::$itemsCommentsSectionCache[$field_id] = 1;
            }
        }





        /* ======================================================================================
                                                BUGS
           ====================================================================================== */
        /**
         * Add the metaboxes
         *
         * @since  0.1.0
         */
        public function bugs()
        {
            $areBugsDisabled         = upstream_are_bugs_disabled();
            $userHasAdminPermissions = upstream_admin_permissions('disable_project_bugs');

            if (upstream_disable_bugs() || ($areBugsDisabled && ! $userHasAdminPermissions)) {
                return;
            }

            $label        = upstream_bug_label();
            $label_plural = upstream_bug_label_plural();

            $metabox = new_cmb2_box([
                'id'           => $this->prefix . 'bugs',
                'title'        => '<span class="dashicons dashicons-warning"></span> ' . esc_html($label_plural),
                'object_types' => [$this->type],
                'attributes'   => ['data-test' => 'test'],
            ]);

            //Create a default grid
            $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

            /*
             * Outputs some hidden data for dynamic use.
             */
            $metabox->add_field([
                'id'          => $this->prefix . 'hidden',
                'type'        => 'title',
                'description' => '',
                'after'       => 'upstream_admin_output_bug_hidden_data',
                'attributes'  => [
                    'class'        => 'hidden',
                    'data-empty'   => upstream_empty_group('bugs'),
                    'data-publish' => upstream_admin_permissions('publish_project_bugs'),
                ],
            ]);

            $group_field_id = $metabox->add_field([
                'id'           => $this->prefix . 'bugs',
                'type'         => 'group',
                'description'  => '',
                'permissions'  => 'delete_project_bugs', // also set on individual row level
                'options'      => [
                    'group_title'   => esc_html($label) . " {#}",
                    'add_button'    => sprintf(__("Add %s", 'upstream'), esc_html($label)),
                    'remove_button' => sprintf(__("Delete %s", 'upstream'), esc_html($label)),
                    'sortable'      => upstream_admin_permissions('sort_project_bugs'),
                ],
                'before_group' => $this->getBugsFiltersHtml(),
            ]);

            if ( ! $areBugsDisabled) {
                $fields = [];

                $fields[0] = [
                    'id'         => 'id',
                    'type'       => 'text',
                    'before'     => 'upstream_add_field_attributes',
                    'attributes' => [
                        'class' => 'hidden',
                    ],
                ];

                $allowComments = upstreamAreCommentsEnabledOnBugs();
                if ($allowComments) {
                    $fields[0]['before_row'] = '
                    <div class="up-c-tabs-header nav-tab-wrapper nav-tab-wrapper">
                      <a href="#" class="nav-tab nav-tab-active up-o-tab up-o-tab-data" role="tab" data-target=".up-c-tab-content-data">' . __(
                            'Data',
                            'upstream'
                        ) . '</a>
                      <a href="#" class="nav-tab up-o-tab up-o-tab-comments" role="tab" data-target=".up-c-tab-content-comments">' . __('Comments') . '</a>
                    </div>
                    <div class="up-c-tabs-content">
                      <div class="up-o-tab-content up-c-tab-content-data is-active">';
                }

                $fields[1] = [
                    'id'         => 'created_by',
                    'type'       => 'text',
                    'attributes' => [
                        'class' => 'hidden',
                    ],
                ];
                $fields[2] = [
                    'id'         => 'created_time',
                    'type'       => 'text',
                    'attributes' => [
                        'class' => 'hidden',
                    ],
                ];

                // start row
                $fields[10] = [
                    'name'        => __('Title', 'upstream'),
                    'id'          => 'title',
                    'type'        => 'text',
                    'permissions' => 'bug_title_field',
                    'before'      => 'upstream_add_field_attributes',
                    'attributes'  => [
                        'class' => 'bug-title',
                    ],
                ];

                $fields[11] = [
                    'name'             => __('Assigned To', 'upstream'),
                    'id'               => 'assigned_to',
                    'type'             => 'select2',
                    'permissions'      => 'bug_assigned_to_field',
                    'before'           => 'upstream_add_field_attributes',
                    'show_option_none' => true,
                    'options_cb'       => 'upstream_admin_get_all_project_users',
                ];

                // start row
                $fields[20] = [
                    'name'        => __("Description", 'upstream'),
                    'id'          => 'description',
                    'type'        => 'wysiwyg',
                    'permissions' => 'bug_description_field',
                    'before'      => 'upstream_add_field_attributes',
                    'options'     => [
                        'media_buttons' => true,
                        'textarea_rows' => 5,
                    ],
                    'escape_cb'   => 'applyOEmbedFiltersToWysiwygEditorContent',
                ];

                // start row
                $fields[30] = [
                    'name'             => __("Status", 'upstream'),
                    'id'               => 'status',
                    'type'             => 'select',
                    'permissions'      => 'bug_status_field',
                    'before'           => 'upstream_add_field_attributes',
                    'show_option_none' => true, // ** IMPORTANT - do not enforce a value in this field.
                    // An row with no value here is considered to be a deleted row.
                    'options_cb'       => 'upstream_admin_get_bug_statuses',
                    'attributes'       => [
                        'class' => 'bug-status',
                    ],
                ];
                $fields[31] = [
                    'name'             => __("Severity", 'upstream'),
                    'id'               => 'severity',
                    'type'             => 'select',
                    'permissions'      => 'bug_severity_field',
                    'before'           => 'upstream_add_field_attributes',
                    'show_option_none' => true,
                    'options_cb'       => 'upstream_admin_get_bug_severities',
                    'attributes'       => [
                        'class' => 'bug-severity',
                    ],
                ];

                // start row
                $fields[40] = [
                    'name'        => __('Attachments', 'upstream'),
                    'desc'        => '',
                    'id'          => 'file',
                    'type'        => 'file',
                    'permissions' => 'bug_files_field',
                    'before'      => 'upstream_add_field_attributes',
                    'options'     => [
                        'url' => false, // Hide the text input for the url
                    ],
                ];

                $fields[41] = [
                    'name'        => __("Due Date", 'upstream'),
                    'id'          => 'due_date',
                    'type'        => 'up_timestamp',
                    'date_format' => 'Y-m-d',
                    'permissions' => 'bug_due_date_field',
                    'before'      => 'upstream_add_field_attributes',
                    'escape_cb'   => ['UpStream_Admin', 'escapeCmb2TimestampField'],
                ];

                if ($allowComments) {
                    $fields[50] = [
                        'name'      => '&nbsp;',
                        'id'        => 'comments',
                        'type'      => 'comments',
                        'after_row' => '</div><div class="up-o-tab-content up-c-tab-content-comments"></div></div>',
                    ];
                }

                // set up the group grid plugin
                $cmb2GroupGrid = $cmb2Grid->addCmb2GroupGrid($group_field_id);

                // define nuber of rows
                $rows = apply_filters('upstream_bug_metabox_rows', 5);

                // filter the fields & sort numerically
                $fields = apply_filters('upstream_bug_metabox_fields', $fields);
                ksort($fields);

                // loop through ordered fields and add them to the group
                if ($fields) {
                    foreach ($fields as $key => $value) {
                        $fields[$key] = $metabox->add_group_field($group_field_id, $value);
                    }
                }

                // loop through number of rows
                for ($i = 0; $i < $rows; $i++) {

                    // add each row
                    $row[$i] = $cmb2GroupGrid->addRow();

                    // this is our hidden row that must remain as is
                    if ($i == 0) {
                        $row[0]->addColumns([$fields[0], $fields[1], $fields[2]]);
                    } else {

                        // this allows up to 4 columns in each row
                        $array = [];
                        if (isset($fields[$i * 10])) {
                            $array[] = $fields[$i * 10];
                        }
                        if (isset($fields[$i * 10 + 1])) {
                            $array[] = $fields[$i * 10 + 1];
                        }
                        if (isset($fields[$i * 10 + 2])) {
                            $array[] = $fields[$i * 10 + 2];
                        }
                        if (isset($fields[$i * 10 + 3])) {
                            $array[] = $fields[$i * 10 + 3];
                        }

                        // add the fields as columns
                        $row[$i]->addColumns(
                            apply_filters("upstream_bug_row_{$i}_columns", $array)
                        );
                    }
                }
            }

            if ($userHasAdminPermissions) {
                $metabox->add_field([
                    'id'          => $this->prefix . 'disable_bugs',
                    'type'        => 'checkbox',
                    'description' => __('Disable Bugs for this project', 'upstream'),
                ]);
            }
        }



        /* ======================================================================================
                                                SIDEBAR TOP
           ====================================================================================== */

        /**
         * Add the metaboxes
         *
         * @since  0.1.0
         */
        public function details()
        {
            $metabox = new_cmb2_box([
                'id'           => $this->prefix . 'details',
                'title'        => '<span class="dashicons dashicons-admin-generic"></span> ' . sprintf(__(
                        "%s Details",
                        'upstream'
                    ), $this->project_label),
                'object_types' => [$this->type],
                'context'      => 'side',
                'priority'     => 'high',
            ]);

            $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

            $fields = [];

            $fields[0] = [
                'name'             => __('Status', 'upstream'),
                'desc'             => '',
                'id'               => $this->prefix . 'status',
                'type'             => 'select',
                'show_option_none' => true,
                'permissions'      => 'project_status_field',
                'before'           => 'upstream_add_field_attributes',
                'options_cb'       => 'upstream_admin_get_project_statuses',
                'save_field'       => upstream_admin_permissions('project_status_field'),
            ];

            $fields[1] = [
                'name'             => __('Owner', 'upstream'),
                'desc'             => '',
                'id'               => $this->prefix . 'owner',
                'type'             => 'select',
                'show_option_none' => true,
                'permissions'      => 'project_owner_field',
                'before'           => 'upstream_add_field_attributes',
                'options_cb'       => 'upstream_admin_get_all_project_users',
                'save_field'       => upstream_admin_permissions('project_owner_field'),
            ];

            if ( ! is_clients_disabled()) {
                $client_label = upstream_client_label();

                $fields[2] = [
                    'name'             => $client_label,
                    'desc'             => '',
                    'id'               => $this->prefix . 'client',
                    'type'             => 'select',
                    'show_option_none' => true,
                    'permissions'      => 'project_client_field',
                    'before'           => 'upstream_add_field_attributes',
                    'options_cb'       => 'upstream_admin_get_all_clients',
                    'save_field'       => upstream_admin_permissions('project_client_field'),
                ];

                $fields[3] = [
                    'name'              => sprintf(__('%s Users', 'upstream'), $client_label),
                    'id'                => $this->prefix . 'client_users',
                    'type'              => 'multicheck',
                    'select_all_button' => false,
                    'permissions'       => 'project_users_field',
                    'before'            => 'upstream_add_field_attributes',
                    'options_cb'        => 'upstream_admin_get_all_clients_users',
                    'save_field'        => upstream_admin_permissions('project_users_field'),
                ];
            }

            $fields[10] = [
                'name'        => __('Start Date', 'upstream'),
                'desc'        => '',
                'id'          => $this->prefix . 'start',
                'type'        => 'up_timestamp',
                'date_format' => 'Y-m-d',
                'permissions' => 'project_start_date_field',
                'before'      => 'upstream_add_field_attributes',
                'show_on_cb'  => 'upstream_show_project_start_date_field',
                'save_field'  => upstream_admin_permissions('project_start_date_field'),
                'escape_cb'   => ['UpStream_Admin', 'escapeCmb2TimestampField'],
            ];
            $fields[11] = [
                'name'        => __('End Date', 'upstream'),
                'desc'        => '',
                'id'          => $this->prefix . 'end',
                'type'        => 'up_timestamp',
                'date_format' => 'Y-m-d',
                'permissions' => 'project_end_date_field',
                'before'      => 'upstream_add_field_attributes',
                'show_on_cb'  => 'upstream_show_project_end_date_field',
                'save_field'  => upstream_admin_permissions('project_end_date_field'),
                'escape_cb'   => ['UpStream_Admin', 'escapeCmb2TimestampField'],
            ];

            $fields[12] = [
                'name'        => __("Description", 'upstream'),
                'desc'        => '',
                'id'          => $this->prefix . 'description',
                'type'        => 'wysiwyg',
                'permissions' => 'project_description',
                'before'      => 'upstream_add_field_attributes',
                'options'     => [
                    'media_buttons' => false,
                    'textarea_rows' => 3,
                    'teeny'         => true,
                ],
                'save_field'  => upstream_admin_permissions('project_description'),
            ];

            // filter the fields & sort numerically
            $fields = apply_filters('upstream_details_metabox_fields', $fields);
            ksort($fields);

            // loop through ordered fields and add them to the group
            if ($fields) {
                foreach ($fields as $key => $value) {
                    $fields[$key] = $metabox->add_field($value);
                }
            }

            $row = $cmb2Grid->addRow();
            $row->addColumns([$fields[10], $fields[11]]);
        }



        /* ======================================================================================
                                                Files
           ====================================================================================== */
        /**
         * Add the metaboxes
         *
         * @since  0.1.0
         */
        public function files()
        {
            $areFilesDisabled        = upstream_are_files_disabled();
            $userHasAdminPermissions = upstream_admin_permissions('disable_project_files');

            if (upstream_disable_files() || ($areFilesDisabled && ! $userHasAdminPermissions)) {
                return;
            }

            $label        = upstream_file_label();
            $label_plural = upstream_file_label_plural();

            $metabox = new_cmb2_box([
                'id'           => $this->prefix . 'files',
                'title'        => '<span class="dashicons dashicons-paperclip"></span> ' . esc_html($label_plural),
                'object_types' => [$this->type],
            ]);

            //Create a default grid
            $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

            /*
             * Outputs some hidden data for dynamic use.
             */
            $metabox->add_field([
                'id'          => $this->prefix . 'hidden',
                'type'        => 'title',
                'description' => '',
                //'after'       => 'upstream_admin_output_files_hidden_data',
                'attributes'  => [
                    'class'        => 'hidden',
                    'data-empty'   => upstream_empty_group('files'),
                    'data-publish' => upstream_admin_permissions('publish_project_files'),

                ],
            ]);

            $group_field_id = $metabox->add_field([
                'id'           => $this->prefix . 'files',
                'type'         => 'group',
                'description'  => '',
                'permissions'  => 'delete_project_files', // also set on individual row level
                'before_group' => $this->getFilesFiltersHtml(),
                'options'      => [
                    'group_title'   => esc_html($label) . " {#}",
                    'add_button'    => sprintf(__("Add %s", 'upstream'), esc_html($label)),
                    'remove_button' => sprintf(__("Delete %s", 'upstream'), esc_html($label)),
                    'sortable'      => upstream_admin_permissions('sort_project_files'),
                ],
            ]);

            if ( ! $areFilesDisabled) {
                $fields = [];

                // start row
                $fields[0] = [
                    'id'         => 'id',
                    'type'       => 'text',
                    'before'     => 'upstream_add_field_attributes',
                    'attributes' => ['class' => 'hidden'],
                ];

                $allowComments = upstreamAreCommentsEnabledOnFiles();
                if ($allowComments) {
                    $fields[0]['before_row'] = '
                    <div class="up-c-tabs-header nav-tab-wrapper nav-tab-wrapper">
                      <a href="#" class="nav-tab nav-tab-active up-o-tab up-o-tab-data" role="tab" data-target=".up-c-tab-content-data">' . __(
                            'Data',
                            'upstream'
                        ) . '</a>
                      <a href="#" class="nav-tab up-o-tab up-o-tab-comments" role="tab" data-target=".up-c-tab-content-comments">' . __('Comments') . '</a>
                    </div>
                    <div class="up-c-tabs-content">
                      <div class="up-o-tab-content up-c-tab-content-data is-active">';
                }

                $fields[1] = [
                    'id'         => 'created_by',
                    'type'       => 'text',
                    'attributes' => ['class' => 'hidden'],
                ];
                $fields[2] = [
                    'id'         => 'created_time',
                    'type'       => 'text',
                    'attributes' => ['class' => 'hidden'],
                ];

                // start row
                $fields[10] = [
                    'name'        => __('Title', 'upstream'),
                    'id'          => 'title',
                    'type'        => 'text',
                    'permissions' => 'file_title_field',
                    'before'      => 'upstream_add_field_attributes',
                    'attributes'  => [
                        'class' => 'file-title',
                    ],
                ];

                $fields[11] = [
                    'name'             => __('Assigned To', 'upstream'),
                    'id'               => 'assigned_to',
                    'type'             => 'select2',
                    'permissions'      => 'file_assigned_to_field',
                    'before'           => 'upstream_add_field_attributes',
                    'show_option_none' => true,
                    'options_cb'       => 'upstream_admin_get_all_project_users',
                ];

                $fields[20] = [
                    'name'        => esc_html($label),
                    'desc'        => '',
                    'id'          => 'file',
                    'type'        => 'file',
                    'permissions' => 'file_files_field',
                    'before'      => 'upstream_add_field_attributes',
                    'options'     => [
                        'url' => false, // Hide the text input for the url
                    ],
                ];

                // start row
                $fields[30] = [
                    'name'        => __("Description", 'upstream'),
                    'id'          => 'description',
                    'type'        => 'wysiwyg',
                    'permissions' => 'file_description_field',
                    'before'      => 'upstream_add_field_attributes',
                    'options'     => [
                        'media_buttons' => true,
                        'textarea_rows' => 3,
                    ],
                ];

                if ($allowComments) {
                    $fields[40] = [
                        'name'      => '&nbsp;',
                        'id'        => 'comments',
                        'type'      => 'comments',
                        'after_row' => '</div><div class="up-o-tab-content up-c-tab-content-comments"></div></div>',
                    ];
                }

                // set up the group grid plugin
                $cmb2GroupGrid = $cmb2Grid->addCmb2GroupGrid($group_field_id);

                // define nuber of rows
                $rows = apply_filters('upstream_file_metabox_rows', 4);

                // filter the fields & sort numerically
                $fields = apply_filters('upstream_file_metabox_fields', $fields);
                ksort($fields);

                // loop through ordered fields and add them to the group
                if ($fields) {
                    foreach ($fields as $key => $value) {
                        $fields[$key] = $metabox->add_group_field($group_field_id, $value);
                    }
                }

                // loop through number of rows
                for ($i = 0; $i < $rows; $i++) {

                    // add each row
                    $row[$i] = $cmb2GroupGrid->addRow();

                    // this is our hidden row that must remain as is
                    if ($i == 0) {
                        $row[0]->addColumns([$fields[0], $fields[1], $fields[2]]);
                    } else {

                        // this allows up to 4 columns in each row
                        $array = [];
                        if (isset($fields[$i * 10])) {
                            $array[] = $fields[$i * 10];
                        }
                        if (isset($fields[$i * 10 + 1])) {
                            $array[] = $fields[$i * 10 + 1];
                        }
                        if (isset($fields[$i * 10 + 2])) {
                            $array[] = $fields[$i * 10 + 2];
                        }
                        if (isset($fields[$i * 10 + 3])) {
                            $array[] = $fields[$i * 10 + 3];
                        }

                        // add the fields as columns
                        $row[$i]->addColumns(
                            apply_filters("upstream_file_row_{$i}_columns", $array)
                        );
                    }
                }
            }

            if ($userHasAdminPermissions) {
                $metabox->add_field([
                    'id'          => $this->prefix . 'disable_files',
                    'type'        => 'checkbox',
                    'description' => __('Disable Files for this project', 'upstream'),
                ]);
            }
        }


        /* ======================================================================================
                                                SIDEBAR LOW
           ====================================================================================== */
        /**
         * Add the metaboxes
         *
         * @since  0.1.0
         */
        public function sidebar_low()
        {
            $metabox = new_cmb2_box([
                'id'           => $this->prefix . 'activity',
                'title'        => '<span class="dashicons dashicons-update"></span> ' . __('Activity', 'upstream'),
                'object_types' => [$this->type],
                'context'      => 'side', //  'normal', 'advanced', or 'side'
                'priority'     => 'low',  //  'high', 'core', 'default' or 'low'
            ]);

            //Create a default grid
            $cmb2Grid = new \Cmb2Grid\Grid\Cmb2Grid($metabox);

            /*
             * Outputs some hidden data for dynamic use.
             */
            $metabox->add_field([
                'name'   => '',
                'desc'   => '',
                'id'     => $this->prefix . 'activity',
                'type'   => 'title',
                'before' => 'upstream_activity_buttons',
                'after'  => 'upstream_output_activity',
            ]);
        }

        /**
         * Add the metaboxes
         *
         * @since  0.1.0
         */
        public function comments()
        {
            $areCommentsDisabled     = upstream_are_comments_disabled();
            $userHasAdminPermissions = upstream_admin_permissions('disable_project_comments');

            if ( ! self::$allowProjectComments || ($areCommentsDisabled && ! $userHasAdminPermissions)) {
                return;
            }

            $metabox = new_cmb2_box([
                'id'           => $this->prefix . 'discussions',
                'title'        => '<span class="dashicons dashicons-format-chat"></span> ' . upstream_discussion_label(),
                'object_types' => [$this->type],
                'priority'     => 'low',
            ]);

            if ( ! $areCommentsDisabled) {
                $metabox->add_field([
                    'name'         => __('Add new Comment'),
                    'desc'         => '',
                    'id'           => $this->prefix . 'new_message',
                    'type'         => 'wysiwyg',
                    'permissions'  => 'publish_project_discussion',
                    'before'       => 'upstream_add_field_attributes',
                    'after_field'  => '<p class="u-text-right"><button class="button button-primary" type="button" data-action="comments.add_comment" data-nonce="' . wp_create_nonce('upstream:project.add_comment') . '">' . __('Add Comment',
                            'upstream') . '</button></p></div></div>',
                    'after_row'    => 'upstreamRenderCommentsBox',
                    'options'      => [
                        'media_buttons' => true,
                        'textarea_rows' => 5,
                    ],
                    'escape_cb'    => 'applyOEmbedFiltersToWysiwygEditorContent',
                    'before_field' => '<div class="row"><div class="hidden-xs hidden-sm col-md-12 col-lg-12"><label for="' . $this->prefix . 'new_message' . '">' . __('Add new Comment') . '</label>',
                ]);
            }

            if ($userHasAdminPermissions) {
                $metabox->add_field([
                    'id'          => $this->prefix . 'disable_comments',
                    'type'        => 'checkbox',
                    'description' => __('Disable Discussion for this project', 'upstream'),
                ]);
            }
        }

        /**
         * This method ensures WordPress generate and show custom slugs based on project's title automaticaly below the field.
         * Since it will do so only for public posts and Projects-post-type are not public (they would appear on sites searches),
         * we rapidly make it public and switch back to non-public status. This temporary change will not cause search/visibility side effects.
         *
         * Called by the "edit_form_before_permalink" action right before the "edit_form_after_title" hook.
         *
         * @since   1.12.3
         * @static
         *
         * @global  $post_type_object
         */
        public static function makeProjectTemporarilyPublic()
        {
            global $post_type_object;

            if ($post_type_object->name === "project") {
                $post_type_object->public = true;
            }
        }

        /**
         * This method is called right after the makeProjectTemporarilyPublic() and ensures the project is non-public once again. side effects.
         *
         * Called by the "edit_form_after_title" action right after the "edit_form_before_permalink" hook.
         *
         * @since   1.12.3
         * @static
         *
         * @see     self::makeProjectTemporarilyPublic()
         *
         * @global  $post_type_object
         */
        public static function makeProjectPrivateOnceAgain()
        {
            global $post_type_object;

            if ($post_type_object->name === "project") {
                $post_type_object->public = false;
            }
        }

        /**
         * AJAX endpoint that retrieves all comments from all items on the give project.
         *
         * @since   1.13.0
         * @static
         */
        public static function fetchAllItemsComments()
        {
            header('Content-Type: application/json');

            $response = [
                'success' => false,
                'data'    => [
                    'milestones' => [],
                    'tasks'      => [],
                    'bugs'       => [],
                    'files'      => [],
                ],
                'error'   => null,
            ];

            try {
                // Check if the request payload is potentially invalid.
                if (
                    ! defined('DOING_AJAX')
                    || ! DOING_AJAX
                    || empty($_GET)
                    || ! isset($_GET['nonce'])
                    || ! isset($_GET['project_id'])
                    || ! wp_verify_nonce($_GET['nonce'], 'project.get_all_items_comments')
                ) {
                    throw new \Exception(__("Invalid request.", 'upstream'));
                }

                // Check if the project exists.
                $project_id = (int)$_GET['project_id'];
                if ($project_id <= 0) {
                    throw new \Exception(__("Invalid Project.", 'upstream'));
                }

                $usersCache  = [];
                $usersRowset = get_users([
                    'fields' => [
                        'ID',
                        'display_name',
                    ],
                ]);
                foreach ($usersRowset as $userRow) {
                    $userRow->ID *= 1;

                    $usersCache[$userRow->ID] = (object)[
                        'id'     => $userRow->ID,
                        'name'   => $userRow->display_name,
                        'avatar' => getUserAvatarURL($userRow->ID),
                    ];
                }
                unset($userRow, $usersRowset);

                $dateFormat        = get_option('date_format');
                $timeFormat        = get_option('time_format');
                $theDateTimeFormat = $dateFormat . ' ' . $timeFormat;
                $currentTimestamp  = time();

                $user                     = wp_get_current_user();
                $userHasAdminCapabilities = isUserEitherManagerOrAdmin($user);
                $userCanReply             = ! $userHasAdminCapabilities ? user_can(
                    $user,
                    'publish_project_discussion'
                ) : true;
                $userCanModerate          = ! $userHasAdminCapabilities ? user_can($user, 'moderate_comments') : true;
                $userCanDelete            = ! $userHasAdminCapabilities ? $userCanModerate || user_can(
                        $user,
                        'delete_project_discussion'
                    ) : true;

                $commentsStatuses = ['approve'];
                if ($userHasAdminCapabilities || $userCanModerate) {
                    $commentsStatuses[] = 'hold';
                }

                $itemsTypes = ['milestones', 'tasks', 'bugs', 'files'];
                foreach ($itemsTypes as $itemType) {
                    $itemTypeSingular = rtrim($itemType, 's');

                    if ($itemType === 'milestones') {
                        $rowset = \UpStream\Milestones::getInstance()->getMilestonesFromProject($project_id, true);
                    } else {
                        $rowset = array_filter((array)get_post_meta(
                            $project_id,
                            '_upstream_project_' . $itemType,
                            true
                        ));
                    }
                    if (count($rowset) > 0) {
                        foreach ($rowset as $row) {
                            if ( ! is_array($row)
                                 || ! isset($row['id'])
                                 || empty($row['id'])
                            ) {
                                continue;
                            }

                            $comments = (array)get_comments([
                                'post_id'    => $project_id,
                                'status'     => $commentsStatuses,
                                'meta_query' => [
                                    'relation' => 'AND',
                                    [
                                        'key'   => 'type',
                                        'value' => $itemTypeSingular,
                                    ],
                                    [
                                        'key'   => 'id',
                                        'value' => $row['id'],
                                    ],
                                ],
                            ]);

                            if (count($comments) > 0) {
                                $response['data'][$itemType][$row['id']] = [];

                                foreach ($comments as $comment) {
                                    $author = $usersCache[(int)$comment->user_id];

                                    $date = DateTime::createFromFormat('Y-m-d H:i:s', $comment->comment_date_gmt);

                                    $commentData = json_decode(json_encode([
                                        'id'             => $comment->comment_ID,
                                        'parent_id'      => $comment->comment_parent,
                                        'content'        => $comment->comment_content,
                                        'state'          => $comment->comment_approved,
                                        'created_by'     => $author,
                                        'created_at'     => [
                                            'localized' => "",
                                            'humanized' => sprintf(
                                                _x('%s ago', '%s = human-readable time difference', 'upstream'),
                                                human_time_diff($date->getTimestamp(), $currentTimestamp)
                                            ),
                                        ],
                                        'currentUserCap' => [
                                            'can_reply'    => $userCanReply,
                                            'can_moderate' => $userCanModerate,
                                            'can_delete'   => $userCanDelete || $author->id === $user->ID,
                                        ],
                                    ]));

                                    $commentData->created_at->localized = $date->format($theDateTimeFormat);

                                    $commentsCache = [];
                                    if ((int)$comment->comment_parent > 0) {
                                        $parent        = get_comment($comment->comment_parent);
                                        $commentsCache = [
                                            $parent->comment_ID => json_decode(json_encode([
                                                'created_by' => [
                                                    'name' => $parent->comment_author,
                                                ],
                                            ])),
                                        ];
                                    }

                                    ob_start();
                                    upstream_admin_display_message_item($commentData, $commentsCache);
                                    $response['data'][$itemType][$row['id']][] = ob_get_contents();
                                    ob_end_clean();
                                }
                            }
                        }
                    }
                }

                $response['success'] = true;
            } catch (Exception $e) {
                $response['error'] = $e->getMessage();
            }

            wp_send_json($response);
        }

        /**
         * Define select2 CMB2 field settings.
         *
         * @param \CMB2_Field $field      Current CMB2_Field object.
         * @param string      $value      Current escaped field value.
         * @param int         $object_id  Project ID.
         * @param string      $objectType Current object type.
         * @param \CMB2_Types $fieldType  Current field type object.
         *
         * @since   1.16.0
         * @static
         *
         */
        public static function renderSelect2Field($field, $value, $object_id, $objectType, $fieldType)
        {
            if ( ! is_array($value)) {
                $value = explode('#', (string)$value);
            }

            $value = array_filter(array_unique($value));

            $fieldName = $field->args['_name'];
            if ( ! preg_match('/\[\]$/', $fieldName)) {
                $fieldName .= '[]';
            }

            $options = [];
            if (count($field->args['options']) === 0) {
                if ( ! empty($field->args['options_cb']) && is_callable($field->args['options_cb'])) {
                    $options = call_user_func($field->args['options_cb']);
                }
            } ?>
            <select
                    id="<?php echo $field->args['id']; ?>"
                    name="<?php echo esc_attr($fieldName); ?>"
                    class="o-select2"
                    multiple
                    data-placeholder="<?php _e('None', 'upstream'); ?>"
                    tabindex="-1">
                <?php foreach ($options as $optionValue => $optionTitle): ?>
                    <option value="<?php echo esc_attr($optionValue); ?>"<?php echo in_array(
                        $optionValue,
                        $value
                    ) ? ' selected' : ''; ?>><?php echo esc_html($optionTitle); ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        }

        /**
         * Sanitizes select2 fields before they're saved.
         *
         * @param mixed          $overrideValue Sanitization override value to return.
         * @param mixed          $value         Actual field value.
         * @param int            $object_id     Project ID.
         * @param string         $object_type   Current object type.
         * @param \CMB2_Sanitize $sanitizer     Current sanitization object.
         *
         * @since   1.16.0
         * @static
         *
         */
        public static function sanitizeSelect2Field($overrideValue, $value, $object_id, $object_type, $sanitizer)
        {
            $value = array_filter(array_unique((array)$value));

            return $value;
        }
    }

endif;
