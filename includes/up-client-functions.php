<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


function upstream_get_client_id()
{
    $client_id = (int)upstream_project_client_id();

    if ($client_id === 0) {
        $user_id   = upstream_current_user_id();
        $client_id = (int)upstream_get_users_client_id($user_id);
    }

    return $client_id > 0 ? $client_id : null;
}

function upstream_client_logo($client_id = 0)
{
    $logoURL = "";

    if ((int)$client_id === 0) {
        $client_id = upstream_get_client_id();
    }

    if ($client_id > 0) {
        global $wpdb, $table_prefix;

        $logoURL = $wpdb->get_var(sprintf(
            '
            SELECT `meta_value`
            FROM `%s`
            WHERE `post_id` = "%s"
                AND `meta_key` = "_upstream_client_logo"',
            $table_prefix . 'postmeta',
            $client_id
        ));
    }

    return apply_filters('upstream_client_logo', $logoURL, $client_id);
}

/**
 * Save post metadata when a post is saved.
 * Mainly used to update user ids
 *
 * @param int  $post_id The post ID.
 * @param post $post    The post object.
 * @param bool $update  Whether this is an existing post being updated or not.
 */
function upstream_update_client_meta_values($post_id, $post, $update)
{
    $slug = 'client';

    // If this isn't a 'client' post, don't update it.
    if ($slug != $post->post_type) {
        return;
    }

    // update the overall progress of the project
    if (isset($_POST['_upstream_client_users'])) :

        $users = $_POST['_upstream_client_users'];

        // update the user with a unique id if one is not set
        $i = 0;
        if ($users) :
            foreach ($users as $user) {
                if ( ! isset($user['id']) || empty($user['id']) || $user['id'] == '') {
                    $users[$i]['id'] = upstream_admin_set_unique_id();
                }
                $i++;
            }
        endif;

        update_post_meta($post_id, '_upstream_client_users', $users);

    endif;
}

add_action('save_post', 'upstream_update_client_meta_values', 99999, 3);

/**
 * Retrieve all Client Users associated with a given client.
 *
 * @since   1.11.0
 *
 * @param   int $client_id The reference id.
 *
 * @return  array|bool  Array in case of success or false in case the client_id is invalid.
 */
function upstream_get_client_users($client_id)
{
    $client_id = (int)$client_id;
    if ($client_id <= 0) {
        return false;
    }

    $usersList       = [];
    $clientUsersList = array_filter((array)get_post_meta($client_id, '_upstream_new_client_users', true));
    if (count($clientUsersList) > 0) {
        $usersIdsList = [];
        foreach ($clientUsersList as $clientUser) {
            if (isset($clientUser['user_id'])) {
                $usersIdsList[] = (int)$clientUser['user_id'];
            }
        }

        $rowset = (array)get_users([
            'fields'  => ['ID', 'display_name'],
            'include' => $usersIdsList,
        ]);

        foreach ($rowset as $row) {
            $usersList[(int)$row->ID] = [
                'id'   => (int)$row->ID,
                'name' => $row->display_name,
            ];
        }
    }

    return $usersList;
}

/**
 * Check if a given user is a Client User associated with a given client.
 *
 * @since   1.11.0
 *
 * @param   int $client_user_id The client user id.
 * @param   int $client_id      The client id.
 *
 * @return  bool|null   Bool or NULL in case the user is invalid.
 */
function upstream_do_client_user_belongs_to_client($client_user_id, $client_id)
{
    $client_user_id = (int)$client_user_id;
    if ($client_user_id <= 0) {
        return null;
    }

    $clientUsers = (array)upstream_get_client_users($client_id);

    $clientUserBelongsToClient = isset($clientUsers[$client_user_id]);

    return $clientUserBelongsToClient;
}

/**
 * Retrieve all client user permissions.
 *
 * @since   1.11.0
 *
 * @param   int $client_user_id The client user id.
 *
 * @return  array|bool  A permissions array or false if user doesn't exist.
 */
function upstream_get_client_user_permissions($client_user_id)
{
    $clientUser = new \WP_User((int)$client_user_id);
    if ($clientUser->ID === 0) {
        return false;
    }

    $permissions = upstream_get_client_users_permissions();
    foreach ($permissions as $permissionIndex => $permission) {
        if (isset($clientUser->caps[$permission['key']])) {
            $permission['value'] = $clientUser->caps[$permission['key']];
        } elseif (isset($clientUser->allcaps[$permission['key']])) {
            $permission['value'] = $clientUser->allcaps[$permission['key']];
        }

        $permissions[$permissionIndex] = $permission;
    }

    return $permissions;
}
