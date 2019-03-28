<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


function upstream_hide_meta_boxes()
{
    remove_meta_box('authordiv', 'project', 'normal');
}

add_action('admin_menu', 'upstream_hide_meta_boxes');

/**
 * UpStream_Roles Class
 *
 * This class handles the role creation and assignment of capabilities for those roles.
 *
 *
 * @since 1.0.0
 */
class UpStream_Roles
{

    /**
     * Add new shop roles with default WP caps
     * Called during installation
     *
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function add_roles()
    {
        global $wp_roles;

        if ( ! $wp_roles->is_role('upstream_manager')) {
            add_role('upstream_manager', __('UpStream Manager', 'upstream'), [
                'read'                   => true,
                'edit_posts'             => true,
                'delete_posts'           => true,
                'unfiltered_html'        => true,
                'upload_files'           => true,
                'export'                 => true,
                'import'                 => true,
                'delete_others_pages'    => true,
                'delete_others_posts'    => true,
                'delete_pages'           => true,
                'delete_private_pages'   => true,
                'delete_private_posts'   => true,
                'delete_published_pages' => true,
                'delete_published_posts' => true,
                'edit_others_pages'      => true,
                'edit_others_posts'      => true,
                'edit_pages'             => true,
                'edit_private_pages'     => true,
                'edit_private_posts'     => true,
                'edit_published_pages'   => true,
                'edit_published_posts'   => true,
                'manage_categories'      => true,
                'manage_links'           => true,
                'moderate_comments'      => true,
                'publish_pages'          => true,
                'publish_posts'          => true,
                'read_private_pages'     => true,
                'read_private_posts'     => true,
            ]);
        }

        if ( ! $wp_roles->is_role('upstream_user')) {
            add_role('upstream_user', __('UpStream User', 'upstream'), [
                'read'         => true,
                'edit_posts'   => true,
                'upload_files' => true,
            ]);
        }

        if ( ! $wp_roles->is_role('upstream_client_user')) {
            add_role('upstream_client_user', __('UpStream Client User', 'upstream'), [
                'read'         => true,
                'upload_files' => true,
            ]);
        }
    }

    /**
     * Add default UpStream capabilities
     *
     * @param string    $role
     *
     * @access public
     * @since  1.0.0
     * @global WP_Roles $wp_roles
     * @return void
     */
    public function add_default_caps($role = null)
    {
        global $wp_roles;

        if (class_exists('WP_Roles')) {
            if ( ! isset($wp_roles)) {
                $wp_roles = new WP_Roles();
            }
        }

        if (is_object($wp_roles)) {

            if (is_null($role)) {
                $rolesName = [
                    'administrator',
                    'upstream_manager',
                    'upstream_user',
                    'upstream_client_user',
                ];
            } else {
                $rolesName = [$role];
            }

            foreach ($rolesName as $roleName) {
                switch ($roleName) {
                    case 'administrator':
                        // Add the main post type capabilities
                        $capabilities = $this->get_upstream_manager_caps();
                        foreach ($capabilities as $cap_group) {
                            foreach ($cap_group as $cap) {
                                $wp_roles->add_cap($roleName, $cap);
                            }
                        }

                        break;

                    case 'upstream_manager':
                        // Add the main post type capabilities
                        $capabilities = $this->get_upstream_manager_caps();
                        foreach ($capabilities as $cap_group) {
                            foreach ($cap_group as $cap) {
                                $wp_roles->add_cap($roleName, $cap);
                            }
                        }

                        break;

                    case 'upstream_user':
                        // Add the main post type capabilities
                        $capabilities = $this->get_upstream_user_caps();
                        foreach ($capabilities as $cap_group) {
                            foreach ($cap_group as $cap) {
                                $wp_roles->add_cap($roleName, $cap);
                            }
                        }

                        break;

                    case 'upstream_client_user':
                        // Add the main post type capabilities
                        $capabilities = $this->get_upstream_user_caps();
                        foreach ($capabilities as $cap_group) {
                            foreach ($cap_group as $cap) {
                                $wp_roles->add_cap($roleName, $cap);
                            }
                        }

                        break;
                }
            }

            // Apply the default capabilities only once.
            $not_set = (int)get_option('upstream_default_capabilities_set') !== 1;

            if ($not_set) {
                // By default add capability for adding images in comments too all roles.
                $roles = array_keys(get_editable_roles());
                foreach ($roles as $role_name) {
                    $role = get_role($role_name);
                    $role->add_cap('upstream_comment_images', true);
                }
                update_option('upstream_default_capabilities_set', 1);
            }
        }
    }

    /**
     * Gets the core post type capabilities
     *
     * @access public
     * @since  1.0.0
     * @return array $capabilities Core post type capabilities
     */
    public function get_upstream_manager_caps()
    {
        $capabilities = [];

        $capability_types = ['project', 'client', 'milestone'];

        foreach ($capability_types as $capability_type) {
            $capabilities[$capability_type] = [
                // Post type
                "edit_{$capability_type}",
                "read_{$capability_type}",
                "delete_{$capability_type}",
                "edit_{$capability_type}s",
                "edit_others_{$capability_type}s",
                "publish_{$capability_type}s",
                "read_private_{$capability_type}s",
                "delete_{$capability_type}s",
                "delete_private_{$capability_type}s",
                "delete_published_{$capability_type}s",
                "delete_others_{$capability_type}s",
                "edit_private_{$capability_type}s",
                "edit_published_{$capability_type}s",

                // Terms
                "manage_{$capability_type}_terms",
                "edit_{$capability_type}_terms",
                "delete_{$capability_type}_terms",
                "assign_{$capability_type}_terms",

                "delete_project_discussion",
                "edit_project_author",
                "project_owner_field",
                "project_title_field",
                "project_status_field",
                "project_client_field",
                "project_users_field",
                "project_start_date_field",
                "project_end_date_field",
                //                "project_description_field",
                "publish_project_milestones",
                "publish_project_bugs",
                "publish_project_discussion",
                "publish_project_files",
                "publish_project_tasks",
                "manage_upstream",

                "task_title_field",
                "task_assigned_to_field",
                "task_status_field",
                "task_progress_field",
                "task_milestone_field",
                "task_start_date_field",
                "task_end_date_field",
                "task_notes_field",

                "bug_title_field",
                "bug_assigned_to_field",
                "bug_severity_field",
                "bug_status_field",
                "bug_due_date_field",
                "bug_description_field",
                "bug_files_field",

                "milestone_milestone_field",
                "milestone_assigned_to_field",
                "milestone_start_date_field",
                "milestone_end_date_field",
                "milestone_notes_field",
            ];
        }

        return $capabilities;
    }

    /**
     * Gets the core post type capabilities
     *
     * @access public
     * @since  1.0.0
     * @return array $capabilities Core post type capabilities
     */
    public function get_upstream_user_caps()
    {
        $capabilities['project'] = [
            'edit_project',
            'read_project',
            'edit_projects',
            // 'edit_others_projects',
            // 'read_private_projects',
            // 'edit_private_projects',
            'edit_published_projects',

            // === TERMS ===
            'assign_project_terms',
            'manage_project_terms',
            //'edit_project_terms',
            //'delete_project_terms',

            /*
             * Individual project fields.
             * Giving the role access to these fields, means that
             * they can edit OTHER users tasks, bugs, milestones
             * but only the fields added to their capabilities.
             * And this will only work in the WP admin.
             */
            'project_title_field',
            'project_status_field',
            'project_owner_field',
            'project_client_field',
            'project_users_field',
            'project_start_date_field',
            'project_end_date_field',
            // "project_description_field",

            'edit_milestone',
            'read_milestone',
            'edit_milestones',
            'edit_others_milestones',
            'read_private_milestones',
            'edit_private_milestones',
            // individual milestone fields
            'milestone_milestone_field',
            'milestone_assigned_to_field',
            'milestone_start_date_field',
            'milestone_end_date_field',
            'milestone_notes_field',

            // individual task fields
            'task_title_field',
            'task_assigned_to_field',
            'task_status_field',
            'task_progress_field',
            'task_start_date_field',
            'task_end_date_field',
            'task_notes_field',
            'task_milestone_field',

            // individual bug fields
            'bug_title_field',
            'bug_assigned_to_field',
            'bug_description_field',
            'bug_status_field',
            'bug_severity_field',
            'bug_files_field',
            'bug_due_date_field',

            // Publish project items
            'publish_project_milestones', // enables the 'Add Milestone' button within project
            'publish_project_tasks', // enables the 'Add Task' button within project
            'publish_project_bugs', // enables the 'Add Bug' button within project
            'publish_project_files', // enables the 'Add Files' button within project
            'publish_project_discussion',
            'delete_project_discussion',

            //'delete_project_milestones',
            //'delete_project_tasks',
            //'delete_project_bugs',
            //'delete_project_files',

            //'sort_project_milestones',
            //'sort_project_tasks',
            //'sort_project_bugs',
            //'sort_project_files',

        ];

        $capabilities['client'] = [
            'edit_client',
            'read_client',
            'edit_clients',
            'edit_others_clients',
            'publish_clients',
            // 'read_private_clients',
            // 'edit_private_clients',
            'edit_published_clients',
        ];

        return $capabilities;
    }

    /**
     * Remove core post type capabilities (called on uninstall)
     *
     * @access public
     * @since  1.5.2
     * @return void
     */
    public function remove_caps()
    {
        global $wp_roles;

        if (class_exists('WP_Roles')) {
            if ( ! isset($wp_roles)) {
                $wp_roles = new WP_Roles();
            }
        }

        if (is_object($wp_roles)) {

            // Add the main post type capabilities
            $manager_caps = $this->get_upstream_manager_caps();
            $manager_role = get_role('upstream_manager');
            $admin_role   = get_role('administrator');

            foreach ($manager_caps as $post_type) {
                foreach ($post_type as $index => $cap) {
                    if ($manager_role) {
                        $manager_role->remove_cap($cap);
                    }
                    if ($admin_role) {
                        $admin_role->remove_cap($cap);
                    }
                }
            }

            // Add the main post type capabilities
            $user_caps = $this->get_upstream_user_caps();
            $user_role = get_role('upstream_user');
            foreach ($user_caps as $post_type) {
                foreach ($post_type as $index => $cap) {
                    if ($user_role) {
                        $user_role->remove_cap($cap);
                    }
                }
            }
        }
    }
}
