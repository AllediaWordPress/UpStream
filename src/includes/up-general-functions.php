<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get a post id,
 * no matter where we are or what we are doing.
 */
function upstream_post_id() {
    $post_id = 0;
    if( ! $post_id )
        $post_id = get_the_ID();
    if( ! $post_id )
        $post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
    if( ! $post_id )
        $post_id = isset( $_POST['post'] ) ? (int) $_POST['post'] : 0;
    if( ! $post_id )
        $post_id = isset( $_POST['post_ID'] ) ? (int) $_POST['post_ID'] : 0;
    if( ! $post_id )
        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    if( ! $post_id )
        $post_id = isset( $_POST['post'] ) ? (int) $_POST['post'] : 0;
    if( ! $post_id ) {
        global $wp_query;
        $post_id = $wp_query->get_queried_object_id();
    }
    if( ! $post_id ) {
        if( isset( $_POST['formdata'] ) ){
            parse_str( $_POST['formdata'], $posted );
            $post_id = $posted['post_id'];
        }
    }
    return $post_id;
}


// Url for logging out, depending on client or WP user
function upstream_logout_url() {
    if( is_user_logged_in() ) {
        return wp_logout_url();
    } else {
        return '?action=logout';
    }
}


/**
 * Disable the bugs option
 *
 */
function upstream_disable_bugs() {
    $options        = get_option( 'upstream_general' );
    $disable_bugs   = isset( $options['disable_bugs'] ) ? $options['disable_bugs'] : array('no');
    if( $disable_bugs[0] == 'yes' )
        return true;
}



/**
 * set a unique id.
 *
 */
function upstream_admin_set_unique_id() {
    return uniqid( get_current_user_id() );
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
function upstream_user_type() {
    if( is_user_logged_in() ) {
        return 'wp';
    } else {
        return 'client';
    }
}



// gets the client id that a user belongs to
function upstream_get_users_client_id( $user_id ) {

    $args = array(
        'post_type'        => 'client',
        'post_status'      => 'publish',
        //'order'            => $order, TODO
        'fields'           => 'ids',
        'posts_per_page'   => 1,
        'meta_query' => array(
            array(
                'key'     => '_upstream_client_users',
                'value'   => $user_id,
                'compare' => 'REGEXP',
            ),
        ),
    );

    $the_query = new WP_Query( $args );
    if( $the_query->posts ){
        return $the_query->posts[0];
    }

}

// get some data for current user
// returns a single item
// basically a wrapper for upstream_user_data()
function upstream_current_user( $item = null ) {
    if( ! $item )
        return;
    $user_data  = upstream_user_data( upstream_current_user_id() );
    $return     = isset( $user_data[ $item ] ) ? $user_data[ $item ] : '';
    return $return;
}

// get some data for a user with ID passed
// returns a single item
// basically a wrapper for upstream_user_data()
function upstream_user_item( $id = 0, $item = null ) {
    if( ! $item || ! $id )
        return;
    $user_data  = upstream_user_data( $id );
    $return     = isset( $user_data[ $item ] ) ? $user_data[ $item ] : '';
    return $return;
}

// get the user avatar with full name in tooltips
function upstream_user_avatar( $user_id ) {

    if( ! $user_id )
        return;
    // get user data & ignore current user.
    // if we want current user, pass the ID
    $user_data  = upstream_user_data( $user_id, true );
    $url        = isset( $user_data[ 'avatar' ] ) ? $user_data[ 'avatar' ] : '';

    $return = '<img class="avatar" src="' . esc_attr( $url ) . '" data-toggle="tooltip" data-placement="top" data-original-title="' . esc_attr( $user_data[ 'full_name' ] ) . '" />';

    return apply_filters( 'upstream_user_avatar', $return );
}

// get data for any user including current
// can send id
function upstream_user_data( $data = 0, $ignore_current = false ) {

    // if no data sent, find current user email
    if( ! $data && ! $ignore_current )
        $data = upstream_get_email_address();

    $user_data  = null;
    $type       = is_email( $data ) ? 'email' : 'id';
    $wp_user    = get_user_by( $type, $data );

    if (!function_exists('is_plugin_active')) {
        include_once ABSPATH .'wp-admin/includes/plugin.php';
    }

    $isBuddyPressRunning = is_plugin_active('buddypress/bp-loader.php') && class_exists('BuddyPress') && function_exists('bp_core_fetch_avatar');

    if( $wp_user && is_object( $wp_user ) ) {

        // get the role
        $role = ucwords( $wp_user->roles[0] );
        if( in_array( 'upstream_user', $wp_user->roles ) ) {
            $role = sprintf( __( '%s User', 'upstream' ), upstream_project_label() );
        }
        if( in_array( 'upstream_manager', $wp_user->roles ) ) {
            $role = sprintf( __( '%s Manager', 'upstream' ), upstream_project_label() );
        }

        $user_data = array(
            'id'        => $wp_user->ID,
            'fname'     => $wp_user->first_name,
            'lname'     => $wp_user->last_name,
            'full_name' => $wp_user->first_name . ' ' . $wp_user->last_name,
            'email'     => $wp_user->user_email,
            'phone'     => '',
            'projects'  => upstream_get_users_projects( $wp_user->ID ),
            'role'      => $role,
            'avatar'    => ""
        );

        if ($isBuddyPressRunning) {
            $user_data['avatar'] = bp_core_fetch_avatar(array(
                'item_id' => $wp_user->ID,
                'type'    => 'thumb',
                'html'    => false
            ));
        } else {
            if (is_plugin_active('wp-user-avatar/wp-user-avatar.php') && function_exists('wpua_functions_init')) {
                global $wp_query;

                // Make sure WP_Query is loaded.
                if (!($wp_query instanceof \WP_Query)) {
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
            } else if (is_plugin_active('custom-user-profile-photo/3five_cupp.php') && function_exists('get_cupp_meta')) {
                $user_data['avatar'] = get_cupp_meta($wp_user->ID);
            }

            if (empty($user_data['avatar'])) {
                if (!function_exists('get_avatar_url')) {
                    require_once ABSPATH . 'wp-includes/link-template.php';
                }

                $user_data['avatar'] = get_avatar_url($wp_user->user_email, 96, get_option('avatar_default', 'mystery'));
            }
        }

    } else {

        global $wpdb;
        $users = $wpdb->get_results (
            "SELECT * FROM `" . $wpdb->postmeta .
            "` WHERE `meta_key` = '_upstream_client_users' AND
            `meta_value` REGEXP '.*\"". $type ."\";s:[0-9]+:\"". $data ."\".*'"
        );

        if( ! $users )
            return;

        $metavalue = unserialize( $users[0]->meta_value );

        foreach ($metavalue as $key => $user) {

            // get the matching user
            if( in_array( $data, array( $user['id'], $user['email'] ) ) ) {

                $fname = isset( $user['fname'] ) ? $user['fname'] : '';
                $lname = isset( $user['lname'] ) ? $user['lname'] : '';
                $user_data = array(
                    'id'        => $user['id'],
                    'fname'     => $fname,
                    'lname'     => $lname,
                    'full_name' => $fname . ' ' . $lname,
                    'email'     => isset( $user['email'] ) ? $user['email'] : '',
                    'phone'     => isset( $user['phone'] ) ? $user['phone'] : '',
                    'projects'  => upstream_get_users_projects( $user['id'] ),
                    'role'      => __( 'Client User', 'upstream' ),
                );

                if ($isBuddyPressRunning) {
                    $user_data['avatar'] = bp_core_fetch_avatar(array(
                        'item_id' => $user['id'],
                        'type'    => 'thumb',
                        'html'    => false
                    ));
                } else {
                    if (!function_exists('get_avatar_url')) {
                        require_once ABSPATH . 'wp-includes/link-template.php';
                    }

                    $user_data['avatar'] = get_avatar_url($user['email'], 96, get_option('avatar_default', 'mystery'));
                }
            }

        }

    }

    return $user_data;
}



// get a users email address from anything
// normalizes things as we can pass either nothing, or an id or an email.
function upstream_get_email_address( $user = 0 ) {

    // if $user is already an email, simply return it
    if( is_email( $user ) )
        return $user;

    $email = null;

    // this assumes that $user is a wordpress user id
    if( $user != 0 && is_numeric( $user ) ) {
        $wp_user    = get_user_by( 'id', $user );
        $email      = $wp_user->user_email;
    }

    // this assumes that $user is a client user id
    if( $user != 0 && ! is_numeric( $user ) ) {
        $client_id  = upstream_get_users_client_id( $user );
        $users      = get_post_meta( $client_id, '_upstream_client_users', true );
        if ( is_array ($users) && count ($users) > 0 ) :
            foreach ($users as $key => $user) {
                if( $user['id'] == $user ) {
                    $email  = $user['email'];
                }
            }
        endif;
    }

    // this assumes we are a logged in wordpress user looking for our own info
    if( ! $user && upstream_user_type() == 'wp' ) {
        $wp_user    = get_user_by( 'id', get_current_user_id() );
        $email      = $wp_user->user_email;
    }

    // this assumes we are a logged in client user looking for our own info
    if( ! $user && upstream_user_type() == 'client' ) {
        if (!isset($_SESSION['upstream'])) {
            return null;
        }

        $client_id  = $_SESSION['upstream']['client_id'];
        $user_id    = $_SESSION['upstream']['user_id'];
        $users      = get_post_meta( $client_id, '_upstream_client_users', true );
        if ( is_array ($users) && count ($users) > 0 ) :
            foreach ($users as $key => $user) {
                if( $user['id'] == $user_id ) {
                    $email = isset( $user['email'] ) ? $user['email'] : '';
                }
            }
        endif;
    }

    return $email;

}


// gets a users name
// displays full name or email if no name set
function upstream_users_name( $id = 0, $show_email = false ) {

    $user = upstream_user_data( $id, true );

    if( ! $user )
        return;

    // if first name exists, then show name. Else show email.
    $output = $user['fname'] != '' ? $user['fname'] . ' ' . $user['lname'] : $user['email'];

    if( $show_email && ! empty( $user['email'] ) ) {
        $output .= " <a target='_blank' href='mailto:" . esc_html( $user['email'] ) . "' title='" . esc_html( $user['email'] ) . "'><span class='dashicons dashicons-email-alt'></span></a>";
    }

    return $output;
}


// returns the ID's of the projects that a user is regestired to
function upstream_get_users_projects( $user_id ) {

    $args = array(
        'post_type'        => 'project',
        'post_status'      => 'publish',
        'fields'           => 'ids',
        'posts_per_page'   => -1,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key'     => '_upstream_project_client_users',
                'value'   => $user_id,
                'compare' => 'REGEXP',
            ),
            array(
                'key'     => '_upstream_project_members',
                'value'   => $user_id,
                'compare' => 'REGEXP',
            ),
        ),
    );

    $the_query = new WP_Query( $args );
    return $the_query->posts;
}


/**
 * Returns percentages for use in dropdowns.
 *
 * @return
 */
function upstream_get_percentages_for_dropdown() {

    $array = array(
        '' => '0%',
        '5' => '5%',
        '10' => '10%',
        '15' => '15%',
        '20' => '20%',
        '25' => '25%',
        '30' => '30%',
        '35' => '35%',
        '40' => '40%',
        '45' => '45%',
        '50' => '50%',
        '55' => '55%',
        '60' => '60%',
        '65' => '65%',
        '70' => '70%',
        '75' => '75%',
        '80' => '80%',
        '85' => '85%',
        '90' => '90%',
        '95' => '95%',
        '100' => '100%',
    );

    return apply_filters( 'upstream_percentages', $array );

}


/*
 * Run date formatting through here
 */
function upstream_format_date( $timestamp ) {
    if( ! $timestamp ) {
        $date = null;
    } else {
        $date = date_i18n( get_option( 'date_format' ), $timestamp, false );
    }
    return apply_filters( 'upstream_format_date', $date, $timestamp );
}

/*
 * Run time formatting through here
 */
function upstream_format_time( $timestamp ) {
    if( ! $timestamp ) {
        $time = null;
    } else {
        $time = date_i18n( get_option( 'time_format' ), $timestamp, false );
    }
    return apply_filters( 'upstream_format_date', $time, $timestamp );
}

/*
 * Used within class-up-project
 */
function upstream_timestamp_from_date( $value ) {

    // if blank, return empty string
    if( ! $value || empty( $value ) )
        return '';

    $timestamp = null;

    // if already a timestamp, return the timestamp
    if( is_numeric($value) && (int)$value == $value )
        $timestamp = $value;

    if( ! $timestamp ) {
        $date_format    = get_option( 'date_format' );
        $date           = DateTime::createFromFormat( $date_format, trim( $value ) );

        if( $date ) {
            $timestamp = $date->getTimestamp();
        } else {
            $date_object = date_create_from_format( $date_format, $value );
            $timestamp = $date_object ? $date_object->setTime( 0, 0, 0 )->getTimeStamp() : strtotime( $value );
        }
    }

    // returns the timestamp and sets it to the start of the day
    return strtotime( 'today', $timestamp );

}

// function to convert date format
// pinched from CMB2
function upstream_php_to_js_dateformat() {
    $format = get_option( 'date_format' );
    $supported_options = array(
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
        );

        foreach ( $supported_options as $php => $js ) {
            // replaces every instance of a supported option, but skips escaped characters
            $format = preg_replace( "~(?<!\\\\)$php~", $js, $format );
        }

        $format = preg_replace_callback( '~(?:\\\.)+~', 'upstream_wrap_escaped_chars', $format );

        return $format;

}
function upstream_wrap_escaped_chars( $value ) {
    return "&#39;" . str_replace( '\\', '', $value[0] ) . "&#39;";
}



function upstream_logo_url() {
    $option = get_option( 'upstream_general' );
    $logo   = $option['logo'];
    return apply_filters( 'upstream_logo', $logo );
}
function upstream_login_heading() {
    $option = get_option( 'upstream_general' );
    return isset( $option['login_heading'] ) ? $option['login_heading'] : '';
}
function upstream_login_text() {
    $option = get_option( 'upstream_general' );
    return isset( $option['login_text'] ) ? wp_kses_post( wpautop( $option['login_text'] ) ) : '';
}
function upstream_admin_email() {
    $option = get_option( 'upstream_general' );
    return isset( $option['admin_email'] ) ? $option['admin_email'] : '';
}
function upstream_support_link() {
   	if( isset( $option['admin_support_link'] ) ) {
		return isset( $option['admin_support_link'] ) ? $option['admin_support_link'] : '';
	} else {
		return isset( $option['admin_email'] ) ? $option['admin_email'] : '';
	}
}

/**
 * Check if Milestones are disabled for the current open project.
 * If no ID is passed, this function tries to guess it by checking $_GET/$_POST vars.
 *
 * @since   1.8.0
 *
 * @param   int     $post_id The project ID to be checked
 *
 * @return  bool
 */
function upstream_are_milestones_disabled($post_id = 0)
{
    $areMilestonesDisabled = false;
    $post_id = (int)$post_id;

    if ($post_id <= 0) {
        $post_id = (int)upstream_post_id();
    }

    if ($post_id > 0) {
        $theMeta = get_post_meta($post_id, '_upstream_project_disable_milestones', false);
        $areMilestonesDisabled = !empty($theMeta) && $theMeta[0] === 'on';
    }

    return $areMilestonesDisabled;
}

/**
 * Check if Tasks are disabled for the current open project.
 * If no ID is passed, this function tries to guess it by checking $_GET/$_POST vars.
 *
 * @since   1.8.0
 *
 * @param   int     $post_id The project ID to be checked
 *
 * @return  bool
 */
function upstream_are_tasks_disabled($post_id = 0)
{
    $areTasksDisabled = false;
    $post_id = (int)$post_id;

    if ($post_id <= 0) {
        $post_id = (int)upstream_post_id();
    }

    if ($post_id > 0) {
        $theMeta = get_post_meta($post_id, '_upstream_project_disable_tasks', false);
        $areTasksDisabled = !empty($theMeta) && $theMeta[0] === 'on';
    }

    return $areTasksDisabled;
}

/**
 * Check if Bugs are disabled for the current open project.
 * If no ID is passed, this function tries to guess it by checking $_GET/$_POST vars.
 *
 * @since   1.8.0
 *
 * @param   int     $post_id The project ID to be checked
 *
 * @return  bool
 */
function upstream_are_bugs_disabled($post_id = 0)
{
    $areBugsDisabled = false;
    $post_id = (int)$post_id;

    if ($post_id <= 0) {
        $post_id = (int)upstream_post_id();
    }

    if ($post_id > 0) {
        $theMeta = get_post_meta($post_id, '_upstream_project_disable_bugs', false);
        $areBugsDisabled = !empty($theMeta) && $theMeta[0] === 'on';
    }

    return $areBugsDisabled;
}

/**
 * Check if Files are disabled for the current open project.
 * If no ID is passed, this function tries to guess it by checking $_GET/$_POST vars.
 *
 * @since   1.8.0
 *
 * @param   int     $post_id The project ID to be checked
 *
 * @return  bool
 */
function upstream_are_files_disabled($post_id = 0)
{
    $areBugsDisabled = false;
    $post_id = (int)$post_id;

    if ($post_id <= 0) {
        $post_id = (int)upstream_post_id();
    }

    if ($post_id > 0) {
        $theMeta = get_post_meta($post_id, '_upstream_project_disable_files', false);
        $areBugsDisabled = !empty($theMeta) && $theMeta[0] === 'on';
    }

    return $areBugsDisabled;
}

function upstream_tinymce_quicktags_settings($tinyMCE)
{
    if (preg_match('/^(?:_upstream_project_|description|notes|new_message)/i', $tinyMCE['id'])) {
        $tinyMCE['buttons'] = 'strong,em,link,del,ul,ol,li,close';
    }

    return $tinyMCE;
}

function upstream_tinymce_before_init_setup_toolbar($tinyMCE)
{
    if (preg_match('/_upstream_project_|#description|#notes|#new_message|#upstream/i', $tinyMCE['selector'])) {
        $tinyMCE['toolbar1'] = 'bold,italic,underline,strikethrough,bullist,numlist,link';
        $tinyMCE['toolbar2'] = '';
        $tinyMCE['toolbar3'] = '';
        $tinyMCE['toolbar4'] = '';
    }

    return $tinyMCE;
}

function upstream_tinymce_before_init($tinyMCE)
{
    if (preg_match('/_upstream_project_|#description|#notes|#new_message|#upstream/i', $tinyMCE['selector'])) {
        if (isset($tinyMCE['plugins'])) {
            $pluginsToBeAdded = array(
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
                'wpview'
            );

            $pluginsList = explode(',', $tinyMCE['plugins']);
            $pluginsListUnique = array_unique(array_merge($pluginsList, $pluginsToBeAdded));

            $tinyMCE['plugins'] = implode(',', $pluginsListUnique);
        }
    }

    return $tinyMCE;
}

function upstream_disable_tasks()
{
    $options = get_option('upstream_general');

    $disable_tasks = isset($options['disable_tasks']) ? (array)$options['disable_tasks'] : array('no');

    $areTasksDisabled = $disable_tasks[0] === 'yes';

    return $areTasksDisabled;
}

function upstream_disable_milestones()
{
    $options = get_option('upstream_general');

    $disable_milestones = isset($options['disable_milestones']) ? (array)$options['disable_milestones'] : array('no');

    $areMilestonesDisabled = $disable_milestones[0] === 'yes';

    return $areMilestonesDisabled;
}

function upstream_disable_files()
{
    $options = get_option('upstream_general');

    $disable_files = isset($options['disable_files']) ? (array)$options['disable_files'] : array('no');

    $areFilesDisabled = $disable_files[0] === 'yes';

    return $areFilesDisabled;
}

function upstream_disable_discussions()
{
    $options = get_option('upstream_general');

    $disable_discussion = isset($options['disable_discussion']) ? (array)$options['disable_discussion'] : array('no');

    $areDiscussionsDisabled = $disable_discussion[0] === 'yes';

    return $areDiscussionsDisabled;
}

/**
 * Apply OEmbed filters to a given string in an attempt to render potential embeddable content.
 * This function is called as a callback from CMB2 field method 'escape_cb'.
 *
 * @since   1.10.0
 *
 * @see     https://github.com/CMB2/CMB2/wiki/Field-Parameters#escape_cb
 *
 * @uses    $wp_embed
 *
 * @param   mixed       $content    The unescaped content to be analyzed.
 * @param   array       $field_args Array of field arguments.
 * @param   \CMB2_Field $field      The field instance.
 *
 * @return  mixed                   Escaped value to be displayed.
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
