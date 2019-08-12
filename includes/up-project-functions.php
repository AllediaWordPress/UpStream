<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

function upstream_project_status($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_meta('status');

    return apply_filters('upstream_project_status', $result, $id);
}

function upstream_project_statuses_colors()
{
    $option = get_option('upstream_projects');
    $colors = wp_list_pluck($option['statuses'], 'color', 'id');

    return apply_filters('upstream_project_statuses_colors', $colors);
}

function upstream_get_all_project_statuses()
{
    $data = [];

    $rowset = get_option('upstream_projects');
    foreach ($rowset['statuses'] as $status) {
        $data[$status['id']] = $status;
    }

    return $data;
}

function upstream_get_open_project_status_ids()
{
    $all_statuses = upstream_get_all_project_statuses();
    $open_status_ids = [];

    foreach ($all_statuses as $key => $status) {
        if ($status['type'] == 'open')
            $open_status_ids[] = $status['id'];
    }

    return $open_status_ids;
}

function upstream_project_status_color($project_id = 0)
{
    $status = [
        'status' => '',
        'color'  => '#aaa',
    ];

    $projectStatusId = (string)upstream_project_status($project_id);
    if ( ! empty($projectStatusId)) {
        $rowset = get_option('upstream_projects');
        if ( ! empty($rowset)
             && ! empty($rowset['statuses'])
        ) {
            foreach ($rowset['statuses'] as $row) {
                if (isset($row['id'])
                    && $row['id'] === $projectStatusId
                ) {
                    $status['status'] = $row['name'];
                    $status['color']  = $row['color'];
                    break;
                }
            }
        }
    }

    return apply_filters('upstream_project_status_color', $status);
}

function upstream_project_status_type($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_project_status_type();

    return apply_filters('upstream_project_status_type', $result);
}

function upstream_project_progress($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_meta('progress');
    $result  = $result ? $result : '0';

    return apply_filters('upstream_project_progress', $result, $id);
}

function upstream_project_owner_id($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_meta('owner');

    return apply_filters('upstream_project_owner_id', $result, $id);
}

function upstream_project_owner_name($id = 0, $show_email = false)
{
    $project  = new UpStream_Project($id);
    $owner_id = $project->get_meta('owner');
    $result   = $owner_id ? upstream_users_name($owner_id, $show_email) : null;

    return apply_filters('upstream_project_owner_name', $result, $id, $show_email);
}

function upstream_project_client_id($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_meta('client');

    return apply_filters('upstream_project_client_id', $result, $id);
}

function upstream_project_client_name($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_client_name();

    return apply_filters('upstream_project_client_name', $result, $id);
}

function upstream_project_client_users($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = (array)$project->get_meta('client_users');
    $result  = ! empty($result) ? array_filter($result, 'is_numeric') : '';

    return apply_filters('upstream_project_client_users', $result, $id);
}

function upstream_project_members_ids($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_meta('members');

    return apply_filters('upstream_project_members_ids', $result, $id);
}

// only get WP users
function upstream_project_users($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_meta('members');
    $result  = isset($result) ? array_filter($result, 'is_numeric') : '';

    return apply_filters('upstream_project_users', $result, $id);
}

function upstream_project_start_date($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_meta('start');

    return apply_filters('upstream_project_start_date', $result, $id);
}

function upstream_project_end_date($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_meta('end');

    return apply_filters('upstream_project_end_date', $result, $id);
}


function upstream_project_files($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_meta('files');

    return apply_filters('upstream_project_files', $result, $id);
}

function upstream_project_description($projectId = 0)
{
    $project = new UpStream_Project((int)$projectId);
    $result  = $project->get_meta('description');

    return apply_filters('upstream_project_description', $result, $projectId);
}

/* ------------ MILESTONES -------------- */

function upstream_project_milestones($id = 0)
{
    if (empty($id)) {
        $id = get_the_ID();
    }

    $result = \UpStream\Milestones::getInstance()->getMilestonesAsRowset($id);

    return apply_filters('upstream_project_milestones', $result, $id);
}

function upstream_project_milestone_by_id($id = 0, $milestone_id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_item_by_id($milestone_id, 'milestones');

    return apply_filters('upstream_project_milestone_by_id', $result, $id, $milestone_id);
}

/**
 * @return mixed|void
 * @deprecated Each milestone instance returns its color.
 */
function upstream_project_milestone_colors()
{
    return [];
}

/* ------------ TASKS -------------- */

function upstream_project_tasks($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_meta('tasks');

    return apply_filters('upstream_project_tasks', $result, $id);
}

function upstream_project_task_by_id($id = 0, $task_id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_item_by_id($task_id, 'tasks');

    return apply_filters('upstream_project_task_by_id', $result, $id, $task_id);
}

function upstream_project_tasks_counts($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_statuses_counts('tasks');

    return apply_filters('upstream_project_tasks_statuses_counts', $result, $id);
}

function upstream_project_task_statuses_colors()
{
    $option = get_option('upstream_tasks');
    $colors = wp_list_pluck($option['statuses'], 'color', 'id');

    return apply_filters('upstream_project_tasks_statuses_colors', $colors);
}

function upstream_project_task_status_color($id = 0, $item_id)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_item_colors($item_id, 'tasks', 'status');

    return apply_filters('upstream_project_task_status_color', $result);
}

/* ------------ BUGS -------------- */

function upstream_project_bugs($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_meta('bugs');

    return apply_filters('upstream_project_bugs', $result, $id);
}

function upstream_project_bug_by_id($id = 0, $bug_id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_item_by_id($bug_id, 'bugs');

    return apply_filters('upstream_project_bug_by_id', $result, $id, $bug_id);
}

function upstream_project_bugs_counts($id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_statuses_counts('bugs');

    return apply_filters('upstream_project_bugs_statuses_counts', $result, $id);
}

function upstream_project_bug_statuses_colors()
{
    $option = get_option('upstream_bugs');
    $colors = wp_list_pluck($option['statuses'], 'color', 'id');

    return apply_filters('upstream_project_bugs_statuses_colors', $colors);
}

function upstream_project_bug_severity_colors()
{
    $option = get_option('upstream_bugs');
    $colors = wp_list_pluck($option['severities'], 'color', 'id');

    return apply_filters('upstream_project_bugs_severity_colors', $colors);
}

function upstream_project_bug_status_color($id = 0, $item_id)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_item_colors($item_id, 'bugs', 'status');

    return apply_filters('upstream_project_bug_status_color', $result);
}


function upstream_project_item_by_id($id = 0, $item_id = 0)
{
    $project = new UpStream_Project($id);
    $result  = $project->get_item_by_id($item_id, 'milestones');
    if ( ! $result) {
        $result = $project->get_item_by_id($item_id, 'tasks');
    }
    if ( ! $result) {
        $result = $project->get_item_by_id($item_id, 'bugs');
    }
    if ( ! $result) {
        $result = $project->get_item_by_id($item_id, 'files');
    }
    if ( ! $result) {
        $result = $project->get_item_by_id($item_id, 'discussion');
    }

    return apply_filters('upstream_project_item_by_id', $result, $id, $item_id);
}

/* ------------ COUNTS -------------- */


/**
 * Get the count of items for a type.
 *
 * @param type | string type of item such as bug, task etc
 * @param id | int id of the project you want the count for
 */
function upstream_count_total($type, $id = 0)
{
    if ( ! $id && is_admin()) {
        $id = isset($_GET['post']) ? $_GET['post'] : 'n/a';
    }

    $count = new Upstream_Counts($id);

    return $count->total($type);
}

/**
 * Get the count of OPEN items for a type.
 *
 * @param type | string type of item such as bug, task etc
 * @param id | int id of the project you want the count for
 */
function upstream_count_total_open($type, $id = 0)
{
    $count = new Upstream_Counts($id);

    return $count->total_open($type);
}

/**
 * Get the count of items for a type that is assigned to current user.
 *
 * @param type | string type of item such as bug, task etc
 * @param id | int id of the project you want the count for
 */
function upstream_count_assigned_to($type, $id = 0)
{
    $count = new Upstream_Counts($id);

    return $count->assigned_to($type);
}

/**
 * Get the count of OPEN items for a type that is assigned to current user.
 *
 * @param type | string type of item such as bug, task etc
 * @param id | int id of the project you want the count for
 */
function upstream_count_assigned_to_open($type, $id = 0)
{
    $count = new Upstream_Counts($id);

    return $count->assigned_to_open($type);
}

/**
 * Retrieve details from a given project.
 *
 * @param int $project_id The project ID.
 *
 * @return  object
 * @since   1.12.0
 *
 */
function getUpStreamProjectDetailsById($project_id)
{
    $post = get_post($project_id);
    if ($post instanceof \WP_Post) {
        global $wpdb;

        $project              = new stdClass();
        $project->id          = (int)$project_id;
        $project->title       = $post->post_title;
        $project->description = "";
        $project->progress    = 0;
        $project->status      = null;
        $project->client_id   = 0;
        $project->clientName  = "";
        $project->owner_id    = 0;
        $project->ownerName   = "";
        $project->dateStart   = 0;
        $project->dateEnd     = 0;
        $project->members     = [];
        $project->clientUsers = [];

        $metas = $wpdb->get_results(sprintf(
            '
            SELECT `meta_key`, `meta_value`
            FROM `%s`
            WHERE `post_id` = "%s"
              AND `meta_key` LIKE "_upstream_project_%s"',
            $wpdb->prefix . 'postmeta',
            $project->id,
            "%"
        ));

        foreach ($metas as $meta) {
            if ($meta->meta_key === '_upstream_project_description') {
                $project->description = $meta->meta_value;
            } elseif ($meta->meta_key === '_upstream_project_progress') {
                $project->progress = (int)$meta->meta_value;
            } elseif ($meta->meta_key === '_upstream_project_status') {
                $project->status = $meta->meta_value;
            } elseif ($meta->meta_key === '_upstream_project_client') {
                $project->client_id = (int)$meta->meta_value;
            } elseif ($meta->meta_key === '_upstream_project_owner') {
                $project->owner_id = (int)$meta->meta_value;
            } elseif ($meta->meta_key === '_upstream_project_start') {
                $project->dateStart = (int)$meta->meta_value;
            } elseif ($meta->meta_key === '_upstream_project_end') {
                $project->dateEnd = (int)$meta->meta_value;
            } elseif ($meta->meta_key === '_upstream_project_members') {
                $project->members = (array)maybe_unserialize($meta->meta_value);
            } elseif ($meta->meta_key === '_upstream_project_client_users') {
                $project->clientUsers = (array)maybe_unserialize($meta->meta_value);
            }
        }

        $usersRowset = (array)get_users([
            'fields' => ['ID', 'display_name'],
        ]);

        $users = [];
        foreach ($usersRowset as $user) {
            $users[(int)$user->ID] = (object)[
                'id'   => (int)$user->ID,
                'name' => $user->display_name,
            ];
        }

        if ($project->client_id > 0) {
            $client = get_post($project->client_id);
            if ($client instanceof \WP_Post) {
                if ( ! empty($client->post_title)) {
                    $project->clientName = $client->post_title;
                }
            }
        }

        if ($project->owner_id > 0 && isset($users[$project->owner_id])) {
            $project->ownerName = $users[$project->owner_id]->name;
        }

        if (count($project->members) > 0) {
            foreach ($project->members as $memberIndex => $member_id) {
                $member_id = (int)$member_id;
                if ($member_id > 0 && isset($users[$member_id])) {
                    $project->members[$memberIndex] = $users[$member_id];
                }
            }
        }

        if (count($project->clientUsers) > 0) {
            foreach ($project->clientUsers as $clientUserIndex => $clientUser_id) {
                $clientUser_id = (int)$clientUser_id;
                if ($clientUser_id > 0 && isset($users[$clientUser_id])) {
                    $project->clientUsers[$clientUserIndex] = $users[$clientUser_id];
                }
            }
        }

        return $project;
    }

    return false;
}

function countItemsForUserOnProject($itemType, $user_id, $project_id)
{
    $user_id = (int)$user_id;
    if ( ! in_array($itemType, ['milestones', 'tasks', 'bugs'])) {
        return null;
    }

    $count = 0;

    $metas = (array)get_post_meta((int)$project_id, '_upstream_project_' . $itemType);
    $metas = count($metas) > 0 ? (array)$metas[0] : [];

    if (isset($metas[0])) {
        $metas = (array)$metas[0];
    }

    if (is_array($metas) && count($metas) > 0) {
        foreach ($metas as $meta) {
            if (isset($meta['assigned_to'])) {
                $assignedTo = $meta['assigned_to'];

                if (
                    (is_array($assignedTo) && in_array($user_id, $assignedTo))
                    && ((int)$meta['assigned_to'] === $user_id)
                ) {
                    $count++;
                }
            }
        }
    }

    return $count;
}

/**
 * Retrieve the number of approved comments within a given project.
 *
 * @param int $project_id The project ID.
 *
 * @return  int
 * @since   1.13.0
 *
 */
function getProjectCommentsCount($project_id)
{
    if ( ! is_numeric($project_id) || $project_id < 0) {
        return;
    }

    $commentsCount = get_comments([
        'post_id' => $project_id,
        'count'   => true,
        'status'  => "approve",
    ]);

    return (int)$commentsCount;
}

/**
 * @return array
 */
function upstream_user_projects()
{
    $projectsList = [];

    $currentUser = (object)upstream_user_data(@$_SESSION['upstream']['user_id']);

    if (isset($currentUser->projects)) {
        if (is_array($currentUser->projects) && count($currentUser->projects) > 0) {
            $archiveClosedItems = upstream_archive_closed_items();
            $areClientsEnabled  = ! is_clients_disabled();

            foreach ($currentUser->projects as $project_id => $project) {
                $data = (object)[
                    'id'                 => $project_id,
                    'title'              => $project->post_title,
                    'slug'               => $project->post_name,
                    'status'             => $project->post_status,
                    'permalink'          => get_permalink($project_id),
                    'startDateTimestamp' => (int)upstream_project_start_date($project_id),
                    'endDateTimestamp'   => (int)upstream_project_end_date($project_id),
                    'progress'           => (float)upstream_project_progress($project_id),
                    'status'             => (string)upstream_project_status($project_id),
                    'clientName'         => null,
                    'categories'         => [],
                    'features'           => [
                        '',
                    ],
                ];

                // If should archive closed items, we filter the rowset.
                if ($archiveClosedItems) {

                    $openStatuses = upstream_get_open_project_status_ids();
                    if ( ! empty($data->status) && ! in_array($data->status, $openStatuses)) {
                        continue;
                    }
                }

                $data->startDate = (string)upstream_format_date($data->startDateTimestamp);
                $data->endDate   = (string)upstream_format_date($data->endDateTimestamp);

                if ($areClientsEnabled) {
                    $data->clientName = trim((string)upstream_project_client_name($project_id));
                }

                $statuses = upstream_get_all_project_statuses();

                if (isset($statuses[$data->status])) {
                    $data->status = $statuses[$data->status];
                }

                $data->timeframe = $data->startDate;
                if ( ! empty($data->endDate)) {
                    if ( ! empty($data->timeframe)) {
                        $data->timeframe .= ' - ';
                    } else {
                        $data->timeframe = '<i>' . __('Ends at', 'upstream') . '</i>';
                    }

                    $data->timeframe .= $data->endDate;
                }

                $categories = (array)wp_get_object_terms($data->id, 'project_category');
                if (count($categories) > 0) {
                    foreach ($categories as $category) {
                        $data->categories[$category->term_id] = $category->name;
                    }
                }

                $projectsList[$project_id] = $data;
            }

            unset($project, $project_id);
        }

        unset($currentUser->projects);
    }

    unset($currentUser);

    return $projectsList;
}
