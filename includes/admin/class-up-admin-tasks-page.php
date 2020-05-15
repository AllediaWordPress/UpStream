<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}


class Upstream_Task_List extends WP_List_Table
{
    private static $tasksStatuses = [];
    private static $milestones = null;
    public $task_label = '';
    public $task_label_plural = '';
    private $columns = [];

    /*
     * Displays the filtering links above the table
     */

    /** Class constructor */
    public function __construct()
    {
        $this->task_label        = upstream_task_label();
        $this->task_label_plural = upstream_task_label_plural();

        parent::__construct([
            'singular' => $this->task_label,
            'plural'   => $this->task_label_plural,
            'ajax'     => false //does this table support ajax?
        ]);
    }

    public function get_columns()
    {
        $columnsList = [
            'title'       => $this->task_label,
            'progress'    => __('Progress', 'upstream'),
            'project'     => upstream_project_label(),
            'milestone'   => upstream_milestone_label(),
            'id'          => __('ID', 'upstream'),
            'assigned_to' => __('Assigned To', 'upstream'),
            'end_date'    => __('End Date', 'upstream'),
            'status'      => __('Status', 'upstream'),
        ];

        if (upstream_disable_milestones()) {
            unset($columnsList['milestone']);
        }

        return $columns = apply_filters('upstream_admin_task_page_columns', $columnsList);
    }

    public function get_views()
    {
        $views = [];

        if ( ! empty($_REQUEST['status'])) {
            $current = esc_html($_REQUEST['status']);
        } elseif ( ! empty($_REQUEST['view'])) {
            $current = esc_html($_REQUEST['view']);
        } else {
            $current = 'all';
        }

        //All link
        $all_class = ($current == 'all' ? ' class="current"' : '');
        $all_url   = remove_query_arg(['status', 'view']);
        $all_count = upstream_count_total('tasks');

        $views['all'] = "<a href='" . esc_url($all_url) . "' " . esc_attr($all_class) . " >" . __(
                'All',
                'upstream'
            ) . "</a>(" . esc_html($all_count) . ")";

        //Mine link
        $mine_class    = ($current == 'mine' ? ' class="current"' : '');
        $mine_url      = add_query_arg(['view' => 'mine', 'status' => false]);
        $mine_count    = upstream_count_assigned_to('tasks');
        $views['mine'] = "<a href='" . esc_url($mine_url) . "' " . esc_attr($mine_class) . " >" . __(
                'Mine',
                'upstream'
            ) . "</a>(" . esc_html($mine_count) . ")";

        // links for other statuses
        $option   = get_option('upstream_tasks');
        $statuses = isset($option['statuses']) ? $option['statuses'] : '';
        $counts   = self::count_statuses();

        if ($statuses) {
            // check if user wants to hide completed tasks
            $hide = get_user_option('upstream_completed_tasks', get_current_user_id());

            foreach ($statuses as $status) {
                if ($hide === 'on' && self::hide_completed($status['name'])) {
                    continue;
                }

                $stati         = strtolower($status['id']);
                $class         = ($current == $stati ? ' class="current"' : '');
                $url           = add_query_arg(['status' => $stati, 'view' => false, 'paged' => false]);
                $count         = isset($counts[$status['name']]) ? $counts[$status['name']] : 0;
                $views[$stati] = "<a href='" . esc_url($url) . "' " . esc_attr($class) . " >" . esc_html($status['name']) . "</a>(" . esc_html($count) . ")";
            }
        }

        return $views;
    }

    /**
     * Returns the count of each status
     *
     * @return array
     */
    public static function count_statuses()
    {
        $statuses = getTasksStatuses();
        $rowset   = self::get_tasks();
        $data     = [];

        if (empty($rowset)) {
            return $data;
        }

        foreach ($rowset as $row) {
            if (isset($row['status'])
                && ! empty($row['status'])
                && isset($statuses[$row['status']])
            ) {
                $statusTitle = $statuses[$row['status']]['name'];
                if (isset($data[$statusTitle])) {
                    $data[$statusTitle]++;
                } else {
                    $data[$statusTitle] = 1;
                }
            }
        }

        return $data;
    }

    /**
     * Retrieve all tasks from all projects.
     *
     * @return array
     */
    public static function get_tasks()
    {
        $args = [
            'post_type'      => 'project',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_upstream_project_tasks',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        // The Query
        $the_query = new WP_Query($args);

        // The Loop
        if ( ! $the_query->have_posts()) {
            return;
        }

        $tasks = [];
        while ($the_query->have_posts()) : $the_query->the_post();

            $post_id = get_the_ID();
            if (upstream_are_tasks_disabled($post_id)) {
                continue;
            }

            $meta  = get_post_meta($post_id, '_upstream_project_tasks', true);
            $owner = get_post_meta($post_id, '_upstream_project_owner', true);

            if ($meta) :
                foreach ($meta as $meta_val => $task) {

                    // set up the data for each column
                    $task['title']       = isset($task['title']) ? $task['title'] : __('(no title)', 'upstream');
                    $task['project']     = get_the_title($post_id);
                    $task['owner']       = $owner;
                    $task['assigned_to'] = isset($task['assigned_to']) ? $task['assigned_to'] : 0;
                    $task['milestone']   = isset($task['milestone']) ? $task['milestone'] : '';
                    $task['start_date']  = isset($task['start_date']) ? $task['start_date'] : '';
                    $task['end_date']    = isset($task['end_date']) ? $task['end_date'] : '';
                    $task['status']      = isset($task['status']) ? $task['status'] : '';
                    $task['progress']    = isset($task['progress']) ? $task['progress'] : '';
                    $task['notes']       = isset($task['notes']) ? $task['notes'] : '';
                    $task['project_id']  = $post_id; // add the post id to each task

                    // check if we can add the task to the list
                    $user_id = get_current_user_id();
                    // $option     = get_option( 'upstream_tasks' );
                    // $hide       = $option['hide_closed'];

                    // // check if user wants to hide completed tasks
                    // if ( $hide == 'on' && self::hide_completed( $task['status'] ) )
                    //     continue;


                    $tasks[] = $task;
                }

            endif;

        endwhile;

        return $tasks;
    }

    /**
     *
     *
     * @return null|int
     */
    public static function hide_completed($status)
    {
        $option   = get_option('upstream_tasks');
        $statuses = isset($option['statuses']) ? $option['statuses'] : '';

        if ( ! $statuses) {
            return false;
        }

        $types = wp_list_pluck($statuses, 'type', 'name');

        foreach ($types as $key => $value) {
            if ($key == $status && $value == 'open') {
                return false;
            }
        }

        return true;
    }

    public function extra_tablenav($which)
    {
        if ($which != 'top') {
            return;
        } ?>

        <div class="alignleft actions">

            <?php
            if ( ! is_singular()) {
                $projects = $this->get_projects_unique();
                if ( ! empty($projects)) {
                    ?>

                    <select name='project' id='project' class='postform'>
                        <option value=''><?php printf(__('Show all %s', 'upstream'), 'projects') ?></option>
                        <?php foreach ($projects as $project_id => $title) {
                            ?>
                            <option
                                    value="<?php echo $project_id ?>" <?php isset($_GET['project']) ? selected(
                                $_GET['project'],
                                $project_id
                            ) : ''; ?>><?php echo esc_html($title) ?></option>
                            <?php
                        } ?>
                    </select>

                    <?php
                }

                $assigned_to = $this->get_assigned_to_unique();
                if ( ! empty($assigned_to)) {
                    ?>

                    <select name='assigned_to' id='assigned_to' class='postform'>
                        <option value=''><?php printf(__('Show all %s', 'upstream'), 'users') ?></option>
                        <?php foreach ($assigned_to as $user_id => $user) {
                            ?>
                            <option
                                    value="<?php echo $user_id ?>" <?php isset($_GET['assigned_to']) ? selected(
                                $_GET['assigned_to'],
                                $user_id
                            ) : ''; ?>><?php echo esc_html($user) ?></option>
                            <?php
                        } ?>
                    </select>

                    <?php
                }

                $status = self::getTasksStatuses();

                if ( ! empty($status)) {
                    ?>

                    <select name='status' id='status' class='postform'>
                        <option value=''><?php printf(__('Show all %s', 'upstream'), 'statuses') ?></option>
                        <?php foreach ($status as $stati) {
                            if (is_array($stati)) {
                                $statusTitle = $stati['name'];
                                $statusId    = $stati['id'];
                            } else {
                                $statusTitle = $stati;
                                $statusId    = $stati;
                            } ?>
                            <option
                                    value="<?php echo $statusId; ?>" <?php isset($_REQUEST['status']) ? selected(
                                $_REQUEST['status'],
                                $statusId
                            ) : ''; ?>><?php echo esc_html($statusTitle) ?></option>
                            <?php
                        } ?>
                    </select>

                    <?php
                }

                submit_button(__('Filter'), 'button', 'filter', false);
            } ?>
        </div>
        <?php
    }

    private function get_projects_unique()
    {
        $tasks = self::get_tasks();
        if (empty($tasks)) {
            return;
        }

        $items = wp_list_pluck($tasks, 'project', 'project_id');
        $items = array_unique($items);
        $items = array_filter($items);

        return $items;
    }

    private function get_assigned_to_unique()
    {
        $tasks = (array)self::get_tasks();
        if (count($tasks) === 0) {
            return;
        }

        $rowset = wp_list_pluck($tasks, 'assigned_to');

        $data = [];

        $setUserNameIntoData = function ($user_id) use (&$data) {
            if ( ! isset($data[$user_id])) {
                $data[$user_id] = upstream_users_name($user_id);
            }
        };

        foreach ($rowset as $assignees) {
            if ( ! is_array($assignees)) {
                $assignees = (array)$assignees;
            }

            $assignees = array_unique(array_filter(array_map('intval', $assignees)));
            foreach ($assignees as $assignee_id) {
                $setUserNameIntoData($assignee_id);
            }
        }

        return $data;
    }

    private static function getTasksStatuses()
    {
        if (empty(self::$tasksStatuses)) {
            $rowset = self::get_tasks();
            if (!$rowset || count($rowset) === 0) {
                return;
            }

            $statuses = getTasksStatuses();

            $data = [];

            foreach ($rowset as $row) {
                if ( ! empty($row['status'])
                     && isset($row['status'])
                ) {
                    $data[$row['status']] = isset($statuses[$row['status']])
                        ? $statuses[$row['status']]
                        : $row['status'];
                }
            }

            self::$tasksStatuses = $data;
        } else {
            $data = self::$tasksStatuses;
        }

        return $data;
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array  $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        $output = "";

        switch ($column_name) {

            case 'title':

                $output = '<a class="row-title" href="' . get_edit_post_link($item['project_id']) . '">' . esc_html($item['title']) . '</a>';

                return $output;

            case 'id':
                return $item['id'];

            case 'project':

                $output = '<a href="' . get_edit_post_link($item['project_id']) . '">' . esc_html($item['project']) . '</a>';
                $output .= '<br>' . esc_html(upstream_project_progress($item['project_id'])) . '% ' . __(
                        'Complete',
                        'upstream'
                    );

                return $output;

            case 'milestone':
                if (self::$milestones === null) {
                    self::$milestones = getMilestones();
                }

                if ( ! upstream_are_milestones_disabled($item['project_id'])) {
                    if (isset($item['milestone']) && ! empty($item['milestone'])) {
                        $milestone = upstream_project_milestone_by_id($item['project_id'], $item['milestone']);
                        $progress  = $milestone['progress'] ? $milestone['progress'] : '0';

                        $milestoneTitle = isset(self::$milestones[$milestone['milestone']])
                            ? self::$milestones[$milestone['milestone']]['title']
                            : $milestone['milestone'];

                        return $milestone ? esc_html($milestoneTitle) . '<br>' . esc_html($progress) . '% ' . __(
                                'Complete',
                                'upstream'
                            ) : '';
                    }
                }

                return '<span><i style="color: #CCC;">' . __('none', 'upstream') . '</i></span>';

            case 'assigned_to':
                $assignees = isset($item['assigned_to']) ? array_filter((array)$item['assigned_to']) : [];
                if (count($assignees) > 0) {
                    $users = get_users([
                        'fields'  => [
                            'ID',
                            'display_name',
                        ],
                        'include' => $assignees,
                    ]);

                    $html = [];

                    $currentUserId = get_current_user_id();
                    foreach ($users as $user) {
                        if ((int)$user->ID === $currentUserId) {
                            $html[] = '<span class="mine">' . esc_html($user->display_name) . '</span>';
                        } else {
                            $html[] = '<span>' . esc_html($user->display_name) . '</span>';
                        }
                    }

                    return implode(',<br>', $html);
                } else {
                    return '<span><i style="color: #CCC;">' . __('none', 'upstream') . '</i></span>';
                }
            // no break
            case 'end_date':
                if (isset($item['end_date']) && (int)$item['end_date'] > 0) {
                    return '<span class="end-date">' . upstream_format_date($item['end_date']) . '</span>';
                } else {
                    return '<span><i style="color: #CCC;">' . __('none', 'upstream') . '</i></span>';
                }

            // no break
            case 'status':
                if ( ! isset($item['status'])
                     || empty($item['status'])
                ) {
                    return '<span><i style="color: #CCC;">' . __('none', 'upstream') . '</i></span>';
                }

                $status = self::$tasksStatuses[$item['status']];

                if (is_array($status)) {
                    $statusTitle = $status['name'];
                    $statusColor = $status['color'];
                } else {
                    $statusTitle = $status;
                    $statusColor = '#aaaaaa';
                }

                $output = sprintf(
                    '<span class="status %s" style="border-color: %s">
                        <span class="count" style="background-color: %2$s">&nbsp;</span> %3$s
                    </span>',
                    esc_attr(strtolower($statusTitle)),
                    esc_attr($statusColor),
                    esc_html($statusTitle)
                );

                return $output;

            case 'progress':

                $task     = upstream_project_item_by_id($item['project_id'], $item['id']);
                $progress = isset($task['progress']) && $task['progress'] ? $task['progress'] : '0';
                $output   = esc_html($progress) . '%';

                return $output;

            default:
                //return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortable_columns = [
            'title'       => ['title', true],
            // 'project'       => array( 'project', false ),
            // 'milestone'     => array( 'milestone', false ),
            'assigned_to' => ['assigned_to', false],
            'end_date'    => ['end_date', false],
            'status'      => ['status', false],
            'progress'    => ['progress', false],
        ];

        return $sortable_columns;
    }

    /** Text displayed when no customer data is available */
    public function no_items()
    {
        printf(__('No %s avaliable.', 'upstream'), strtolower($this->task_label_plural));
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {
        $this->_column_headers = $this->get_column_info();

        $per_page     = $this->get_items_per_page('tasks_per_page', 10);
        $current_page = $this->get_pagenum();
        $this->items  = self::output_tasks($per_page, $current_page);

        $unpaginated_items = self::get_tasks();
        $unpaginated_items = self::sort_filter($unpaginated_items);

        $total_items = count($unpaginated_items);

        $this->set_pagination_args([
            'total_items' => $total_items, //We have to calculate the total number of items
            'per_page'    => $per_page //We have to determine how many items to show on a page
        ]);
    }

    /**
     * Output tasks
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function output_tasks($per_page = 10, $page_number = 1)
    {

        // get the tasks
        $tasks = self::get_tasks();

        // sort & filter the tasks
        $tasks = self::sort_filter($tasks);

        // does the paging
        if ( ! $tasks) {
            $output = 0;
        } else {
            $output = array_slice($tasks, ($page_number - 1) * $per_page, $per_page);
        }

        return $output;
    }

    public static function sort_filter($tasks = [])
    {
        if ( ! is_array($tasks) || count($tasks) === 0) {
            return [];
        }

        // filtering
        $the_tasks = $tasks; // store the tasks array
        $status    = (isset($_REQUEST['status']) && $_REQUEST['status'] ? $_REQUEST['status'] : 'all');
        if ($status != 'all') {
            if ( ! empty($the_tasks)) {
                $tasks = []; // reset the tasks array
                foreach ($the_tasks as $key => $task) {
                    $stat = isset($task['status']) ? $task['status'] : null;
                    if (strtolower($stat) == $status) {
                        $tasks[] = $task;
                    }
                }
            }
        }

        $preset = isset($_REQUEST['view']) && $_REQUEST['view'] ? $_REQUEST['view'] : 'all';
        if ($preset === 'mine') {
            $currentUserId = (int)get_current_user_id();

            foreach ($tasks as $rowIndex => $row) {
                $assignees = isset($row['assigned_to']) ? array_map('intval', (array)$row['assigned_to']) : [];

                if ( ! in_array($currentUserId, $assignees)) {
                    unset($tasks[$rowIndex]);
                }
            }
        }

        $project = (isset($_REQUEST['project']) && $_REQUEST['project'] ? $_REQUEST['project'] : '');
        if ( ! empty($tasks) && ! empty($project)) {
            foreach ($tasks as $key => $task) {
                if ($task['project_id'] != $project) {
                    unset($tasks[$key]);
                }
            }
        }

        $assigned_to = isset($_REQUEST['assigned_to']) && $_REQUEST['assigned_to'] ? (int)$_REQUEST['assigned_to'] : 0;
        if ($assigned_to > 0) {
            foreach ($tasks as $rowIndex => $row) {
                $assignees = isset($row['assigned_to']) ? array_map('intval', (array)$row['assigned_to']) : [];

                if ( ! in_array($assigned_to, $assignees)) {
                    unset($tasks[$rowIndex]);
                }
            }
        }

        // sorting the tasks
        if ( ! empty($_REQUEST['orderby'])) {
            if ( ! empty($_REQUEST['order']) && $_REQUEST['order'] == 'asc') {
                $tmp = [];
                foreach ($tasks as &$ma) {
                    $tmp[] = &$ma[esc_html($_REQUEST['orderby'])];
                }
                array_multisort($tmp, SORT_ASC, $tasks);
            }
            if ( ! empty($_REQUEST['order']) && $_REQUEST['order'] == 'desc') {
                $tmp = [];
                foreach ($tasks as &$ma) {
                    $tmp[] = &$ma[esc_html($_REQUEST['orderby'])];
                }
                array_multisort($tmp, SORT_DESC, $tasks);
            }
        }

        return $tasks;
    }

    protected function get_table_classes()
    {
        return ['widefat', 'striped', $this->_args['plural']];
    }
}


class Upstream_Admin_Tasks_Page
{

    // class instance
    public static $instance;

    // customer WP_List_Table object
    public $tasks_obj;

    // class constructor
    public function __construct()
    {
        add_filter('set-screen-option', [$this, 'set_screen'], 10, 3);
        add_action('admin_menu', [$this, 'plugin_menu']);
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if ( ! isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Screen options
     */
    public function screen_option()
    {
        $option = 'per_page';
        $args   = [
            'label'   => upstream_task_label_plural(),
            'default' => 10,
            'option'  => 'tasks_per_page',
        ];

        add_screen_option($option, $args);

        $screen = get_current_screen();
        if ($screen->id == 'project_page_tasks') {
            $this->tasks_obj = new Upstream_Task_List();
        }
    }


    public function plugin_menu()
    {
        if (!function_exists('upstream_count_assigned_to_open')) {
            require_once UPSTREAM_PLUGIN_DIR . '/includes/up-project-functions.php';
        }

        $count = (int)upstream_count_assigned_to_open('tasks');
        if ( ! isUserEitherManagerOrAdmin()  /* RSD: removed for new perms && $count <= 0 */) {
            return;
        }

        $hook = add_submenu_page(
            'edit.php?post_type=project',
            upstream_task_label_plural(),
            upstream_task_label_plural(),
            'edit_projects',
            'tasks',
            [$this, 'plugin_settings_page']
        );

        add_action("load-$hook", [$this, 'screen_option']);

        global $submenu;

        $proj = isset($submenu['edit.php?post_type=project']) ? $submenu['edit.php?post_type=project'] : '';
        if ($proj) {
            foreach ($proj as $key => $value) {
                if (in_array('tasks', $value)) {
                    $i                                            = (int)$key;
                    $submenu['edit.php?post_type=project'][$i][0] .= $count ? " <span class='update-plugins count-1'><span class='update-count'>" . esc_html($count) . "</span></span>" : '';
                }
            }
        }
    }


    /**
     * Plugin settings page
     */
    public function plugin_settings_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(upstream_task_label_plural()); ?></h1>

            <div id="post-body-content">

                <div class="meta-box-sortables ui-sortable">
                    <?php $this->tasks_obj->views(); ?>
                    <?php //$this->tasks_obj->display_tablenav( 'top' );
                    ?>
                    <?php //$this->tasks_obj->search_box('search', 'search_id');
                    ?>
                    <form method="post">
                        <?php
                        $this->tasks_obj->prepare_items();
                        $this->tasks_obj->display(); ?>
                    </form>
                </div>
            </div>

            <br class="clear">
        </div>
        <?php
    }
}

add_action('plugins_loaded', function () {
    if (upstream_disable_tasks()) {
        return;
    }

    Upstream_Admin_Tasks_Page::get_instance();
});
