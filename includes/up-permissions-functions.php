<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


/**
 * Permission checks for the frontend are always run through here.
 * Return true if they are allowed.
 *
 * @deprecated Use current_user_can
 */
function upstream_permissions($capability = null, $item_id = null)
{
    // set the return variable that can be overwritten after all checks
    // set the return variable that can be overwritten after all checks
    $return       = false;
    $current_user = upstream_current_user_id();

    // these guys can do whatever they want
    if (upstream_project_owner_id() == upstream_current_user_id() ||
        current_user_can('upstream_manager') ||
        current_user_can('administrator')) {
        $return = true;
    }

    // if they are simply a project member or a client user, this will give them at least READ access to the project
    // and stops them being redirected to the projects archive page
    if ($capability == 'view_project') {
        $members = upstream_project_members_ids();
        if (is_array($members) && in_array($current_user, $members)) {
            $return = true;
        }

        // client users
        $client_users = upstream_project_client_users();
        if (is_array($client_users) && in_array($current_user, $client_users)) {
            $return = true;
        }
    }


    // if capability is set and they have the capability
    if (isset($capability) && ! empty($capability)) {

        // for WP user - get standard capabilities
        if (is_int($current_user) && current_user_can($capability)) {
            $return = true;
        }

        // for client users
        // get their capabilities, stored within the meta of the Client post type
        if ( ! is_int($current_user)) {
            $client_users = get_post_meta(
                upstream_project_client_id(upstream_post_id()),
                '_upstream_client_users',
                true
            );
            if (is_array($client_users) && ! empty($client_users)) {
                foreach ($client_users as $index => $user) {
                    if ($user['id'] == $current_user) {
                        if (isset($user['capability']) && in_array($capability, $user['capability'])) {
                            $return = true;
                        }
                    }
                }
            }
        }
    }

    // if we have an individual item and they are the creator or have been assigned this item
    // used for the 'Actions' column to allow editing/deleting buttons
    if (isset($item_id)) {
        $item        = upstream_project_item_by_id(upstream_post_id(), $item_id);
        $assigned_to = isset($item['assigned_to']) ? ( array )$item['assigned_to'] : [];
        $created_by  = isset($item['created_by']) ? $item['created_by'] : null;
        if (in_array($current_user, $assigned_to) || $created_by == $current_user) {
            $return = true;
        }
    }


    // blocks all fields (except for status) from being edited/deleted
    // used if the project status is closed
    if ($capability != 'project_status_field' && upstream_project_status_type() == 'closed') {
        $return = false;
    }


    return apply_filters('upstream_permissions', $return);
}


/* ======================================================================================
                    ADMIN
   ====================================================================================== */


/**
 * Permission checks are always run through here.
 * Return true if they are allowed.
 *
 * @param  string $capability
 *
 * @return bool
 */
function upstream_admin_permissions($capability = null)
{

    /*
     * Set the return variable that can be overwritten after all checks
     */
    $return = false;

    /*
     * These guys can do whatever they want
     */
    if (upstream_project_owner_id() == upstream_current_user_id() ||
        current_user_can('upstream_manager') ||
        current_user_can('administrator')) {
        $return = true;
    }

    /*
     * If the user has the capability
     */
    if (isset($capability) && ! empty($capability)) {
        if (current_user_can($capability)) {
            $return = true;
        }
    }

    /*
     * If project status is closed, block all fields (except for status) from being edited/deleted
     */
    if ((isset($capability) && $capability != 'project_status_field') &&
        upstream_project_status_type() == 'closed') {
        $return = false;
    }

    return apply_filters('upstream_admin_permissions', $return);
}

/**
 * Retrieve all Client Users permissions available.
 *
 * @since   1.11.0
 *
 * @return  array
 */
function upstream_get_client_users_permissions()
{
    $permissions = [];

    $permissionsList = (array)apply_filters('upstream:users.permissions', []);
    foreach ($permissionsList as $permission) {
        $permissions[$permission['key']] = $permission;
    }

    return $permissions;
}

/**
 * Check if a given user can access a given project.
 *
 * @since   1.12.2
 *
 * @param   numeric/WP_User     $user_id    The user to be checked against the project.
 * @param   numeric $project_id The project to be checked.
 *
 * @return  bool
 */
function upstream_user_can_access_project($user_id, $project_id)
{
    $project_id = is_numeric($project_id) ? (int)$project_id : 0;
    if ($project_id <= 0) {
        return false;
    }

    $user = $user_id instanceof \WP_User ? $user_id : new \WP_User($user_id);
    if ($user->ID === 0) {
        return false;
    }

    $userIsAdmin = count(array_intersect((array)$user->roles, ['administrator', 'upstream_manager'])) > 0;
    if ($userIsAdmin) {
        return true;
    }

    $userCanAccessProject = false;

    if (in_array('upstream_client_user', $user->roles)) {
        // Check if client user is allowed on this project.
        $meta = (array)get_post_meta($project_id, '_upstream_project_client_users');
        $meta = ! empty($meta) ? (array)$meta[0] : [];
        $meta = empty($meta) ? [] : $meta;

        if (in_array($user->ID, $meta)) {
            $userCanAccessProject = true;
        }
    }

    if ( ! $userCanAccessProject && user_can($user, 'edit_published_projects')) {
        // Check if user is a member of the project.
        $projectMembers = upstream_project_members_ids($project_id);
        if (is_array($projectMembers) && in_array($user->ID, $projectMembers)) {
            $userCanAccessProject = true;
        } elseif ($user->ID == (int)$projectMembers) {
            $userCanAccessProject = true;
        }
    }

    return $userCanAccessProject;
}
