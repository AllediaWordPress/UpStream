<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! class_exists('UpStream_Admin_Project_Columns')) :

    /**
     * Admin columns
     *
     * @version 0.1.0
     */
    class UpStream_Admin_Project_Columns
    {

        /**
         * Constructor
         *
         * @since 0.1.0
         */
        public function __construct()
        {
            $this->hooks();
            $this->filterAllowedProjects();

            self::$noneTag = '<i style="color: #CCC;">' . __('none', 'upstream') . '</i>';
        }

        /**
         * Array of projects ids current user is allowed to access.
         *
         * @since   1.12.2
         * @access  private
         *
         * @see     $this->filterAllowedProjects()
         *
         * @var     array $allowedProjects
         */
        private $allowedProjects = [];

        /**
         * Indicates either user can access all projects due his/her current roles.
         *
         * @since   1.12.2
         * @access  private
         *
         * @see     $this->filterAllowedProjects()
         *
         * @var     bool $allowAllProjects
         */
        private $allowAllProjects = false;

        public function hooks()
        {
            add_filter('manage_project_posts_columns', [$this, 'project_columns']);
            add_action('manage_project_posts_custom_column', [$this, 'project_data'], 10, 2);

            // sorting
            add_filter('manage_edit-project_sortable_columns', [$this, 'table_sorting']);
            add_filter('request', [$this, 'project_orderby_status']);
            add_filter('request', [$this, 'project_orderby_dates']);
            add_filter('request', [$this, 'project_orderby_progress']);

            // filtering
            add_action('restrict_manage_posts', [$this, 'table_filtering']);
            add_action('parse_query', [$this, 'filter']);
        }

        /**
         * Retrieve all projects current user are allowed to access.
         * This info is used on filter() method to ensure the user will see only projects he's allowed to see.
         * We cannot do this check within filter() itself to avoid infinite loops.
         *
         * @since   1.12.2
         *
         * @see     $this->filter()
         */
        public function filterAllowedProjects()
        {
            // Fetch current user.
            $user = wp_get_current_user();

            $this->allowAllProjects = count(array_intersect(
                    (array)$user->roles,
                    ['administrator', 'upstream_manager']
                )) > 0;
            if ( ! $this->allowAllProjects) {
                // Retrieve all projects current user can access.
                $allowedProjects = upstream_get_users_projects($user);
                // Stores the projects ids so they can be used on filter() function.
                $this->allowedProjects = array_keys($allowedProjects);
                // Retrieve the global query object.
                global $wp_query;
                // Assign this custom property so we know only this time the query will be filtered based on these ids.
                $wp_query->filterAllowedProjects = true;
            }
        }

        /**
         * Set columns for project
         */
        public function project_columns($defaults)
        {
            $post_type = $_GET['post_type'];

            $columns    = [];
            $taxonomies = [];

            /* Get taxonomies that should appear in the manage posts table. */
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            $taxonomies = wp_filter_object_list($taxonomies, ['show_admin_column' => true], 'and', 'name');

            /* Allow devs to filter the taxonomy columns. */
            $taxonomies = apply_filters(
                "manage_taxonomies_for_upstream_{$post_type}_columns",
                $taxonomies,
                $post_type
            );
            $taxonomies = array_filter($taxonomies, 'taxonomy_exists');

            /* Loop through each taxonomy and add it as a column. */
            foreach ($taxonomies as $taxonomy) {
                $columns['taxonomy-' . $taxonomy] = get_taxonomy($taxonomy)->labels->name;
            }

            $defaults['owner'] = __('Owner', 'upstream');

            if ( ! is_clients_disabled()) {
                $defaults['client'] = __('Client', 'upstream');
            }

            $defaults['start'] = __('Start', 'upstream');
            $defaults['end']   = __('End', 'upstream');

            if ( ! upstream_disable_tasks()) {
                $defaults['tasks'] = upstream_task_label_plural();
            }

            if ( ! upstream_disable_bugs()) {
                $defaults['bugs'] = upstream_bug_label_plural();
            }
            $defaults['progress'] = __('Progress', 'upstream');
            $defaults['messages'] = '<div style="text-align: center;"><span class="dashicons dashicons-admin-comments"></span><span class="s-hidden-on-tables">' . __('Comments') . '</span></div>';

            $defaults = ['project-status' => ''] + $defaults;

            return $defaults;
        }


        private static $noneTag = '';
        private static $usersCache = [];
        private static $clientsCache = [];
        private static $tasksStatuses = [];
        private static $bugsStatuses = [];
        private static $areTasksDisabled = null;
        private static $areBugsDisabled = null;

        public function project_data($column_name, $post_id)
        {
            if ($column_name === 'project-status') {
                $status = upstream_project_status_color($post_id);

                if ( ! empty($status['status'])) {
                    echo '<div title="' . esc_attr($status['status']) . '" style="width: 100%; position: absolute; top: 0px; left: 0px; overflow: hidden; height: 100%; border-left: 2px solid ' . esc_attr($status['color']) . '" class="' . esc_attr(strtolower($status['status'])) . '"></div>';
                }

                return;
            }

            if ($column_name === 'owner') {
                $owner_id = (int)upstream_project_owner_id($post_id);
                if ($owner_id > 0) {
                    if ( ! isset(self::$usersCache[$owner_id])) {
                        $user                        = get_user_by('id', $owner_id);
                        self::$usersCache[$user->ID] = $user->display_name;
                        unset($user);
                    }

                    echo self::$usersCache[$owner_id];
                } else {
                    echo self::$noneTag;
                }

                return;
            }

            if ($column_name === 'client') {
                $client_id = (int)upstream_project_client_id($post_id);
                if ($client_id > 0) {
                    if ( ! isset($clientsCache[$client_id])) {
                        $client                         = get_post($client_id);
                        self::$clientsCache[$client_id] = $client->post_title;
                        unset($client);
                    }

                    echo self::$clientsCache[$client_id];
                } else {
                    echo self::$noneTag;
                }

                return;
            }

            if ($column_name === 'start') {
                $startDate = (int)upstream_project_start_date($post_id);
                if ($startDate > 0) {
                    echo '<span class="start-date">' . upstream_format_date($startDate) . '</span>';
                } else {
                    echo self::$noneTag;
                }

                return;
            }

            if ($column_name === 'end') {
                $endDate = (int)upstream_project_end_date($post_id);
                if ($endDate > 0) {
                    echo '<span class="end-date">' . upstream_format_date($endDate) . '</span>';
                } else {
                    echo self::$noneTag;
                }

                return;
            }

            if ($column_name === 'tasks') {
                if (self::$areTasksDisabled === null) {
                    self::$areTasksDisabled = (bool)upstream_are_tasks_disabled();
                }

                if ( ! self::$areTasksDisabled) {
                    $counts = upstream_project_tasks_counts($post_id);

                    if (empty($counts)) {
                        echo self::$noneTag;
                    } else {
                        if (empty(self::$tasksStatuses)) {
                            self::$tasksStatuses = getTasksStatuses();
                        }

                        foreach ($counts as $taskStatusId => $count) {
                            $taskStatus = isset(self::$tasksStatuses[$taskStatusId])
                                ? self::$tasksStatuses[$taskStatusId]
                                : [
                                    'color' => '#aaaaaa',
                                    'name'  => $taskStatusId,
                                ];

                            printf(
                                '<span class="status %s" style="border-color: %s">
                                <span class="count" style="background-color: %2$s">%3$s</span> %4$s
                            </span>',
                                esc_attr(strtolower($taskStatus['name'])),
                                esc_attr($taskStatus['color']),
                                $count,
                                $taskStatus['name']
                            );
                        }
                    }
                }

                return;
            }

            if ($column_name === 'bugs') {
                if (self::$areBugsDisabled === null) {
                    self::$areBugsDisabled = (bool)upstream_are_bugs_disabled();
                }

                if ( ! self::$areBugsDisabled) {
                    $counts = upstream_project_bugs_counts($post_id);
                    if (empty($counts)) {
                        echo self::$noneTag;
                    } else {
                        if (empty(self::$bugsStatuses)) {
                            self::$bugsStatuses = getBugsStatuses();
                        }

                        foreach ($counts as $bugStatusId => $count) {
                            $bugStatus = isset(self::$bugsStatuses[$bugStatusId])
                                ? self::$bugsStatuses[$bugStatusId]
                                : [
                                    'color' => '#aaaaaa',
                                    'name'  => $bugStatusId,
                                ];

                            printf(
                                '<span class="status %s" style="border-color: %s">
                                <span class="count" style="background-color: %2$s">%3$s</span> %4$s
                            </span>',
                                esc_attr(strtolower($bugStatus['name'])),
                                esc_attr($bugStatus['color']),
                                $count,
                                $bugStatus['name']
                            );
                        }
                    }
                }

                return;
            }

            if ($column_name === 'progress') {
                $progress = (int)upstream_project_progress($post_id);

                echo '<div style="text-align: center;">' . $progress . '%</div>';

                return;
            }

            if ($column_name === 'messages') {
                echo '<div style="text-align: center;">';

                $count = (int)getProjectCommentsCount($post_id);
                if ($count > 0) {
                    echo '<a href="' . esc_url(get_edit_post_link($post_id) . '#_upstream_project_discussions') . '"><span>' . esc_html($count) . '</a></span>';
                } else {
                    echo self::$noneTag;
                }

                echo '</div>';
            }
        }


        /*
         * Sorting the table
         */
        public function table_sorting($columns)
        {
            $columns['project-status'] = 'project-status';
            $columns['start']          = 'start';
            $columns['end']            = 'end';
            $columns['progress']       = 'progress';

            return $columns;
        }


        public function project_orderby_status($vars)
        {
            if (isset($vars['orderby']) && 'project-status' == $vars['orderby']) {
                $vars = array_merge($vars, [
                    'meta_key' => '_upstream_project_status',
                    'orderby'  => 'meta_value',
                ]);
            }

            return $vars;
        }

        public function project_orderby_dates($vars)
        {
            if (isset($vars['orderby']) && 'start' == $vars['orderby']) {
                $vars = array_merge($vars, [
                    'meta_key' => '_upstream_project_start',
                    'orderby'  => 'meta_value_num',
                ]);
            }

            if (isset($vars['orderby']) && 'end' == $vars['orderby']) {
                $vars = array_merge($vars, [
                    'meta_key' => '_upstream_project_end',
                    'orderby'  => 'meta_value_num',
                ]);
            }

            return $vars;
        }

        public function project_orderby_progress($vars)
        {
            if (isset($vars['orderby']) && 'progress' == $vars['orderby']) {
                $vars = array_merge($vars, [
                    'meta_key' => '_upstream_project_progress',
                    'orderby'  => 'meta_value_num',
                ]);
            }

            return $vars;
        }

        public function table_filtering()
        {
            global $pagenow;

            $isMultisite = is_multisite();
            if ($isMultisite) {
                $currentPage = isset($_SERVER['PHP_SELF']) ? preg_replace(
                    '/^\/wp-admin\//i',
                    '',
                    $_SERVER['PHP_SELF']
                ) : '';
            } else {
                $currentPage = $pagenow;
            }

            $postType = isset($_GET['post_type']) ? $_GET['post_type'] : null;
            if ($currentPage === 'edit.php'
                && $postType === 'project'
            ) {
                $projectOptions = get_option('upstream_projects');
                $statuses       = $projectOptions['statuses'];
                unset($projectOptions);

                $selectedStatus = isset($_GET['project-status']) ? $_GET['project-status'] : ''; ?>
                <select name="project-status" id="project-status" class="postform">
                    <option value="">
                        <?php printf(__('Show all %s', 'upstream'), __('statuses', 'upstream')); ?>
                    </option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo $status['name']; ?>" <?php selected(
                            $selectedStatus,
                            $status['name']
                        ); ?>>
                            <?php echo $status['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php
                // Filter by Project Owner.
                $users = upstream_admin_get_all_project_users();

                $selectedOwner = isset($_GET['project-owner']) ? (int)$_GET['project-owner'] : -1; ?>
                <select name="project-owner" id="project-owner" class="postform">
                    <option value="">
                        <?php printf(__('Show all %s', 'upstream'), __('owners', 'upstream')); ?>
                    </option>
                    <?php foreach ($users as $ownerId => $ownerName): ?>
                        <option
                                value="<?php echo $ownerId; ?>" <?php echo $selectedOwner === $ownerId ? ' selected' : ''; ?>>
                            <?php echo $ownerName; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php
                if (!is_clients_disabled()) {
                    // Filter by Project Client.
                    $clients          = upstream_wp_get_clients();
                    $selectedClientId = isset($_GET['project-client']) ? (int)$_GET['project-client'] : -1; ?>
                    <select name="project-client" id="project-client" class="postform">
                        <option value="">
                            <?php printf(__('Show all %s', 'upstream'), upstream_client_label_plural(true)); ?>
                        </option>
                        <?php foreach ($clients as $clientId => $clientName): ?>
                            <option
                                    value="<?php echo $clientId; ?>" <?php echo $selectedClientId === (int)$clientId ? ' selected' : ''; ?>>
                                <?php echo $clientName; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                }
            }
        }

        public function filter($query)
        {
            $isAdmin = is_admin();
            if ( ! $isAdmin) {
                return;
            }

            $postType = isset($_GET['post_type']) ? $_GET['post_type'] : 'post';
            if ($postType !== 'project') {
                return;
            }

            $isMultisite = is_multisite();
            if ($isMultisite) {
                $currentPage = isset($_SERVER['PHP_SELF']) ? preg_replace(
                    '/^\/wp-admin\//i',
                    '',
                    $_SERVER['PHP_SELF']
                ) : '';
            } else {
                global $pagenow;

                $currentPage = $pagenow;
            }

            if ($currentPage !== 'edit.php') {
                return;
            }

            // RSD: moved this for item 886/887
            if ( ! $this->allowAllProjects && $query->filterAllowedProjects) {
                $query->query_vars            = array_merge($query->query_vars, [
                    'post__in' => count($this->allowedProjects) === 0 ? ['making_sure_no_project_is_returned'] : $this->allowedProjects,
                ]);
                $query->filterAllowedProjects = null;
            }

            $shouldExit = true;
            $filters    = ['status', 'owner', 'client'];
            foreach ($filters as $filterName) {
                $filterKey = 'project-' . $filterName;

                if (isset($_GET[$filterKey]) && ! empty($_GET[$filterKey])) {
                    $shouldExit = false;
                }
            }

            if ($shouldExit) {
                return;
            }


            $metaQuery = [];

            $projectStatus = isset($_GET['project-status']) ? (string)$_GET['project-status'] : '';
            if (strlen($projectStatus) > 0) {
                $metaQuery[] = [
                    'key'     => '_upstream_project_status',
                    'value'   => $projectStatus,
                    'compare' => '=',
                ];
            }

            $projectOwnerId = isset($_GET['project-owner']) ? (int)$_GET['project-owner'] : 0;
            if ($projectOwnerId > 0) {
                $metaQuery[] = [
                    'key'     => '_upstream_project_owner',
                    'value'   => $projectOwnerId,
                    'compare' => '=',
                ];
            }

            $projectClientId = isset($_GET['project-client']) ? (int)$_GET['project-client'] : 0;
            if ($projectClientId > 0) {
                $metaQuery[] = [
                    'key'     => '_upstream_project_client',
                    'value'   => $projectClientId,
                    'compare' => '=',
                ];
            }

            $metaQueryCount = count($metaQuery);
            if ($metaQueryCount > 0) {
                if ($metaQueryCount === 1) {
                    $query->query_vars['meta_key']   = $metaQuery[0]['key'];
                    $query->query_vars['meta_value'] = $metaQuery[0]['value'];
                } else {
                    $metaQuery['relation'] = 'AND';

                    $query->query_vars['meta_query'] = $metaQuery;
                }

                $query->meta_query = $metaQuery;
            }
        }
    }

    new UpStream_Admin_Project_Columns;

endif;
