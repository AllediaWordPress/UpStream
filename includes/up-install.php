<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Install
 *
 * Runs on plugin install by setting up the post types, custom taxonomies,
 * flushing rewrite rules to initiate the new 'downloads' slug and also
 * creates the plugin and populates the settings fields for those plugin
 * pages. After successful install, the user is redirected to the UpStream Welcome
 * screen.
 *
 * @param bool  $network_side If the plugin is being network-activated
 *
 * @return void
 * @global      $upstream_options
 * @global      $wp_version
 *
 * @since 1.0
 * @global      $wpdb
 */

/**
 * Check UpStream minimum requirements: PHP and WordPress versions.
 * This function calls wp_die() if any of the minimum requirements is not satisfied.
 *
 * @since   1.10.1
 *
 * @uses    wp_die()
 */
function upstream_check_min_requirements()
{
    global $wp_version;

    $minWPVersionRequired  = "4.5";
    $minPHPVersionRequired = "5.6.20";

    // Check PHP version.
    if (version_compare(PHP_VERSION, $minPHPVersionRequired, '<')) {
        $errorMessage = sprintf(
            '<p>' . __('It seems you are running an outdated version of PHP: <code>%s</code>.', 'upstream') . '</p>' .
            '<p>' . __(
                'For security reasons, UpStream requires at least PHP version <code>%s</code> to run.',
                'upstream'
            ) . '</p>' .
            '<p>' . __('Please, consider upgrading your PHP version.', 'upstream') . '</p><br /><br />',
            PHP_VERSION,
            $minPHPVersionRequired
        );
    } // Check WordPress version.
    elseif (version_compare($wp_version, $minWPVersionRequired, '<')) {
        $errorMessage = sprintf(
            '<p>' . __(
                'It seems you are running an outdated version of WordPress: <code>%s</code>.',
                'upstream'
            ) . '</p>' .
            '<p>' . __(
                'For security reasons, UpStream requires at least version <code>%s</code> to run.',
                'upstream'
            ) . '</p>' .
            '<p>' . __('Please, consider upgrading your WordPress.', 'upstream') . '</p><br /><br />',
            $wp_version,
            $minWPVersionRequired
        );
    }

    if (isset($errorMessage)) {
        $errorMessage .= '<a class="button" href="javascript:history.back();">' . __('Go Back', 'upstream') . '</a>';

        wp_die($errorMessage);
    }
}

function upstream_install($network_wide = false)
{
    global $wpdb;

    upstream_check_min_requirements();

    if (is_multisite() && $network_wide) {
        foreach ($wpdb->get_col("SELECT blog_id FROM $wpdb->blogs LIMIT 100") as $blog_id) {
            switch_to_blog($blog_id);
            upstream_run_install();
            restore_current_blog();
        }
    } else {
        upstream_run_install();
    }

    flush_rewrite_rules();
}

register_activation_hook(UPSTREAM_PLUGIN_FILE, 'upstream_install');
register_deactivation_hook(UPSTREAM_PLUGIN_FILE, 'upstream_uninstall');
add_action('upstream_update_data', 'upstream_update_data', 10, 2);

/**
 * Run the UpStream Install process
 *
 * @return void
 * @since  2.5
 */
function upstream_run_install()
{
    // RSD: to ensure the current user has manage_upstream capability
    $user = wp_get_current_user();
    $user->add_cap('manage_upstream');

    // Setup the Downloads Custom Post Type
    upstream_setup_post_types();

    // Setup the Download Taxonomies
    upstream_setup_taxonomies();

    // Add the default options
    upstream_add_default_options();

    // Clear the permalinks
    flush_rewrite_rules(false);

    // Add upgraded_from option
    $current_version = get_option('upstream_version', false);
    $freshInstall    = empty($current_version);

    if ( ! $freshInstall) {
        update_option('upstream_version_upgraded_from', $current_version);
    }

    update_option('upstream_version', UPSTREAM_VERSION);

    // Create UpStream roles
    $roles = new UpStream_Roles;
    $roles->add_roles();

    if ($freshInstall) {
        upstream_run_fresh_install();

        // Make sure we don't redirect if activating from network, or bulk.
        if ( ! is_network_admin() && ! isset($_GET['activate-multi'])) {
            // Add the transient to redirect
            set_transient('_upstream_activation_redirect', true, 30);
        }
    } else {
        upstream_run_reinstall();

        do_action('upstream_update_data', $current_version, UPSTREAM_VERSION);
    }
}

/**
 * Run the fresh UpStream Install process
 *
 * @return void
 * @since  2.5
 */
function upstream_run_fresh_install()
{
    // Add default capabilities for roles.
    $roles = new UpStream_Roles;
    $roles->add_default_caps();
}

/**
 * Run the UpStream Reinstall process
 *
 * @return void
 * @since  2.5
 */
function upstream_run_reinstall()
{

}

/**
 * Runs the UpStream uninstall process.
 */
function upstream_uninstall()
{

    flush_rewrite_rules();

    // RSD: deactivate any child plugins
    if (is_plugin_active('UpStream-Reports-PDF/upstream-reports-pdf.php')) {
        add_action('update_option_active_plugins', 'upstream_deactivate_dependency_reports_pdf');
    }
    if (is_plugin_active('UpStream-Reports/upstream-reports.php')) {
        add_action('update_option_active_plugins', 'upstream_deactivate_dependency_reports');
    }
    if (is_plugin_active('UpStream-Copy-Project/upstream-copy-project.php')) {
        add_action('update_option_active_plugins', 'upstream_deactivate_dependency_copy_project');
    }
    if (is_plugin_active('UpStream-Custom-Fields/upstream-custom-fields.php')) {
        add_action('update_option_active_plugins', 'upstream_deactivate_dependency_custom_fields');
    }
    if (is_plugin_active('UpStream-Customizer/upstream-customizer.php')) {
        add_action('update_option_active_plugins', 'upstream_deactivate_dependency_customizer');
    }
    if (is_plugin_active('UpStream-Email-Notifications/upstream-email-notifications.php')) {
        add_action('update_option_active_plugins', 'upstream_deactivate_dependency_email_notifications');
    }
    if (is_plugin_active('UpStream-Calendar-View/upstream-calendar-view.php')) {
        add_action('update_option_active_plugins', 'upstream_deactivate_dependency_calendar_view');
    }
    if (is_plugin_active('UpStream-Frontend-Edit/upstream-frontend-edit.php')) {
        add_action('update_option_active_plugins', 'upstream_deactivate_dependency_frontend_edit');
    }
    if (is_plugin_active('UpStream-Project-Timeline/upstream-project-timeline.php')) {
        add_action('update_option_active_plugins', 'upstream_deactivate_dependency_project_timeline');
    }
}

function upstream_deactivate_dependency_calendar_view()

{
    up_debug();

    deactivate_plugins('UpStream-Calendar-View/upstream-calendar-view.php');
}

function upstream_deactivate_dependency_reports_pdf()

{
    up_debug();

    deactivate_plugins('UpStream-Reports-PDF/upstream-reports-pdf.php');
}

function upstream_deactivate_dependency_reports()

{
    up_debug();

    deactivate_plugins('UpStream-Reports/upstream-reports.php');
}

function upstream_deactivate_dependency_copy_project()

{
    up_debug();

    deactivate_plugins('UpStream-Copy-Project/upstream-copy-project.php');
}

function upstream_deactivate_dependency_project_timeline()

{
    up_debug();

    deactivate_plugins('UpStream-Project-Timeline/upstream-project-timeline.php');
}

function upstream_deactivate_dependency_frontend_edit()

{
    up_debug();

    deactivate_plugins('UpStream-Frontend-Edit/upstream-frontend-edit.php');
}

function upstream_deactivate_dependency_email_notifications()

{
    up_debug();

    deactivate_plugins('UpStream-Email-Notifications/upstream-email-notifications.php');
}

function upstream_deactivate_dependency_customizer()

{
    up_debug();

    deactivate_plugins('UpStream-Customizer/upstream-customizer.php');
}

function upstream_deactivate_dependency_custom_fields()

{
    up_debug();

    deactivate_plugins('UpStream-Custom-Fields/upstream-custom-fields.php');
}

function upstream_add_default_options()
{
    up_debug();

    // general options
    $general = get_option('upstream_general');
    if ( ! $general || empty($general)) {
        $general['project']['single']       = 'Project';
        $general['project']['plural']       = 'Projects';
        $general['client']['single']        = 'Client';
        $general['client']['plural']        = 'Clients';
        $general['milestone']['single']     = 'Milestone';
        $general['milestone']['plural']     = 'Milestones';
        $general['milestone_category']['single'] = 'Milestone Category';
        $general['milestone_category']['plural'] = 'Milestone Categories';
        $general['task']['single']          = 'Task';
        $general['task']['plural']          = 'Tasks';
        $general['bug']['single']           = 'Bug';
        $general['bug']['plural']           = 'Bugs';
        $general['file']['single']          = 'File';
        $general['file']['plural']          = 'Files';

        $general['login_heading'] = 'Project Login';
        $general['admin_email']   = get_bloginfo('admin_email');

        update_option('upstream_general', $general);
    }

    $cachedIds        = [];
    $generateRandomId = function () use (&$cachedIds) {
        do {
            $randomId = upstreamGenerateRandomString(5, 'abcdefghijklmnopqrstuvwxyz0123456789');
        } while (isset($cachedIds[$randomId])); // Isset is faster than in_array in this case.

        $cachedIds[$randomId] = null;

        return $randomId;
    };

    // project options
    $projects = get_option('upstream_projects');
    if ( ! $projects || empty($projects)) {
        $projects['statuses'][0]['name']  = 'In Progress';
        $projects['statuses'][0]['color'] = '#5cbfd1';
        $projects['statuses'][0]['type']  = 'open';
        $projects['statuses'][0]['id']    = $generateRandomId();

        $projects['statuses'][1]['name']  = 'Overdue';
        $projects['statuses'][1]['color'] = '#d15d5c';
        $projects['statuses'][1]['type']  = 'open';
        $projects['statuses'][1]['id']    = $generateRandomId();

        $projects['statuses'][2]['name']  = 'Closed';
        $projects['statuses'][2]['color'] = '#6b6b6b';
        $projects['statuses'][2]['type']  = 'closed';
        $projects['statuses'][2]['id']    = $generateRandomId();

        update_option('upstream_projects', $projects);
    }

    // task options
    $tasks = get_option('upstream_tasks');
    if ( ! $tasks || empty($tasks)) {
        $tasks['statuses'][0]['name']  = 'In Progress';
        $tasks['statuses'][0]['color'] = '#5cbfd1';
        $tasks['statuses'][0]['type']  = 'open';
        $tasks['statuses'][0]['id']    = $generateRandomId();

        $tasks['statuses'][1]['name']  = 'Overdue';
        $tasks['statuses'][1]['color'] = '#d15d5c';
        $tasks['statuses'][1]['type']  = 'open';
        $tasks['statuses'][1]['id']    = $generateRandomId();

        $tasks['statuses'][2]['name']  = 'Completed';
        $tasks['statuses'][2]['color'] = '#5cd165';
        $tasks['statuses'][2]['type']  = 'closed';
        $tasks['statuses'][2]['id']    = $generateRandomId();

        $tasks['statuses'][3]['name']  = 'Closed';
        $tasks['statuses'][3]['color'] = '#6b6b6b';
        $tasks['statuses'][3]['type']  = 'closed';
        $tasks['statuses'][3]['id']    = $generateRandomId();

        update_option('upstream_tasks', $tasks);
    }

    // bug options
    $bugs = get_option('upstream_bugs');
    if ( ! $bugs || empty($bugs)) {
        $bugs['statuses'][0]['name']  = 'In Progress';
        $bugs['statuses'][0]['color'] = '#5cbfd1';
        $bugs['statuses'][0]['type']  = 'open';
        $bugs['statuses'][0]['id']    = $generateRandomId();

        $bugs['statuses'][1]['name']  = 'Overdue';
        $bugs['statuses'][1]['color'] = '#d15d5c';
        $bugs['statuses'][1]['type']  = 'open';
        $bugs['statuses'][1]['id']    = $generateRandomId();

        $bugs['statuses'][2]['name']  = 'Completed';
        $bugs['statuses'][2]['color'] = '#5cd165';
        $bugs['statuses'][2]['type']  = 'closed';
        $bugs['statuses'][2]['id']    = $generateRandomId();

        $bugs['statuses'][3]['name']  = 'Closed';
        $bugs['statuses'][3]['color'] = '#6b6b6b';
        $bugs['statuses'][3]['type']  = 'closed';
        $bugs['statuses'][3]['id']    = $generateRandomId();

        $bugs['severities'][0]['name']  = 'Critical';
        $bugs['severities'][0]['color'] = '#d15d5c';
        $bugs['severities'][0]['id']    = $generateRandomId();

        $bugs['severities'][1]['name']  = 'Standard';
        $bugs['severities'][1]['color'] = '#d17f5c';
        $bugs['severities'][1]['id']    = $generateRandomId();

        $bugs['severities'][2]['name']  = 'Minor';
        $bugs['severities'][2]['color'] = '#d1a65c';
        $bugs['severities'][2]['id']    = $generateRandomId();

        update_option('upstream_bugs', $bugs);
    }
}


/**
 * When a new Blog is created in multisite, see if UpStream is network activated, and run the installer
 *
 * @param int    $blog_id The Blog ID created
 * @param int    $user_id The User ID set as the admin
 * @param string $domain  The URL
 * @param string $path    Site Path
 * @param int    $site_id The Site ID
 * @param array  $meta    Blog Meta
 *
 * @return void
 * @since  1.0.0
 *
 */
function upstream_new_blog_created($blog_id, $user_id, $domain, $path, $site_id, $meta)
{
    up_debug();

    if (is_plugin_active_for_network(plugin_basename(UPSTREAM_PLUGIN_FILE))) {
        switch_to_blog($blog_id);
        upstream_install();
        restore_current_blog();
    }
}

add_action('wpmu_new_blog', 'upstream_new_blog_created', 10, 6);


/**
 * Post-installation
 *
 * Runs just after plugin installation and exposes the
 * upstream_after_install hook.
 *
 * @return void
 * @since 1.0.0
 */
function upstream_after_install()
{
    up_debug();

    if ( ! is_admin()) {
        return;
    }

    $activated = get_transient('_upstream_activation_redirect');

    if (false !== $activated) {

        // add the default options
        //upstream_add_default_project();
        delete_transient('_upstream_activation_redirect');

        if ( ! isset($_GET['activate-multi'])) {
            set_transient('_upstream_redirected', true, 360);
            wp_redirect(admin_url('post-new.php?post_type=project'));
            exit;
        }
    }
}

add_action('admin_init', 'upstream_after_install', 100);


/**
 * Show a success notice after install and redirect.
 */
function upstream_install_success_notice()
{
    up_debug();

    $redirected = get_transient('_upstream_redirected');

    if (false !== $redirected && isset($_GET['page']) && $_GET['page'] == 'upstream_general') {
        // Delete the transient
        //delete_transient( '_upstream_redirected' );

        $class   = 'notice notice-info is-dismissible';
        $message = '<strong>' . __('Success! UpStream is up and running.', 'upstream') . '</strong><br>';
        $message .= __(
                        'Step 1. Please go through each settings tab below and configure the options.',
                        'upstream'
                    ) . '<br>';
        $message .= __(
                        'Step 2. Add a new Client by navigating to <strong>Projects > New Client</strong>',
                        'upstream'
                    ) . '<br>';
        $message .= __(
                        'Step 3. Add your first Project by navigating to <strong>Projects > New Project</strong>',
                        'upstream'
                    ) . '<br>';

        printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
    }
}

add_action('admin_notices', 'upstream_install_success_notice');


/**
 * The original data update function. This is superceded by rev2 as of version 1.27.
 *
 * @param $old_version
 * @param $new_version
 * @throws Exception
 */
function upstream_update_data($old_version, $new_version)
{
    up_debug();

    // Ignore if we are on the same version.
    if ($old_version === $new_version) {
        return;
    }

    if (version_compare($new_version, '1.22.0', '=')) {
        // Make sure administrator and managers are able to
        $roles = [
            'upstream_manager',
            'administrator',
            'upstream_user',
        ];

        foreach ($roles as $role) {
            $role = get_role($role);

            if (is_object($role)) {
                $role->add_cap('project_title_field', true);
                $role->add_cap('project_status_field', true);
                $role->add_cap('project_owner_field', true);
                $role->add_cap('project_client_field', true);
                $role->add_cap('project_users_field', true);
                $role->add_cap('project_start_date_field', true);
                $role->add_cap('project_end_date_field', true);
            }
        }
    }

    if (version_compare($old_version, '1.22.1', '<')) {
        // Force to fix bug statuses and severities with empty ID.
        delete_option('upstream:created_bugs_args_ids');

        UpStream_Options_Bugs::createBugsStatusesIds();
    }

    $hasFinishedMigration = get_option('_upstream_migration_finished_1.24.0', null);
    if (empty($hasFinishedMigration) && version_compare($old_version, '1.24.0', '<')) {
        $this->upstreamMilestoneTags();

        // Default labels for Milestone Categories
        $general = get_option('upstream_general');

        $general['milestone_category']['single'] = 'Milestone Category';
        $general['milestone_category']['plural'] = 'Milestone Categories';

        update_option('upstream_general', $general);


        // Make sure administrator and managers are able to work with the new milestones.
        $roles = [
            'upstream_manager',
            'administrator',
            'upstream_user',
        ];

        foreach ($roles as $role) {
            $role = get_role($role);

            if (is_object($role)) {
                $capabilities = [
                    // Post type
                    'edit_milestone',
                    'read_milestone',
                    'delete_milestone',
                    'edit_milestones',
                    'edit_others_milestones',
                    'publish_milestones',
                    'read_private_milestones',
                    'delete_milestones',
                    'delete_private_milestones',
                    'delete_published_milestones',
                    'delete_others_milestones',
                    'edit_private_milestones',
                    'edit_published_milestones',

                    // Terms
                    'manage_milestone_terms',
                    'edit_milestone_terms',
                    'delete_milestone_terms',
                    'assign_milestone_terms',
                ];

                foreach ($capabilities as $capability) {
                    $role->add_cap($capability, true);
                }
            }
        }

        // If we have projects, create new milestones based on current ones.
        $projects = get_posts(
            [
                'post_type'      => 'project',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'meta_query'     => [
                    'relation' => 'OR',
                    [
                        'key'     => '_upstream_milestones_migrated',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key'     => '_upstream_milestones_migrated',
                        'value'   => 1,
                        'compare' => '!=',
                    ],
                ],
            ]
        );

        if ( ! empty($projects)) {
            // Migrate the milestones.
            $defaultMilestones = get_option('upstream_milestones', []);

            if ( ! empty($defaultMilestones)) {
                foreach ($projects as $project) {
                    \UpStream\Milestones::migrateLegacyMilestonesForProject($project->ID);
                }
            }
        }

        update_option('_upstream_migration_finished_1.24.0', true);
    }

    $hasFinishedMigration = get_option('_upstream_migration_finished_1.24.1', null);
    if (empty($hasFinishedMigration) && version_compare($old_version, '1.24.1', '<')) {
        // If we have unpublished projects, create new milestones based on current ones.
        $projects = get_posts(
            [
                'post_type'      => 'project',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'meta_query'     => [
                    'relation' => 'OR',
                    [
                        'key'     => '_upstream_milestones_migrated',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key'     => '_upstream_milestones_migrated',
                        'value'   => 1,
                        'compare' => '!=',
                    ],
                ],
            ]
        );

        if ( ! empty($projects)) {
            // Migrate the milestones.
            $defaultMilestones = get_option('upstream_milestones', []);

            if ( ! empty($defaultMilestones)) {
                foreach ($projects as $project) {
                    \UpStream\Milestones::migrateLegacyMilestonesForProject($project->ID);
                }
            }
        }

        update_option('_upstream_migration_finished_1.24.1', true);
    }

    $version              = '1.24.2';
    $migrationOption      = '_upstream_migration_finished_' . $version;
    $hasFinishedMigration = get_option($migrationOption, null);
    if (empty($hasFinishedMigration) && version_compare($old_version, $version, '<')) {
        // If we have unpublished projects, create new milestones based on current ones.
        $projects = get_posts(
            [
                'post_type'      => 'project',
                'post_status'    => 'any',
                'posts_per_page' => -1,
            ]
        );

        if ( ! empty($projects)) {
            foreach ($projects as $project) {
                \UpStream\Milestones::fixMilestoneOrdersOnProject($project->ID);
            }
        }

        update_option($migrationOption, true);
    }


    // do the first new migration to get everything to 1.27

    $migrationId          = 'M0000001';
    $migrationOption      = '_upstream_migration_finished_' . $migrationId;
    $hasFinishedMigration = get_option($migrationOption, null);
    if (empty($hasFinishedMigration)) {

        if ( ! class_exists('Upstream_Counts')) {
            include_once UPSTREAM_PLUGIN_DIR . '/includes/class-up-counts.php';
        }

        $counts   = new Upstream_Counts(0);
        $projects = $counts->projects;

        if ( ! empty($projects)) {
            foreach ($projects as $project) {
                $projectObject = new UpStream_Project($project->ID);
                $projectObject->update_project_meta();
            }
        }

        update_option($migrationOption, true);
    }

}

/**
 * Show a message if the plugin needs to be reactivated
 */
function upstream_reactivate_notice()
{
    up_debug();

    $class   = 'notice notice-info is-dismissible';
    $message = '<strong>' . __('UpStream needs to be reactivated.', 'upstream') . '</strong><br>';
    $message .= __(
            'In order to complete the upgrade to this version, you will need to deactivate and re-activate Upstream. Make sure Remove Data under the UpStream menu is unchecked so you do not lose any data.',
            'upstream'
        ) . '<br>';

    printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
}


/**
 * This is a replacement for upstream_update_data(). It is used to update any data from the
 * 1.27 release and after.
 *
 * @since 1.27
 */
function upstream_update_data_rev_2()
{
    if (!is_admin() || !current_user_can('activate_plugins')) {
        return;
    }

    $current_version = get_option('upstream_version', false);

    if (empty($current_version)) {

        // if current version is not set, this is a new install

    }

    else if ($current_version === UPSTREAM_VERSION) {

        // already at current verison... do nothing

        return;
    }

    else if (version_compare($current_version, '1.26.0', '<')) {

        // if current version is less than 1.26 don't use this function at all
        // wait until someone reactivates the plugin and then this function can run.
        add_action('admin_notices', 'upstream_reactivate_notice');

        return;
    }

    $migrationId          = 'M0000001';
    $migrationOption      = '_upstream_migration_finished_' . $migrationId;
    $hasFinishedMigration = get_option($migrationOption, null);
    if (empty($hasFinishedMigration)) {

        if ( ! class_exists('Upstream_Counts')) {
            include_once UPSTREAM_PLUGIN_DIR . '/includes/class-up-counts.php';
        }

        $counts   = new Upstream_Counts(0);
        $projects = $counts->projects;

        if ( ! empty($projects)) {
            foreach ($projects as $project) {
                $projectObject = new UpStream_Project($project->ID);
                $projectObject->update_project_meta();
            }
        }

        update_option($migrationOption, true);
    }

    // if we made any migrations, update the upstream version to the current one
    update_option('upstream_version_upgraded_from', $current_version);
    update_option('upstream_version', UPSTREAM_VERSION);

}

add_action('admin_init', 'upstream_update_data_rev_2', 90);
