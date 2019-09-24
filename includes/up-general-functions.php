<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Get a post id,
 * no matter where we are or what we are doing.
 */
function upstream_post_id()
{
    $post_id = 0;
    if ( ! $post_id) {
        $post_id = get_the_ID();
    }
    if ( ! $post_id) {
        $post_id = isset($_GET['post']) ? (int)$_GET['post'] : 0;
    }
    if ( ! $post_id) {
        $post_id = isset($_POST['post']) ? (int)$_POST['post'] : 0;
    }
    if ( ! $post_id) {
        $post_id = isset($_POST['post_ID']) ? (int)$_POST['post_ID'] : 0;
    }
    if ( ! $post_id) {
        $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    }
    if ( ! $post_id) {
        $post_id = isset($_POST['post']) ? (int)$_POST['post'] : 0;
    }
    if ( ! $post_id) {
        global $wp_query;
        $post_id = $wp_query->get_queried_object_id();
    }
    if ( ! $post_id) {
        if (isset($_POST['formdata'])) {
            parse_str($_POST['formdata'], $posted);
            $post_id = $posted['post_id'];
        }
    }

    return $post_id;
}


// Url for logging out, depending on client or WP user
function upstream_logout_url()
{
    if (
        ( ! empty($_SESSION) && isset($_SESSION['upstream']) && isset($_SESSION['upstream']['user_id'])) ||
        ( ! is_user_logged_in())
    ) {
        return '?action=logout';
    } else {
        return wp_logout_url(get_post_type_archive_link('project'));
    }
}


/**
 * Disable the bugs option
 *
 */
function upstream_disable_bugs()
{
    $options      = get_option('upstream_general');
    $disable_bugs = isset($options['disable_bugs']) ? $options['disable_bugs'] : ['no'];

    return $disable_bugs[0] == 'yes';
}


/**
 * set a unique id.
 *
 */
function upstream_admin_set_unique_id()
{
    return uniqid(get_current_user_id());
}

/**
 * Is a user logged in.
 *
 * @since   1.0.0
 */
function upstream_is_user_logged_in()
{
    // Checks if the user is logged in through WordPress.
    if (is_user_logged_in()) {
        return true;
    }

    return UpStream_Login::userIsLoggedIn();
}

/**
 * Checks if current user is a wordpress user or client.
 *
 * @since   1.0.0
 */
function upstream_current_user_id()
{
    if (is_user_logged_in()) {
        return get_current_user_id();
    } else {
        return isset($_SESSION['upstream']) && isset($_SESSION['upstream']['user_id']) ? $_SESSION['upstream']['user_id'] : 0;
    }
}

// checks if current user is a wordpress user or client
function upstream_user_type()
{
    if (is_user_logged_in()) {
        return 'wp';
    } else {
        return 'client';
    }
}


// gets the client id that a user belongs to
function upstream_get_users_client_id($user_id)
{
    $args = [
        'post_type'      => 'client',
        'post_status'    => 'publish',
        //'order'            => $order, TODO
        'fields'         => 'ids',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'     => '_upstream_client_users',
                'value'   => $user_id,
                'compare' => 'REGEXP',
            ],
        ],
    ];

    $the_query = new WP_Query($args);
    if ($the_query->posts) {
        return $the_query->posts[0];
    }
}

// get some data for current user
// returns a single item
// basically a wrapper for upstream_user_data()
function upstream_current_user($item = null)
{
    if ( ! $item) {
        return;
    }
    $user_data = upstream_user_data(upstream_current_user_id());
    $return    = isset($user_data[$item]) ? $user_data[$item] : '';

    return $return;
}

// get some data for a user with ID passed
// returns a single item
// basically a wrapper for upstream_user_data()
function upstream_user_item($id = 0, $item = null)
{
    if ( ! $item || ! $id) {
        return;
    }
    $user_data = upstream_user_data($id);
    $return    = isset($user_data[$item]) ? $user_data[$item] : '';

    return $return;
}

// get the user avatar with full name in tooltips
function upstream_user_avatar($user_id, $displayTooltip = true)
{
    if ( ! $user_id) {
        return;
    }

    // get user data & ignore current user.
    // if we want current user, pass the ID
    $user_data       = upstream_user_data($user_id, true);
    $userDisplayName = $user_data['display_name'];

    //Display the name only
    if ( ! upstream_show_users_name()) {
        $url     = isset($user_data['avatar']) ? $user_data['avatar'] : '';
        $tooltip = (bool)$displayTooltip ?
            sprintf(
                'title="%s" data-toggle="tooltip" data-placement="top" data-original-title="%1$s"',
                $userDisplayName
            ) : '';
        $return  = sprintf(
            '<img class="avatar" src="%s" %s />',
            esc_attr($url),
            $tooltip
        );
    } else {
        $return = '<span class="avatar_custom_text">' . $userDisplayName . '</span>';
    }

    return apply_filters('upstream_user_avatar', $return);
}

// get data for any user including current
// can send id
function upstream_user_data($data = 0, $ignore_current = false)
{

    // if no data sent, find current user email
    if ( ! $data && ! $ignore_current) {
        $data = upstream_get_email_address();
    }

    $user_data = null;
    $type      = is_email($data) ? 'email' : 'id';
    $wp_user   = get_user_by($type, $data);

    if (empty($wp_user)) {
        $wp_user = wp_get_current_user();
    }

    if ( ! function_exists('is_plugin_active')) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $isBuddyPressRunning = is_plugin_active('buddypress/bp-loader.php') && class_exists('BuddyPress') && function_exists('bp_core_fetch_avatar');

    if ($wp_user && is_object($wp_user)) {
        $role = '';

        if (isset($wp_user->roles)
            && is_array($wp_user->roles)
            && count($wp_user->roles) > 0
        ) {
            $role = ucwords(array_values($wp_user->roles)[0]);
        }

        if (in_array('upstream_user', $wp_user->roles)) {
            $role = sprintf(__('%s User', 'upstream'), upstream_project_label());
        }
        if (in_array('upstream_manager', $wp_user->roles)) {
            $role = sprintf(__('%s Manager', 'upstream'), upstream_project_label());
        }
        if (in_array('upstream_client_user', $wp_user->roles)) {
            $role = sprintf(__('%s Client User', 'upstream'), upstream_project_label());
        }

        $user_data = [
            'id'           => $wp_user->ID,
            'fname'        => $wp_user->first_name,
            'lname'        => $wp_user->last_name,
            'full_name'    => $wp_user->first_name . ' ' . $wp_user->last_name,
            'email'        => $wp_user->user_email,
            'display_name' => $wp_user->display_name,
            'phone'        => '',
            'projects'     => upstream_get_users_projects($wp_user->ID),
            'role'         => $role,
            'avatar'       => "",
        ];

        if ($isBuddyPressRunning) {
            $user_data['avatar'] = bp_core_fetch_avatar([
                'item_id' => $wp_user->ID,
                'type'    => 'thumb',
                'html'    => false,
            ]);
        } else {
            if (is_plugin_active('wp-user-avatar/wp-user-avatar.php') && function_exists('wpua_functions_init')) {
                global $wp_query;

                // Make sure WP_Query is loaded.
                if ( ! ($wp_query instanceof \WP_Query)) {
                    $wp_query = new WP_Query();
                }

                try {
                    // Make sure WP User Avatar dependencies are loaded.
                    require_once ABSPATH . 'wp-settings.php';
                    require_once ABSPATH . 'wp-includes/pluggable.php';
                    require_once ABSPATH . 'wp-includes/query.php';
                    require_once WP_PLUGIN_DIR . '/wp-user-avatar/wp-user-avatar.php';

                    // Load WP User Avatar plugin and its dependencies.
                    wpua_functions_init();

                    // Retrieve current user id.
                    $user_id = upstream_current_user_id();

                    // Retrieve the current user avatar URL.
                    $user_data['avatar'] = get_wp_user_avatar_src($wp_user->ID);
                } catch (Exception $e) {
                    // Do nothing.
                }
            } elseif (is_plugin_active('custom-user-profile-photo/3five_cupp.php') && function_exists('get_cupp_meta')) {
                $user_data['avatar'] = get_cupp_meta($wp_user->ID);
            }

            if (empty($user_data['avatar'])) {
                if ( ! function_exists('get_avatar_url')) {
                    require_once ABSPATH . 'wp-includes/link-template.php';
                }

                $user_data['avatar'] = get_avatar_url(
                    $wp_user->user_email,
                    96,
                    get_option('avatar_default', 'mystery')
                );
            }
        }
    } else {
        global $wpdb;
        $users = $wpdb->get_results(
            "SELECT * FROM `" . $wpdb->postmeta .
            "` WHERE `meta_key` = '_upstream_client_users' AND
            `meta_value` REGEXP '.*\"" . $type . "\";s:[0-9]+:\"" . $data . "\".*'"
        );

        if ( ! $users) {
            return;
        }

        $metavalue = unserialize($users[0]->meta_value);

        foreach ($metavalue as $key => $user) {

            // get the matching user
            if (in_array($data, [$user['id'], $user['email']])) {
                $fname     = isset($user['fname']) ? trim($user['fname']) : '';
                $lname     = isset($user['lname']) ? trim($user['lname']) : '';
                $user_data = [
                    'id'        => $user['id'],
                    'fname'     => $fname,
                    'lname'     => $lname,
                    'full_name' => trim($fname . ' ' . $lname),
                    'email'     => isset($user['email']) ? $user['email'] : '',
                    'phone'     => isset($user['phone']) ? $user['phone'] : '',
                    'projects'  => upstream_get_users_projects($user['id']),
                    'role'      => __('Client User', 'upstream'),
                ];

                $displayName               = ! empty($user_data['full_name']) ? $user_data['full_name'] : $user_data['email'];
                $user_data['display_name'] = $displayName;

                if ($isBuddyPressRunning) {
                    $user_data['avatar'] = bp_core_fetch_avatar([
                        'item_id' => $user['id'],
                        'type'    => 'thumb',
                        'html'    => false,
                    ]);
                } else {
                    if ( ! function_exists('get_avatar_url')) {
                        require_once ABSPATH . 'wp-includes/link-template.php';
                    }

                    $user_data['avatar'] = get_avatar_url(
                        $user['email'],
                        96,
                        get_option('avatar_default', 'mystery')
                    );
                }
            }
        }
    }

    return $user_data;
}


// get a users email address from anything
// normalizes things as we can pass either nothing, or an id or an email.
function upstream_get_email_address($user = 0)
{

    // if $user is already an email, simply return it
    if (is_email($user)) {
        return $user;
    }

    $email = null;

    // this assumes that $user is a wordpress user id
    if ($user != 0 && is_numeric($user)) {
        $wp_user = get_user_by('id', $user);
        $email   = $wp_user->user_email;
    }

    // this assumes that $user is a client user id
    if ($user != 0 && ! is_numeric($user)) {
        $client_id = upstream_get_users_client_id($user);
        $users     = get_post_meta($client_id, '_upstream_client_users', true);
        if (is_array($users) && count($users) > 0) :
            foreach ($users as $key => $user) {
                if ($user['id'] == $user) {
                    $email = $user['email'];
                }
            }
        endif;
    }

    // this assumes we are a logged in wordpress user looking for our own info
    if ( ! $user && upstream_user_type() == 'wp') {
        $wp_user = get_user_by('id', get_current_user_id());
        $email   = $wp_user->user_email;
    }

    // this assumes we are a logged in client user looking for our own info
    if ( ! $user && upstream_user_type() == 'client') {
        if ( ! isset($_SESSION['upstream'])) {
            return null;
        }

        $client_id = $_SESSION['upstream']['client_id'];
        $user_id   = $_SESSION['upstream']['user_id'];
        $users     = get_post_meta($client_id, '_upstream_client_users', true);
        if (is_array($users) && count($users) > 0) :
            foreach ($users as $key => $user) {
                if ($user['id'] == $user_id) {
                    $email = isset($user['email']) ? $user['email'] : '';
                }
            }
        endif;
    }

    return $email;
}


// gets a users name
// displays full name or email if no name set
function upstream_users_name($id = 0, $show_email = false)
{
    $user = upstream_user_data($id, true);

    if ( ! $user) {
        return;
    }

    // if first name exists, then show name. Else show email.
    $output = $user['display_name'];

    if ($show_email && ! empty($user['email'])) {
        $output .= " <a target='_blank' href='mailto:" . esc_html($user['email']) . "' title='" . esc_html($user['email']) . "'><span class='dashicons dashicons-email-alt'></span></a>";
    }

    return $output;
}


/**
 * Retrieve all projects where the user has access to.
 *
 * @param numeric/WP_User     $user    The user to be checked.
 *
 * @return  array
 * @since   1.12.2
 *
 */
function upstream_get_users_projects($user)
{
    $user = $user instanceof \WP_User ? $user : new \WP_User($user);
    if ($user->ID === 0) {
        return [];
    }

    $data = [];

    $rowset = (array)get_posts([
        'post_type'      => "project",
        'post_status'    => "publish",
        'posts_per_page' => -1,
    ]);

    if (count($rowset) > 0) {
        foreach ($rowset as $project) {
            if (upstream_user_can_access_project($user, $project->ID)) {
                $data[$project->ID] = $project;
            }
        }
    }

    return $data;
}


/**
 * Returns percentages for use in dropdowns.
 *
 * @return
 */
function upstream_get_percentages_for_dropdown()
{
    $array = [
        ''    => '0%',
        '5'   => '5%',
        '10'  => '10%',
        '15'  => '15%',
        '20'  => '20%',
        '25'  => '25%',
        '30'  => '30%',
        '35'  => '35%',
        '40'  => '40%',
        '45'  => '45%',
        '50'  => '50%',
        '55'  => '55%',
        '60'  => '60%',
        '65'  => '65%',
        '70'  => '70%',
        '75'  => '75%',
        '80'  => '80%',
        '85'  => '85%',
        '90'  => '90%',
        '95'  => '95%',
        '100' => '100%',
    ];

    return apply_filters('upstream_percentages', $array);
}

/*
 * Run date formatting through here
 */
function upstream_format_date($timestamp, $dateFormat = null)
{
    if (empty($dateFormat)) {
        $dateFormat = get_option('date_format', 'Y-m-d');
    }

    if ( ! $timestamp) {
        $date = null;
    } else {
        $date = date_i18n($dateFormat, $timestamp);
    }

    return apply_filters('upstream_format_date', $date, $timestamp);
}

/*
 * Convert date to unixtime format
 *
 * @return mixed
 */
function upstream_date_unixtime($timestamp, $dateFormat = null)
{
    // Return empty string if timestamp is empty.
    if (is_string($timestamp)) {
        $timestamp = trim($timestamp);
    }

    if (empty($timestamp)) {
        return '';
    }

    if (is_null($dateFormat)) {
        $dateFormat = get_option('date_format', 'Y-m-d');
    }

    $date = \DateTime::createFromFormat($dateFormat, $timestamp);

    if ($date) {
        $date = $date->format('U');
    }

    return apply_filters('upstream_date_mysql', $date, $timestamp);
}

/*
 * Run time formatting through here
 */
function upstream_format_time($timestamp)
{
    if ( ! $timestamp) {
        $time = null;
    } else {
        $time = date_i18n(get_option('time_format'), $timestamp, false);
    }

    return apply_filters('upstream_format_date', $time, $timestamp);
}

/*
 * Used within class-up-project
 */
function upstream_timestamp_from_date($value)
{

    // if blank, return empty string
    if ( ! $value || empty($value)) {
        return '';
    }

    $timestamp = null;

    // if already a timestamp, return the timestamp
    if (is_numeric($value) && (int)$value == $value) {
        $timestamp = $value;
    }

    if ( ! $timestamp) {
        if (empty($value)) {
            return 0;
        }

        $date_format = get_option('date_format');
        $date        = DateTime::createFromFormat($date_format, trim($value));

        if ($date) {
            $timestamp = $date->getTimestamp();
        } else {
            $date_object = date_create_from_format($date_format, $value);
            $timestamp   = $date_object ? $date_object->setTime(0, 0, 0)->getTimeStamp() : strtotime($value);
        }
    }

    // returns the timestamp and sets it to the start of the day
    return strtotime('today', $timestamp);
}

// function to convert date format
// pinched from CMB2
function upstream_php_to_js_dateformat()
{
    $format            = get_option('date_format');
    $supported_options = [
        'd' => 'dd',  // Day, leading 0
        'j' => 'd',   // Day, no 0
        'z' => 'o',   // Day of the year, no leading zeroes,
        // 'D' => 'D',   // Day name short, not sure how it'll work with translations
        // 'l' => 'DD',  // Day name full, idem before
        'm' => 'mm',  // Month of the year, leading 0
        'n' => 'm',   // Month of the year, no leading 0
        // 'M' => 'M',   // Month, Short name
        'F' => 'MM',  // Month, full name,
        'y' => 'y',   // Year, two digit
        'Y' => 'yy',  // Year, full
        'H' => 'HH',  // Hour with leading 0 (24 hour)
        'G' => 'H',   // Hour with no leading 0 (24 hour)
        'h' => 'hh',  // Hour with leading 0 (12 hour)
        'g' => 'h',   // Hour with no leading 0 (12 hour),
        'i' => 'mm',  // Minute with leading 0,
        's' => 'ss',  // Second with leading 0,
        'a' => 'tt',  // am/pm
        'A' => 'TT'   // AM/PM
    ];

    foreach ($supported_options as $php => $js) {
        // replaces every instance of a supported option, but skips escaped characters
        $format = preg_replace("~(?<!\\\\)$php~", $js, $format);
    }

    $format = preg_replace_callback('~(?:\\\.)+~', 'upstream_wrap_escaped_chars', $format);

    return $format;
}

function upstream_wrap_escaped_chars($value)
{
    return "&#39;" . str_replace('\\', '', $value[0]) . "&#39;";
}

function upstream_filter_closed_items()
{
    $option = get_option('upstream_general');

    return isset($option['filter_closed_items']) ? (bool)$option['filter_closed_items'] : false;
}

function upstream_archive_closed_items()
{
    $option = get_option('upstream_general');

    return isset($option['archive_closed_items']) ? (bool)$option['archive_closed_items'] : true;
}

function upstream_show_users_name()
{
    $option = get_option('upstream_general');

    return isset($option['show_users_name']) ? (bool)$option['show_users_name'] : false;
}

function upstream_logo_url()
{
    $option = get_option('upstream_general');
    $logo   = $option['logo'];

    return apply_filters('upstream_logo', $logo);
}

function upstream_login_heading()
{
    $option = get_option('upstream_general');

    return isset($option['login_heading']) ? $option['login_heading'] : '';
}

function upstream_login_text()
{
    $option = get_option('upstream_general');

    return isset($option['login_text']) ? wp_kses_post(wpautop($option['login_text'])) : '';
}

function upstream_admin_email()
{
    $option = get_option('upstream_general');

    return isset($option['admin_email']) ? $option['admin_email'] : '';
}

/**
 * Retrieve the `admin_support_link` option value.
 *
 * @param array $option Array of options. If provided, there's no need to fetch everything again from DB.
 *
 * @return  string
 * @since   1.12.0
 *
 * @see     https://github.com/upstreamplugin/UpStream/issues/81
 *
 */
function upstream_admin_support($option)
{
    if (empty($option)) {
        $option = get_option('upstream_general');
    }

    if (isset($option['admin_support_link'])) {
        return ! empty($option['admin_support_link']) ? $option['admin_support_link'] : 'mailto:' . $option['admin_email'];
    } else {
        return isset($option['admin_email']) ? $option['admin_email'] : '#';
    }
}

/**
 * Retrieve the `admin_support_link_label` option value.
 *
 * @param array $option Array of options. If provided, there's no need to fetch everything again from DB.
 *
 * @return  string
 * @since   1.12.0
 *
 * @see     https://github.com/upstreamplugin/UpStream/issues/81
 *
 */
function upstream_admin_support_label($option)
{
    if (empty($option)) {
        $option = get_option('upstream_general');
    }

    if (isset($option['admin_support_label'])) {
        return ! empty($option['admin_support_label']) ? $option['admin_support_label'] : '';
    } else {
        return __('Contact Admin', 'upstream');
    }
}

/**
 * Check if Milestones are disabled for the current open project.
 * If no ID is passed, this function tries to guess it by checking $_GET/$_POST vars.
 *
 * @param int $post_id The project ID to be checked
 *
 * @return  bool
 * @since   1.8.0
 *
 */
function upstream_are_milestones_disabled($post_id = 0)
{
    $areMilestonesDisabled = false;
    $post_id               = (int)$post_id;

    if ($post_id <= 0) {
        $post_id = (int)upstream_post_id();
    }

    if ($post_id > 0) {
        $theMeta               = get_post_meta($post_id, '_upstream_project_disable_milestones', false);
        $areMilestonesDisabled = ! empty($theMeta) && $theMeta[0] === 'on';
    }

    return $areMilestonesDisabled;
}

/**
 * Check if Tasks are disabled for the current open project.
 * If no ID is passed, this function tries to guess it by checking $_GET/$_POST vars.
 *
 * @param int $post_id The project ID to be checked
 *
 * @return  bool
 * @since   1.8.0
 *
 */
function upstream_are_tasks_disabled($post_id = 0)
{
    $areTasksDisabled = false;
    $post_id          = (int)$post_id;

    if ($post_id <= 0) {
        $post_id = (int)upstream_post_id();
    }

    if ($post_id > 0) {
        $theMeta          = get_post_meta($post_id, '_upstream_project_disable_tasks', false);
        $areTasksDisabled = ! empty($theMeta) && $theMeta[0] === 'on';
    }

    return $areTasksDisabled;
}

/**
 * Check if Bugs are disabled for the current open project.
 * If no ID is passed, this function tries to guess it by checking $_GET/$_POST vars.
 *
 * @param int $post_id The project ID to be checked
 *
 * @return  bool
 * @since   1.8.0
 *
 */
function upstream_are_bugs_disabled($post_id = 0)
{
    $areBugsDisabled = false;
    $post_id         = (int)$post_id;

    if ($post_id <= 0) {
        $post_id = (int)upstream_post_id();
    }

    if ($post_id > 0) {
        $theMeta         = get_post_meta($post_id, '_upstream_project_disable_bugs', false);
        $areBugsDisabled = ! empty($theMeta) && $theMeta[0] === 'on';
    }

    return $areBugsDisabled;
}

/**
 * Check if Files are disabled for the current open project.
 * If no ID is passed, this function tries to guess it by checking $_GET/$_POST vars.
 *
 * @param int $post_id The project ID to be checked
 *
 * @return  bool
 * @since   1.8.0
 *
 */
function upstream_are_files_disabled($post_id = 0)
{
    $areBugsDisabled = false;
    $post_id         = (int)$post_id;

    if ($post_id <= 0) {
        $post_id = (int)upstream_post_id();
    }

    if ($post_id > 0) {
        $theMeta         = get_post_meta($post_id, '_upstream_project_disable_files', false);
        $areBugsDisabled = ! empty($theMeta) && $theMeta[0] === 'on';
    }

    return $areBugsDisabled;
}

function upstream_tinymce_quicktags_settings($tinyMCE)
{
    if (preg_match('/^(?:_upstream_project_|description|notes|new_message)/i', $tinyMCE['id'])) {
        $buttons = 'strong,em,link,del,ul,ol,li,close';

        /**
         * @param array $buttons
         */
        $buttons = apply_filters('upstream_tinymce_buttons', $buttons);

        $tinyMCE['buttons'] = $buttons;
    }

    return $tinyMCE;
}

function upstream_tinymce_before_init_setup_toolbar($tinyMCE)
{
    if ( ! isset($tinyMCE['selector'])) {
        return $tinyMCE;
    }

    if (preg_match('/_upstream_project_|#description|#notes|#new_message|#upstream/i', $tinyMCE['selector'])) {
        /**
         * @param string $buttons
         * @param string $toolbar
         */
        $tinyMCE['toolbar1'] = apply_filters(
            'upstream_tinymce_toolbar',
            'bold,italic,underline,strikethrough,bullist,numlist,link',
            'toolbar1'
        );

        /**
         * This filter is documented above.
         */
        $tinyMCE['toolbar2'] = apply_filters('upstream_tinymce_toolbar', '', 'toolbar2');
        /**
         * This filter is documented above.
         */
        $tinyMCE['toolbar3'] = apply_filters('upstream_tinymce_toolbar', '', 'toolbar3');
        /**
         * This filter is documented above.
         */
        $tinyMCE['toolbar4'] = apply_filters('upstream_tinymce_toolbar', '', 'toolbar4');
    }

    return $tinyMCE;
}

function upstream_tinymce_before_init($tinyMCE)
{
    if ( ! isset($tinyMCE['selector'])) {
        return $tinyMCE;
    }

    if (preg_match('/_upstream_project_|#description|#notes|#new_message|#upstream/i', $tinyMCE['selector'])) {
        if (isset($tinyMCE['plugins'])) {
            $pluginsToBeAdded = [
                'charmap',
                'hr',
                'media',
                'paste',
                'tabfocus',
                'textcolor',
                'wpautoresize',
                'wpemoji',
                'wpgallery',
                'wpdialogs',
                'wptextpattern',
                'wpview',
            ];

            $pluginsList       = explode(',', $tinyMCE['plugins']);
            $pluginsListUnique = array_unique(array_merge($pluginsList, $pluginsToBeAdded));

            /**
             * @param array $pluginsList
             */
            $pluginsListUnique = apply_filters('upstream_tinymce_plugins', $pluginsListUnique);

            $tinyMCE['plugins'] = implode(',', $pluginsListUnique);
        }

        $externalPlugins             = apply_filters('upstream_tinymce_external_plugins', []);
        $tinyMCE['external_plugins'] = wp_json_encode($externalPlugins);
    }

    return $tinyMCE;
}

function upstream_disable_tasks()
{
    $options = get_option('upstream_general');

    $disable_tasks = isset($options['disable_tasks']) ? (array)$options['disable_tasks'] : ['no'];

    $areTasksDisabled = $disable_tasks[0] === 'yes';

    return $areTasksDisabled;
}

function upstream_disable_milestones()
{
    $options = get_option('upstream_general');

    $disable_milestones = isset($options['disable_milestones']) ? (array)$options['disable_milestones'] : ['no'];

    $areMilestonesDisabled = $disable_milestones[0] === 'yes';

    return $areMilestonesDisabled;
}

function upstream_disable_milestone_categories()
{
    $options = get_option('upstream_general');

    $checked = isset($options['disable_milestone_categories']) ? (array)$options['disable_milestone_categories'] : 0;

    return $checked[0] == 1;
}

function upstream_disable_files()
{
    $options = get_option('upstream_general');

    $disable_files = isset($options['disable_files']) ? (array)$options['disable_files'] : ['no'];

    $areFilesDisabled = $disable_files[0] === 'yes';

    return $areFilesDisabled;
}

/**
 * Apply OEmbed filters to a given string in an attempt to render potential embeddable content.
 * This function is called as a callback from CMB2 field method 'escape_cb'.
 *
 * @param mixed       $content    The unescaped content to be analyzed.
 * @param array       $field_args Array of field arguments.
 * @param \CMB2_Field $field      The field instance.
 *
 * @return  mixed                   Escaped value to be displayed.
 * @see     https://github.com/CMB2/CMB2/wiki/Field-Parameters#escape_cb
 *
 * @uses    $wp_embed
 *
 * @since   1.10.0
 *
 */
function applyOEmbedFiltersToWysiwygEditorContent($content, $field_args, $field)
{
    global $wp_embed;

    $content = (string)$content;

    if (strlen($content) > 0) {
        $content = $wp_embed->autoembed($content);
        $content = $wp_embed->run_shortcode($content);
        $content = wpautop($content);
        $content = do_shortcode($content);
    }

    return $content;
}

/**
 * Check if Comments/Discussion are disabled for the current open project.
 * If no ID is passed, this function tries to guess it by checking $_GET/$_POST vars.
 *
 * @param int $post_id The project ID to be checked
 *
 * @param int $post_id The project ID to be checked
 *
 * @return  bool
 * @since   1.8.0
 *
 */
function upstream_are_comments_disabled($post_id = 0)
{
    // General settings
    $pluginOptions = get_option('upstream_general');
    $disabled      = isset($pluginOptions['disable_project_comments']) && (bool)$pluginOptions['disable_project_comments'] === false;

    if ($disabled) {
        return true;
    }

    // Project's settings
    $areCommentsDisabled = false;
    $post_id             = (int)$post_id;

    if ($post_id <= 0) {
        $post_id = (int)upstream_post_id();
    }

    if ($post_id > 0) {
        $theMeta             = get_post_meta($post_id, '_upstream_project_disable_comments', false);
        $areCommentsDisabled = ! empty($theMeta) && $theMeta[0] === 'on';
    }

    return $areCommentsDisabled;
}

/**
 * Check if Projects Categorization is currently disabled.
 *
 * @return  bool
 * @since   1.12.0
 *
 */
function is_project_categorization_disabled()
{
    $options = get_option('upstream_general');

    $isDisabled = isset($options['disable_categories']) ? (bool)$options['disable_categories'] : false;

    return $isDisabled;
}

/**
 * Check if Clients feature is disabled.
 *
 * @return  bool
 * @since   1.12.0
 *
 */
function is_clients_disabled()
{
    $options = get_option('upstream_general');

    $isDisabled = isset($options['disable_clients']) ? (bool)$options['disable_clients'] : false;

    return $isDisabled;
}

/**
 * Check if should Select Users by Default.
 *
 * @return  bool
 */
function select_users_by_default()
{
    $options = get_option('upstream_general');

    $enabled = isset($options['pre_select_users']) ? (bool)$options['pre_select_users'] : false;

    return $enabled;
}

/**
 * Retrieve the avatar URL from a given user.
 *
 * @param int $user_id The user ID.
 *
 * @return  string|bool
 * @since   1.12.0
 *
 */
function getUserAvatarURL($user_id)
{
    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return false;
    }

    if ( ! function_exists('is_plugin_active')) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $avatarURL = "";

    // Check if BuddyPress is running so we can borrow its functions.
    $isBuddyPressRunning = is_plugin_active('buddypress/bp-loader.php') && class_exists('BuddyPress') && function_exists('bp_core_fetch_avatar');
    if ($isBuddyPressRunning) {
        $avatarURL = (string)bp_core_fetch_avatar([
            'item_id' => $user_id,
            'type'    => "thumb",
            'html'    => false,
        ]);
    }

    // Check if WP-User-Avatar is running so we can borrow its functions.
    if (empty($avatarURL) && is_plugin_active('wp-user-avatar/wp-user-avatar.php') && function_exists('wpua_functions_init')) {
        global $wp_query;

        // Make sure WP_Query is loaded.
        if ( ! ($wp_query instanceof \WP_Query)) {
            $wp_query = new WP_Query();
        }

        try {
            // Make sure WP User Avatar dependencies are loaded.
            require_once ABSPATH . 'wp-settings.php';
            require_once ABSPATH . 'wp-includes/pluggable.php';
            require_once ABSPATH . 'wp-includes/query.php';
            require_once WP_PLUGIN_DIR . '/wp-user-avatar/wp-user-avatar.php';

            // Load WP User Avatar plugin and its dependencies.
            wpua_functions_init();

            // Retrieve the current user avatar URL.
            $avatarURL = (string)get_wp_user_avatar_src($user_id);
        } catch (Exception $e) {
            // Do nothing.
        }
    }

    // Check if Custom User Profile Photo is running so we can borrow its functions.
    if (empty($avatarURL) && is_plugin_active('custom-user-profile-photo/3five_cupp.php') && function_exists('get_cupp_meta')) {
        $avatarURL = (string)get_cupp_meta($user_id);
    }

    if (empty($avatarURL)) {
        if ( ! function_exists('get_avatar_url')) {
            require_once ABSPATH . 'wp-includes/link-template.php';
        }

        $avatarURL = (string)get_avatar_url($user_id, 96, get_option('avatar_default', 'mystery'));
    }

    return $avatarURL;
}

/**
 * Check if the current user is either administrator or UpStream Manager.
 *
 * @return  bool
 * @since   1.12.0
 *
 */
function isUserEitherManagerOrAdmin($user = null)
{
    if (empty($user) || ! ($user instanceof \WP_User)) {
        $user = wp_get_current_user();
    }

    if ($user->ID > 0 && isset($user->roles)) {
        return count(array_intersect((array)$user->roles, ['administrator', 'upstream_manager'])) > 0;
    }

    return false;
}

/**
 * Generates a random string of custom length.
 *
 * @param int    $length    The length of the random string.
 * @param string $charsPool The characters that might compose the string.
 *
 * @return  string
 * @since   1.12.2
 *
 */
function upstreamGenerateRandomString(
    $length,
    $charsPool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
) {
    $randomString       = "";
    $maxCharsPoolLength = mb_strlen($charsPool, '8bit') - 1;

    for ($lengthIndex = 0; $lengthIndex < $length; ++$lengthIndex) {
        $randomString .= $charsPool[random_int(0, $maxCharsPoolLength)];
    }

    return $randomString;
}

/**
 * Check if comments are allowed on projects.
 *
 * @return  bool
 * @since   1.13.0
 *
 */
function upstreamAreProjectCommentsEnabled()
{
    // Retrieve UpStream general options.
    $options    = get_option('upstream_general');
    $optionName = 'disable_project_comments';
    // Check if the option exists.
    if (isset($options[$optionName])) {
        $allow = (bool)$options[$optionName];
    } else {
        $legacyOptionName = 'disable_discussion';
        // Check if user has legacy option set.
        if (isset($options[$legacyOptionName])) {
            if (is_array($options[$legacyOptionName]) || is_object($options[$legacyOptionName])) {
                $options[$legacyOptionName] = json_decode(json_encode($options[$legacyOptionName]), true);
                if ( ! empty($options[$legacyOptionName])) {
                    $options[$legacyOptionName] = array_reverse($options[$legacyOptionName]);
                    $legacyOptionValue          = array_pop($options[$legacyOptionName]);
                } else {
                    $legacyOptionValue = "";
                }
            } else {
                $legacyOptionValue = (string)$options[$legacyOptionName];
            }

            if (is_string($legacyOptionValue)) {
                $allow = strtoupper(trim($legacyOptionValue)) !== 'YES';
            } else {
                $allow = true;
            }

            unset($options[$legacyOptionName]);

            // Migrate existent legacy option.
            $options[$optionName] = (int) ! $allow;

            // Update options.
            update_option('upstream_general', $options);
        } else {
            // Default value.
            $allow = true;
        }
    }

    return $allow;
}

/**
 * Check if comments are allowed on milestones.
 *
 * @return  bool
 * @since   1.13.0
 *
 */
function upstreamAreCommentsEnabledOnMilestones()
{
    $options = get_option('upstream_general');

    $optionName = 'disable_comments_on_milestones';

    $allow = isset($options[$optionName]) ? (bool)$options[$optionName] : true;

    return $allow;
}

/**
 * Check if comments are allowed on tasks.
 *
 * @return  bool
 * @since   1.13.0
 *
 */
function upstreamAreCommentsEnabledOnTasks()
{
    $options = get_option('upstream_general');

    $optionName = 'disable_comments_on_tasks';

    $allow = isset($options[$optionName]) ? (bool)$options[$optionName] : true;

    return $allow;
}

/**
 * Check if comments are allowed on bugs.
 *
 * @return  bool
 * @since   1.13.0
 *
 */
function upstreamAreCommentsEnabledOnBugs()
{
    $options = get_option('upstream_general');

    $optionName = 'disable_comments_on_bugs';

    $allow = isset($options[$optionName]) ? (bool)$options[$optionName] : true;

    return $allow;
}

/**
 * Check if comments are allowed on files.
 *
 * @return  bool
 * @since   1.13.0
 *
 */
function upstreamAreCommentsEnabledOnFiles()
{
    $options = get_option('upstream_general');

    $optionName = 'disable_comments_on_files';

    $allow = isset($options[$optionName]) ? (bool)$options[$optionName] : true;

    return $allow;
}

/**
 * Check if should show all the projects in the sidebar.
 *
 * @return  bool
 * @since   1.13.0
 *
 */
function upstreamShowAllProjectsInSidebar()
{
    $options = get_option('upstream_general');

    $optionName = 'show_all_projects_sidebar';

    $allow = isset($options[$optionName]) ? (bool)$options[$optionName] : false;

    return $allow;
}


/**
 * Check if should send emails on new comment.
 *
 * @return  bool
 * @since   1.13.0
 *
 */
function upstreamSendNotificationsForNewComments()
{
    $options = get_option('upstream_general');

    $optionName = 'send_notifications_for_new_comments';

    $allow = isset($options[$optionName]) ? (bool)$options[$optionName] : true;

    return $allow;
}

/**
 * Slighted modification of PHP's native nl2br function.
 *
 * @param string $subject String to be processed.
 *
 * @return  string
 * @since   1.13.1
 *
 */
function upstream_nl2br($subject)
{
    // Step 1: Add <br /> tags for each line-break.
    $subject = nl2br($subject);

    // Step 2: Remove the actual line-breaks.
    $subject = str_replace("\n", "", $subject);
    $subject = str_replace("\r", "", $subject);

    // Step 3: Restore the line-breaks that are inside <pre></pre> tags.
    if (preg_match_all('/\<pre\>(.*?)\<\/pre\>/', $subject, $match)) {
        foreach ($match as $a) {
            foreach ($a as $b) {
                $subject = str_replace(
                    '<pre>' . $b . '</pre>',
                    "<pre>" . str_replace("<br />", PHP_EOL, $b) . "</pre>",
                    $subject
                );
            }
        }
    }

    // Step 4: Removes extra <br /> tags.

    // Before <pre> tags.
    $subject = str_replace("<br /><br /><br /><pre>", '<br /><br /><pre>', $subject);
    // After </pre> tags.
    $subject = str_replace("</pre><br /><br />", '</pre><br />', $subject);

    // Arround <ul></ul> tags.
    $subject = str_replace("<br /><br /><ul>", '<br /><ul>', $subject);
    $subject = str_replace("</ul><br /><br />", '</ul><br />', $subject);
    // Inside <ul> </ul> tags.
    $subject = str_replace("<ul><br />", '<ul>', $subject);
    $subject = str_replace("<br /></ul>", '</ul>', $subject);

    // Arround <ol></ol> tags.
    $subject = str_replace("<br /><br /><ol>", '<br /><ol>', $subject);
    $subject = str_replace("</ol><br /><br />", '</ol><br />', $subject);
    // Inside <ol> </ol> tags.
    $subject = str_replace("<ol><br />", '<ol>', $subject);
    $subject = str_replace("<br /></ol>", '</ol>', $subject);

    // Arround <li></li> tags.
    $subject = str_replace("<br /><li>", '<li>', $subject);
    $subject = str_replace("</li><br />", '</li>', $subject);

    return $subject;
}

function upstreamShouldRunCmb2()
{
    global $pagenow;

    if ($pagenow === 'post.php'
        || $pagenow === 'post-new.php'
    ) {
        $post_id  = isset($_GET['post']) ? (int)$_GET['post'] : 0;
        $postType = get_post_type($post_id);
        if (empty($postType)) {
            $postType = isset($_GET['post_type']) ? $_GET['post_type'] : '';
            if (empty($postType)
                && isset($_POST['post_type'])
            ) {
                $postType = $_POST['post_type'];
            }
        }

        $postTypesUsingCmb2 = apply_filters('upstream:post_types_using_cmb2', ['project', 'client']);

        if (in_array($postType, $postTypesUsingCmb2)) {
            return true;
        }
    } elseif ($pagenow === 'admin.php'
              && isset($_GET['page'])
              && preg_match('/^upstream_/i', $_GET['page'])
    ) {
        return true;
    }

    return false;
}

function upstreamGetUsersMap()
{
    $map = [];

    $rowset = get_users([
        'fields' => ['ID', 'display_name'],
    ]);

    foreach ($rowset as $user) {
        $map[(int)$user->ID] = $user->display_name;
    }

    return $map;
}

function upstreamGetDateFormatForJsDatepicker()
{
    $format            = get_option('date_format');
    $supported_options = [
        'd' => 'dd',  // Day, leading 0
        'j' => 'd',   // Day, no 0
        'z' => 'o',   // Day of the year, no leading zeroes,
        // 'D' => 'D',   // Day name short, not sure how it'll work with translations
        // 'l' => 'DD',  // Day name full, idem before
        'm' => 'mm',  // Month of the year, leading 0
        'n' => 'm',   // Month of the year, no leading 0
        // 'M' => 'M',   // Month, Short name
        'F' => 'MM',  // Month, full name,
        'y' => 'yy',   // Year, two digit
        'Y' => 'yyyy',  // Year, full
        'H' => 'HH',  // Hour with leading 0 (24 hour)
        'G' => 'H',   // Hour with no leading 0 (24 hour)
        'h' => 'hh',  // Hour with leading 0 (12 hour)
        'g' => 'h',   // Hour with no leading 0 (12 hour),
        'i' => 'mm',  // Minute with leading 0,
        's' => 'ss',  // Second with leading 0,
        'a' => 'tt',  // am/pm
        'A' => 'TT',   // AM/PM
        'S' => '',   // th, rd, st
    ];

    foreach ($supported_options as $php => $js) {
        // replaces every instance of a supported option, but skips escaped characters
        $format = preg_replace("~(?<!\\\\)$php~", $js, $format);
    }

    $format = preg_replace_callback('~(?:\\\.)+~', 'upstream_wrap_escaped_chars', $format);

    return $format;
}

function userCanReceiveCommentRepliesNotification($user_id = 0)
{
    if ( ! is_numeric($user_id)) {
        return false;
    }

    if ((int)$user_id <= 0) {
        $user_id = get_current_user_id();
    }

    $receiveNotifications = get_user_meta($user_id, 'upstream_comment_replies_notification', true) !== 'no';

    return $receiveNotifications;
}

/**
 * Retrieve a list of Milestones available on this instance.
 *
 * @return  array
 * @since   1.17.0
 *
 */
function getMilestones()
{
    $data = [];

    $milestones = (array)get_option('upstream_milestones');
    if (isset($milestones['milestones'])) {
        foreach ($milestones['milestones'] as $index => $milestone) {
            if (isset($milestone['id'])) {
                $milestone['order'] = $index;

                $data[$milestone['id']] = $milestone;
            }
        }
    }

    return $data;
}

/**
 * Retrieve a list of Milestones titles available on this instance.
 *
 * @return  array
 * @since   1.17.0
 *
 */
function getMilestonesTitles()
{
    $data = [];

    $milestones = getMilestones();
    foreach ($milestones as $milestone) {
        if (isset($milestone['id'])) {
            $data[$milestone['id']] = $milestone['title'];
        }
    }

    return $data;
}

/**
 * Retrieve a list of Tasks available on this instance.
 *
 * @return  array
 * @since   1.17.0
 *
 */
function getTasksStatuses()
{
    $data = [];

    $tasks = (array)get_option('upstream_tasks');
    if (isset($tasks['statuses'])) {
        foreach ($tasks['statuses'] as $index => $task) {
            if (isset($task['id'])) {
                $task['order'] = $index;

                $data[$task['id']] = $task;
            }
        }
    }

    return $data;
}

/**
 * Retrieve a list of Task statuses titles available on this instance.
 *
 * @return  array
 * @since   1.17.0
 *
 */
function getTasksStatusesTitles()
{
    $data = [];

    $tasks = getTasksStatuses();
    foreach ($tasks as $task) {
        if (isset($task['id'])) {
            $data[$task['id']] = $task['name'];
        }
    }

    return $data;
}


function getBugsStatuses()
{
    $data = [];

    $bugs = (array)get_option('upstream_bugs');
    if (isset($bugs['statuses'])) {
        foreach ($bugs['statuses'] as $index => $bugStatus) {
            if (isset($bugStatus['id'])) {
                $bugStatus['order'] = $index;

                $data[$bugStatus['id']] = $bugStatus;
            }
        }
    }

    return $data;
}

function getBugsSeverities()
{
    $data = [];

    $bugs = (array)get_option('upstream_bugs');
    if (isset($bugs['severities'])) {
        foreach ($bugs['severities'] as $index => $bugSeverity) {
            if (isset($bugSeverity['id'])) {
                $bugSeverity['order'] = $index;

                $data[$bugSeverity['id']] = $bugSeverity;
            }
        }
    }

    return $data;
}

function upstream_media_unrestricted_roles()
{
    $option = get_option('upstream_general');

    return isset($option['media_unrestricted_roles']) ? $option['media_unrestricted_roles'] : ['administrator'];
}


/**
 * DEPRECATED
 */

/**
 * Retrieve a DateTimeZone object of the current WP's timezone.
 * This function falls back to UTC in case of an invalid/empty timezone option.
 *
 * @return  \DateTimeZone
 * @deprecated
 *
 * @since   1.12.3
 */
function upstreamGetTimeZone()
{
    $tz = (string)get_option('timezone_string');

    try {
        $theTimeZone = new DateTimeZone($tz);
    } catch (Exception $e) {
        $theTimeZone = new DateTimeZone('UTC');
    }

    return $theTimeZone;
}

/**
 * Convert a given date (UTC)/timestamp to the instance's timezone.
 *
 * @param int|string $subject The date to be converted. If int, assume it's a timestamp.
 *
 * @return  string|false                The converted string or false in case of failure.
 * @since   1.11.0
 * @deprecated
 *
 */
function upstream_convert_UTC_date_to_timezone($subject, $includeTime = true)
{
    try {
        $dateFormat = get_option('date_format');

        if ($includeTime === true) {
            $dateFormat .= ' ' . get_option('time_format');
        }

        if (is_numeric($subject)) {
            $theDate = new DateTime();
            $theDate->setTimestamp($subject);
        } else {
            $theDate = new DateTime($subject);
        }

        $instanceTimezone = upstreamGetTimeZone();
        $theDate->setTimeZone($instanceTimezone);

        return $theDate->format($dateFormat);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * @param array $users
 *
 * @return string
 */
function upstream_get_users_display_name($users)
{
    $html = 0;

    $usersIds   = array_filter(array_unique($users));
    $usersCount = count($usersIds);

    if ($usersCount > 0) {
        $users = get_users([
            'include' => $usersIds,
        ]);

        $columnValue = [];
        foreach ($users as $user) {
            $columnValue[] = $user->display_name;
        }
        unset($user, $users);

        $html = implode(',<br>', $columnValue);
    }

    unset($usersCount, $usersIds);

    return $html;
}

/**
 * @return array
 * @deprecated
 */
function upstream_admin_get_options_milestones()
{
    return upstream_project_milestones();
}
