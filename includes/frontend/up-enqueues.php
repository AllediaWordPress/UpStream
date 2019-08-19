<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


/**
 * Removing / Dequeueing All Stylesheets And Scripts
 *
 * @return void
 */
function upstream_enqueue_styles_scripts()
{
    global $wp_styles, $wp_scripts;

    if (get_post_type() === false) {

        if (upstream_is_project_base_uri($_SERVER['REQUEST_URI'])) {

        }

        else {
            return;
        }
    }

    else if (get_post_type() != 'project') {
        return;
    }

    // Dequeue styles
    if (is_array($wp_styles->queue)) {
        /**
         * @param array $styleWhitelist
         *
         * @return array
         */
        $styleWhitelist = (array)apply_filters('upstream_frontend_style_whitelist', []);

        foreach ($wp_styles->queue as $style) {
            if ( ! in_array($style, $styleWhitelist)) {
                wp_dequeue_style($style);
            }
        }
    }
    // Dequeue scripts
    if (is_array($wp_scripts->queue)) {
        /**
         * @param array $scriptWhitelist
         *
         * @return array
         */
        $scriptWhitelist = (array)apply_filters('upstream_frontend_script_whitelist', ['jquery']);

        foreach ($wp_scripts->queue as $script) {
            if ( ! in_array($script, $scriptWhitelist)) {
                wp_dequeue_script($script);
            }
        }
    }

    $up_url  = UPSTREAM_PLUGIN_URL;
    $up_ver  = UPSTREAM_VERSION;
    $lib_dir = 'templates/assets/libraries/';
    $js_dir  = 'templates/assets/js/';
    $css_dir = 'templates/assets/css/';


    /*
     * Enqueue styles
     */

    $dir        = upstream_template_path();
    $maintheme  = trailingslashit(get_template_directory()) . $dir . 'assets/css/';
    $childtheme = trailingslashit(get_stylesheet_directory()) . $dir . 'assets/css/';

    if (!is_admin()) {
        wp_enqueue_style('up-bootstrap', $up_url . $css_dir . 'bootstrap.min.css', [], $up_ver, 'all');
        wp_enqueue_style('up-tableexport', $up_url . $css_dir . 'vendor/tableexport.min.css', [], $up_ver, 'all');
        wp_enqueue_style('up-select2', $up_url . $css_dir . 'vendor/select2.min.css', [], $up_ver, 'all');
        wp_enqueue_style('up-chosen', $up_url . $lib_dir . 'chosen/chosen.min.css', [], $up_ver, 'all');
        wp_enqueue_style('up-fontawesome', $up_url . $css_dir . 'fontawesome.min.css', [], $up_ver, 'all');
        wp_enqueue_style('framework', $up_url . $css_dir . 'framework.css', [], $up_ver, 'all');
        wp_enqueue_style(
            'upstream-datepicker',
            $up_url . $js_dir . 'vendor/bootstrap-datepicker-1.8.0/css/bootstrap-datepicker3.css',
            [],
            $up_ver,
            'all'
        );
        wp_enqueue_style('upstream', $up_url . $css_dir . 'upstream.css', ['admin-bar'], $up_ver, 'all');

        if (isset($GLOBALS['login_template'])) {
            wp_enqueue_style('up-login', $up_url . $css_dir . 'login.css', [], $up_ver, 'all');
        }

        if (file_exists($childtheme)) {
            $custom = trailingslashit(get_stylesheet_directory_uri()) . $dir . 'assets/css/upstream-custom.css';
            wp_enqueue_style('child-custom', $custom, [], $up_ver, 'all');
        }
        if (file_exists($maintheme)) {
            $custom = trailingslashit(get_template_directory_uri()) . $dir . 'assets/css/upstream-custom.css';
            wp_enqueue_style('theme-custom', $custom, [], $up_ver, 'all');
        }

        // Enqueue style for poopy sandbox to complement the admin bar.
        if (class_exists('Sandbox_API') && Sandbox_API::getInstance()->is_poopy_site()) {
            if (file_exists(ABSPATH . 'wp-content/plugins/' . plugin_dir_path('sandbox/sandbox.php') . 'static/css/poopy.css')) {
                wp_enqueue_style('poopy', plugin_dir_url('sandbox/sandbox.php') . '/static/css/poopy.css', [], $up_ver);
            }
        }

        /*
         * Enqueue scripts
         */

        wp_enqueue_script('up-filesaver', $up_url . $js_dir . 'vendor/FileSaver.min.js', [], $up_ver, true);
        wp_enqueue_script('up-tableexport', $up_url . $js_dir . 'vendor/tableexport.min.js', [], $up_ver, true);
        wp_enqueue_script('up-select2', $up_url . $js_dir . 'vendor/select2.full.min.js', [], $up_ver, true);
        wp_enqueue_script('up-chosen', $up_url . $lib_dir . '/chosen/chosen.jquery.min.js', ['jquery'], $up_ver, true);
        wp_enqueue_script('up-bootstrap', $up_url . $js_dir . 'bootstrap.min.js', ['jquery'], $up_ver, true);
        wp_enqueue_script('up-fastclick', $up_url . $js_dir . 'fastclick.js', ['jquery'], $up_ver, true);
        wp_enqueue_script('up-nprogress', $up_url . $js_dir . 'nprogress.js', ['jquery'], $up_ver, true);

        wp_enqueue_script(
            'upstream-datepicker',
            $up_url . $js_dir . 'vendor/bootstrap-datepicker-1.8.0/js/bootstrap-datepicker.min.js',
            ['jquery', 'up-bootstrap'],
            $up_ver,
            true
        );
        wp_enqueue_script('up-modal', $up_url . $js_dir . 'vendor/modal.min.js', ['jquery'], $up_ver, true);

        wp_enqueue_script(
            'upstream',
            $up_url . $js_dir . 'upstream.js',
            ['jquery', 'jquery-ui-sortable', 'up-modal', 'admin-bar'],
            $up_ver,
            true
        );


        $noDataStringTemplate = _x(
            "You haven't created any %s yet",
            '%s: item name, ie Milestones, Tasks, Bugs, Files, Discussion',
            'upstream'
        );

        wp_localize_script('upstream', 'upstream', apply_filters('upstream_localized_javascript', [
            'ajaxurl'              => admin_url('admin-ajax.php'),
            'upload_url'           => admin_url('async-upload.php'),
            'security'             => wp_create_nonce('upstream-nonce'),
            'js_date_format'       => upstream_php_to_js_dateformat(),
            'datepickerDateFormat' => upstreamGetDateFormatForJsDatepicker(),
            'langs'                => [
                'LB_COPY'                 => __('Copy', 'upstream'),
                'LB_CSV'                  => __('CSV', 'upstream'),
                'LB_SEARCH'               => __('Search:', 'upstream'),
                'MSG_TABLE_NO_DATA_FOUND' => _x(
                    "You haven't created any %s yet",
                    '%s: item name, ie Milestones, Tasks, Bugs, Files, Discussion',
                    'upstream'
                ),
                'MSG_NO_MILESTONES_YET'   => sprintf($noDataStringTemplate, upstream_milestone_label_plural()),
                'MSG_NO_TASKS_YET'        => sprintf($noDataStringTemplate, upstream_task_label_plural()),
                'MSG_NO_BUGS_YET'         => sprintf($noDataStringTemplate, upstream_bug_label_plural()),
                'MSG_NO_FILES_YET'        => sprintf($noDataStringTemplate, upstream_file_label_plural()),
                'MSG_NO_DISCUSSION_YET'   => sprintf($noDataStringTemplate, upstream_discussion_label()),
                'LB_SUNDAY'               => __('Sunday', 'upstream'),
                'LB_MONDAY'               => __('Monday', 'upstream'),
                'LB_TUESDAY'              => __('Tuesday', 'upstream'),
                'LB_WEDNESDAY'            => __('Wednesday', 'upstream'),
                'LB_THURSDAY'             => __('Thursday', 'upstream'),
                'LB_FRIDAY'               => __('Friday', 'upstream'),
                'LB_SATURDAY'             => __('Saturday', 'upstream'),
                'LB_SUN'                  => __('Sun', 'upstream'),
                'LB_MON'                  => __('Mon', 'upstream'),
                'LB_TUE'                  => __('Tue', 'upstream'),
                'LB_WED'                  => __('Wed', 'upstream'),
                'LB_THU'                  => __('Thu', 'upstream'),
                'LB_FRI'                  => __('Fri', 'upstream'),
                'LB_SAT'                  => __('Sat', 'upstream'),
                'LB_SU'                   => __('Su', 'upstream'),
                'LB_MO'                   => __('Mo', 'upstream'),
                'LB_TU'                   => __('Tu', 'upstream'),
                'LB_WE'                   => __('We', 'upstream'),
                'LB_TH'                   => __('Th', 'upstream'),
                'LB_FR'                   => __('Fr', 'upstream'),
                'LB_SA'                   => __('Sa', 'upstream'),
                'LB_JANUARY'              => __('January', 'upstream'),
                'LB_FEBRUARY'             => __('February', 'upstream'),
                'LB_MARCH'                => __('March', 'upstream'),
                'LB_APRIL'                => __('April', 'upstream'),
                'LB_MAY'                  => __('May', 'upstream'),
                'LB_JUNE'                 => __('June', 'upstream'),
                'LB_JULY'                 => __('July', 'upstream'),
                'LB_AUGUST'               => __('August', 'upstream'),
                'LB_SEPTEMBER'            => __('September', 'upstream'),
                'LB_OCTOBER'              => __('October', 'upstream'),
                'LB_NOVEMBER'             => __('November', 'upstream'),
                'LB_DECEMBER'             => __('December', 'upstream'),
                'LB_JAN'                  => __('Jan', 'upstream'),
                'LB_FEB'                  => __('Feb', 'upstream'),
                'LB_MAR'                  => __('Mar', 'upstream'),
                'LB_APR'                  => __('Apr', 'upstream'),
                'LB_JUN'                  => __('Jun', 'upstream'),
                'LB_JUL'                  => __('Jul', 'upstream'),
                'LB_AUG'                  => __('Aug', 'upstream'),
                'LB_SEP'                  => __('Sep', 'upstream'),
                'LB_OCT'                  => __('Oct', 'upstream'),
                'LB_NOV'                  => __('Nov', 'upstream'),
                'LB_DEC'                  => __('Dec', 'upstream'),
                'LB_TODAY'                => __('Today', 'upstream'),
                'LB_CLEAR'                => __('Clear', 'upstream'),
            ],
        ]));
        do_action('upstream_frontend_enqueue_scripts');
    }
}

add_action(
    'wp_enqueue_scripts',
    'upstream_enqueue_styles_scripts',
    1000
); // Hook this late enough so all stylesheets / scripts has been added (to be further dequeued by this action)

// Removes the "next"/"prev" <link rel /> tags. This prevents links to another projects appearing on the HTML code.
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');

function upstream_deregister_assets()
{
    $isAdmin  = is_admin();
    $postType = get_post_type();

    if ($isAdmin
        || $postType !== 'project'
    ) {
        return;
    }

    wp_dequeue_script('jquery-ui-datepicker');
    wp_deregister_script('jquery-ui-datepicker');
}

add_action('wp_enqueue_scripts', 'upstream_deregister_assets', 10);
