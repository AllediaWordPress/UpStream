<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


if ( ! class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}


class Upstream_Bug_List extends WP_List_Table
{
    private static $bugsStatuses;
    private static $bugsSeverities;
    public $bug_label = '';
    public $bug_label_plural = '';
    private $columns = [];

    /*
     * Displays the filtering links above the table
     */

    /** Class constructor */
    public function __construct()
    {
        $this->bug_label        = upstream_bug_label();
        $this->bug_label_plural = upstream_bug_label_plural();

        parent::__construct([
            'singular' => $this->bug_label,
            'plural'   => $this->bug_label_plural,
            'ajax'     => false //does this table support ajax?
        ]);
    }

    public function get_columns()
    {
        return $columns = apply_filters('upstream_admin_bug_page_columns', [
            'title'       => $this->bug_label,
            'project'     => upstream_project_label(),
            'id'          => __('ID', 'upstream'),
            'assigned_to' => __('Assigned To', 'upstream'),
            'due_date'    => __('Due Date', 'upstream'),
            'status'      => __('Status', 'upstream'),
            'severity'    => __('Severity', 'upstream'),
        ]);
    }

    public function get_views()
    {
        $views = [];

        if ( ! empty($_REQUEST['status'])) {
            $current = sanitize_text_field($_REQUEST['status']);
        } elseif ( ! empty($_REQUEST['view'])) {
            $current = sanitize_text_field($_REQUEST['view']);
        } else {
            $current = 'all';
        }

        //All link
        $all_class    = ($current == 'all' ? ' class="current"' : '');
        $all_url      = remove_query_arg(['status', 'view']);
        $all_count    = upstream_count_total('bugs');
        $views['all'] = "<a href='" . esc_url($all_url) . "' {$all_class} >" . __(
                'All',
                'upstream'
            ) . "</a>({$all_count})";

        //Mine link
        $mine_class    = ($current == 'mine' ? ' class="current"' : '');
        $mine_url      = add_query_arg(['view' => 'mine', 'status' => false]);
        $mine_count    = upstream_count_assigned_to('bugs');
        $views['mine'] = "<a href='" . esc_url($mine_url) . "' {$mine_class} >" . __(
                'Mine',
                'upstream'
            ) . "</a>({$mine_count})";

        // links for other statuses
        $option   = get_option('upstream_bugs');
        $statuses = isset($option['statuses']) ? $option['statuses'] : '';
        $counts   = self::count_statuses();

        if ($statuses) {
            // check if user wants to hide completed bugs
            $hide = get_user_option('upstream_completed_bugs', get_current_user_id());

            foreach ($statuses as $status) {
                if ($hide === 'on' && self::hide_completed($status['name'])) {
                    continue;
                }

                $stati         = strtolower($status['id']);
                $class         = ($current == $stati ? ' class="current"' : '');
                $url           = add_query_arg(['status' => $stati, 'view' => false, 'paged' => false]);
                $count         = isset($counts[$status['name']]) ? $counts[$status['name']] : 0;
                $views[$stati] = "<a href='" . esc_url($url) . "' {$class} >{$status['name']}</a>({$count})";
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
        $statuses = getBugsStatuses();
        $rowset   = self::get_bugs();

        $data = [];

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
     * Retrieve all bugs from all projects.
     *
     * @return array
     */
    public static function get_bugs()
    {
        $args = [
            'post_type'      => 'project',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_upstream_project_bugs',
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

        $bugs = [];
        while ($the_query->have_posts()) : $the_query->the_post();

            $post_id = get_the_ID();

            if (upstream_are_bugs_disabled($post_id)) {
                continue;
            }

            $meta  = get_post_meta($post_id, '_upstream_project_bugs', true);
            $owner = get_post_meta($post_id, '_upstream_project_owner', true);

            if ($meta) :
                foreach ($meta as $meta_val => $bug) {
                    // set up the data for each column
                    $bug['title']       = isset($bug['title']) ? $bug['title'] : __('(no title)', 'upstream');
                    $bug['project']     = get_the_title($post_id);
                    $bug['owner']       = $owner;
                    $bug['assigned_to'] = isset($bug['assigned_to']) ? $bug['assigned_to'] : 0;
                    $bug['due_date']    = isset($bug['due_date']) ? $bug['due_date'] : '';
                    $bug['status']      = isset($bug['status']) ? $bug['status'] : '';
                    $bug['severity']    = isset($bug['severity']) ? $bug['severity'] : '';
                    $bug['description'] = isset($bug['description']) ? $bug['description'] : '';
                    $bug['project_id']  = $post_id; // add the post id to each bug

                    // check if we can add the bug to the list
                    $user_id = get_current_user_id();
                    // $option     = get_option( 'upstream_bugs' );
                    // $hide       = $option['hide_closed'];

                    // // check if user wants to hide completed bugs
                    // if ( $hide == 'on' && self::hide_completed( $bug['status'] ) )
                    //     continue;

                    $bugs[] = $bug;
                }

            endif;

        endwhile;

        return $bugs;
    }

    /**
     *
     *
     * @return null|int
     */
    public static function hide_completed($status)
    {
        $option   = get_option('upstream_bugs');
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
                $projects = (array)$this->get_projects_unique();
                if ( ! empty($projects)) {
                    ?>

                    <select name='project' id='project' class='postform'>
                        <option value=''><?php printf(__('Show all %s', 'upstream'), 'projects') ?></option>
                        <?php foreach ($projects as $project_id => $title) {
                            ?>
                            <option
                                    value="<?php echo $project_id ?>" <?php isset($_GET['project']) ? selected(
                                sanitize_text_field($_GET['project']),
                                $project_id
                            ) : ''; ?>><?php echo esc_html($title) ?></option>
                            <?php
                        } ?>
                    </select>

                    <?php
                }

                $users = (array)$this->get_assigned_to_unique();
                if (count($users) > 0) {
                    $assigned_to = isset($_REQUEST['assigned_to']) ? (int)$_REQUEST['assigned_to'] : 0; ?>
                    <select id="assigned_to" name="assigned_to" class="postform">
                        <option value=""><?php printf(__('Show all %s', 'upstream'), 'users'); ?></option>
                        <?php foreach ($users as $userId => $userName): ?>
                            <option
                                    value="<?php echo esc_attr($userId); ?>" <?php echo $assigned_to === $userId ? 'selected' : ''; ?>><?php echo esc_html($userName) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                }

                $status = self::getBugsStatuses();
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
                                    value="<?php echo $statusId; ?>" <?php isset($_GET['status']) ? selected(
                                sanitize_text_field($_GET['status']),
                                $statusTitle
                            ) : ''; ?>><?php echo esc_html($statusTitle) ?></option>
                            <?php
                        } ?>
                    </select>

                    <?php
                }

                $severities = self::getBugsSeverities();
                if ( ! empty($severities)) {
                    ?>

                    <select name='severity' id='severity' class='postform'>
                        <option value=''><?php printf(__('Show all %s', 'upstream'), 'severities') ?></option>
                        <?php foreach ($severities as $severity) {
                            if (is_array($severity)) {
                                $severityTitle = $severity['name'];
                                $severityId    = $severity['id'];
                            } else {
                                $severityTitle = $severity;
                                $severityId    = $severity;
                            } ?>
                            <option
                                    value="<?php echo $severityId; ?>" <?php isset($_GET['severity']) ? selected(
                                sanitize_text_field($_GET['severity']),
                                $severityTitle
                            ) : ''; ?>><?php echo esc_html($severityTitle) ?></option>
                            <?php
                        } ?>
                    </select>

                    <?php
                }

                submit_button(__('Filter', 'upstream'), 'button', 'filter', false);
            } ?>
        </div>
        <?php
    }

    private function get_projects_unique()
    {
        $bugs = self::get_bugs();
        if (empty($bugs)) {
            return;
        }

        $items = wp_list_pluck($bugs, 'project', 'project_id');
        $items = array_unique($items);
        $items = array_filter($items);

        return $items;
    }

    private function get_assigned_to_unique()
    {
        $bugs = (array)self::get_bugs();
        if (count($bugs) === 0) {
            return;
        }

        $rowset = wp_list_pluck($bugs, 'assigned_to');

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

    private static function getBugsStatuses()
    {
        if (empty(self::$bugsStatuses)) {
            $rowset = self::get_bugs();
            if (empty($rowset) || count($rowset) === 0) {
                return;
            }

            $statuses = getBugsStatuses();

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

            self::$bugsStatuses = $data;
        } else {
            $data = self::$bugsStatuses;
        }

        return $data;
    }

    private static function getBugsSeverities()
    {
        if (empty(self::$bugsSeverities)) {
            $rowset = self::get_bugs();
            if (empty($rowset) || count($rowset) === 0) {
                return;
            }

            $statuses = getBugsSeverities();

            $data = [];

            foreach ($rowset as $row) {
                if ( ! empty($row['severity'])
                     && isset($row['severity'])
                ) {
                    $data[$row['severity']] = isset($statuses[$row['severity']])
                        ? $statuses[$row['severity']]
                        : $row['severity'];
                }
            }

            self::$bugsSeverities = $data;
        } else {
            $data = self::$bugsSeverities;
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
        switch ($column_name) {

            case 'title':

                $output = '<a class="row-title" href="' . get_edit_post_link($item['project_id']) . '">' . $item['title'] . '</a>';

                return $output;

            case 'id':
                return $item['id'];

            case 'project':

                $owner = upstream_project_owner_name($item['project_id']) ? '(' . upstream_project_owner_name($item['project_id']) . ')' : '';

                $output = '<a href="' . get_edit_post_link($item['project_id']) . '">' . $item['project'] . '</a>';
                $output .= '<br>' . $owner;

                return $output;

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
            case 'due_date':
                if (isset($item['due_date']) && (int)$item['due_date'] > 0) {
                    return '<span class="end-date">' . upstream_format_date($item['due_date']) . '</span>';
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

                $status = self::$bugsStatuses[$item['status']];

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

            case 'severity':
                if ( ! isset($item['severity'])
                     || empty($item['severity'])
                ) {
                    return '<span><i style="color: #CCC;">' . __('none', 'upstream') . '</i></span>';
                }

                $severity = self::$bugsSeverities[$item['severity']];

                if (is_array($severity)) {
                    $severityTitle = $severity['name'];
                    $severityColor = $severity['color'];
                } else {
                    $severityTitle = $severity;
                    $severityColor = '#aaaaaa';
                }

                $output = sprintf(
                    '<span class="status %s" style="border-color: %s">
                        <span class="count" style="background-color: %2$s">&nbsp;</span> %3$s
                    </span>',
                    esc_attr(strtolower($severityTitle)),
                    esc_attr($severityColor),
                    esc_html($severityTitle)
                );

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
            'due_date'    => ['due_date', false],
            'status'      => ['status', false],
            'severity'    => ['severity', false],
        ];

        return $sortable_columns;
    }

    /** Text displayed when no customer data is available */
    public function no_items()
    {
        printf(__('No %s avaliable.', 'upstream'), strtolower($this->bug_label_plural));
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {
        $this->_column_headers = $this->get_column_info();

        $per_page     = $this->get_items_per_page('bugs_per_page', 10);
        $current_page = $this->get_pagenum();

        $unpaginated_items = self::get_bugs();
        $unpaginated_items = self::sort_filter($unpaginated_items);

        $total_items = count($unpaginated_items);

        $this->set_pagination_args([
            'total_items' => $total_items, //We have to calculate the total number of items
            'per_page'    => $per_page //We have to determine how many items to show on a page
        ]);

        $this->items = self::output_bugs($per_page, $current_page);
    }

    /**
     * Output bugs
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function output_bugs($per_page = 10, $page_number = 1)
    {

        // get the bugs
        $bugs = self::get_bugs();
        // sort & filter the bugs
        $bugs = self::sort_filter($bugs);
        // does the paging
        if ( ! $bugs) {
            $output = 0;
        } else {
            $output = array_slice($bugs, ($page_number - 1) * $per_page, $per_page);
        }

        return $output;
    }

    public static function sort_filter($bugs = [])
    {
        if ( ! is_array($bugs) || count($bugs) === 0) {
            return [];
        }

        // filtering
        $the_bugs = $bugs; // store the bugs array

        // NOTE: this is being checked against the list below
        $status = isset($_REQUEST['status']) && ! empty($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : 'all';
        if ( ! empty($status) && $status !== 'all') {
            $bugs = array_filter($the_bugs, function ($row) use ($status) {
                return isset($row['status']) && $row['status'] === $status;
            });
        }

        // NOTE: this is being checked against the list below
        $severity = isset($_REQUEST['severity']) && ! empty($_REQUEST['severity']) ? sanitize_text_field($_REQUEST['severity']) : 'all';
        if ( ! empty($severity) && $severity !== 'all') {
            $bugs = array_filter($bugs, function ($row) use ($severity) {
                return isset($row['severity']) && $row['severity'] === $severity;
            });
        }

        // NOTE: this is checking against a known string
        if (isset($_REQUEST['view']) && sanitize_text_field($_REQUEST['view'])  === 'mine') {
            $currentUserId = (int)get_current_user_id();

            $bugs = array_filter($bugs, function ($row) use ($currentUserId) {
                if (isset($row['assigned_to'])) {
                    if ((is_array($row['assigned_to']) && in_array($currentUserId, $row['assigned_to']))
                        || (int)$row['assigned_to'] === $currentUserId
                    ) {
                        return true;
                    }
                }

                return false;
            });
        } else {
            // the cast will set  any non-ints to 0
            $assigned_to = isset($_REQUEST['assigned_to']) ? (int)$_REQUEST['assigned_to'] : 0;
            if ($assigned_to > 0) {
                $bugs = array_filter($bugs, function ($row) use ($assigned_to) {
                    return isset($row['assigned_to']) && $row['assigned_to'] === $assigned_to;
                });
            }
        }

        $project_id = isset($_REQUEST['project']) && ! empty($_REQUEST['project']) ? (int)$_REQUEST['project'] : 0;
        if ($project_id > 0) {
            $bugs = array_filter($bugs, function ($row) use ($project_id) {
                return isset($row['project_id']) && $row['project_id'] === $project_id;
            });
        }

        // sorting the bugs
        if ( ! empty($_REQUEST['orderby'])) {
            if ( ! empty($_REQUEST['order']) && sanitize_text_field($_REQUEST['order']) == 'asc') {
                $tmp = [];
                foreach ($bugs as &$ma) {
                    $tmp[] = &$ma[esc_html(sanitize_text_field($_REQUEST['orderby']))];
                }
                array_multisort($tmp, SORT_ASC, $bugs);
            }
            if ( ! empty($_REQUEST['order']) && sanitize_text_field($_REQUEST['order']) == 'desc') {
                $tmp = [];
                foreach ($bugs as &$ma) {
                    $tmp[] = &$ma[esc_html(sanitize_text_field($_REQUEST['orderby']))];
                }
                array_multisort($tmp, SORT_DESC, $bugs);
            }
        }

        $rowset = [];
        foreach ($bugs as $bug) {
            if ( ! isset($rowset[$bug['id']])) {
                $rowset[$bug['id']] = $bug;
            }
        }

        return array_values($rowset);
    }

    protected function get_table_classes()
    {
        return ['widefat', 'striped', $this->_args['plural']];
    }

    private function get_status_unique()
    {
        $bugs = self::get_bugs();
        if (empty($bugs)) {
            return;
        }

        $items = wp_list_pluck($bugs, 'status');
        $items = array_unique($items);
        $items = array_filter($items);

        return $items;
    }

    private function get_severity_unique()
    {
        $bugs = self::get_bugs();
        if (empty($bugs)) {
            return;
        }

        $items = wp_list_pluck($bugs, 'severity');
        $items = array_unique($items);
        $items = array_filter($items);

        return $items;
    }
}


class Upstream_Admin_Bugs_Page
{

    // class instance
    public static $instance;

    // customer WP_List_Table object
    public $bugs_obj;

    // class constructor`
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

    public function set_screen($status, $option, $value)
    {
        if ('upstream_completed_bugs' == $option) {
            $value = sanitize_text_field($_POST['upstream_hide_completed']);
        }

        return $value;
    }


    /**
     * Screen options
     */
    public function screen_option()
    {
        $option = 'per_page';
        $args   = [
            'label'   => upstream_bug_label_plural(),
            'default' => 10,
            'option'  => 'bugs_per_page',
        ];

        add_screen_option($option, $args);

        $screen = get_current_screen();
        if ($screen->id == 'project_page_bugs') {
            $this->bugs_obj = new Upstream_Bug_List();
        }
    }


    public function plugin_menu()
    {
        $count = (int)upstream_count_assigned_to_open('bugs');
        if ( ! isUserEitherManagerOrAdmin() /* RSD: removed for new perms && $count <= 0 */) {
            return;
        }

        $hook = add_submenu_page(
            'edit.php?post_type=project',
            upstream_bug_label_plural(),
            upstream_bug_label_plural(),
            'edit_projects',
            'bugs',
            [$this, 'plugin_settings_page']
        );

        add_action("load-$hook", [$this, 'screen_option']);

        global $submenu;

        $proj = isset($submenu['edit.php?post_type=project']) ? $submenu['edit.php?post_type=project'] : '';
        if ($proj) {
            foreach ($proj as $key => $value) {
                if (in_array('bugs', $value)) {
                    $i                                            = (int)$key;
                    $submenu['edit.php?post_type=project'][$i][0] .= $count ? " <span class='update-plugins count-1'><span class='update-count'>$count</span></span>" : '';
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
            <h1><?php echo upstream_bug_label_plural(); ?></h1>

            <div id="post-body-content">

                <div class="meta-box-sortables ui-sortable">
                    <?php $this->bugs_obj->views(); ?>
                    <?php //$this->bugs_obj->display_tablenav( 'top' );
                    ?>
                    <?php //$this->bugs_obj->search_box('search', 'search_id');
                    ?>
                    <form method="post">
                        <?php
                        $this->bugs_obj->prepare_items();
                        $this->bugs_obj->display(); ?>
                    </form>
                </div>
            </div>

            <br class="clear">
        </div>
        <?php
    }
}

add_action('plugins_loaded', function () {
    if (upstream_disable_bugs()) {
        return;
    }
    Upstream_Admin_Bugs_Page::get_instance();
});
