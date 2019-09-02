<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * UpStream_Project_Activity Class
 *
 * @since 1.0.0
 */
class UpStream_Project_Activity
{
    use \UpStream\Traits\Singleton;

    /**
     * The project ID
     *
     * @since 1.0.0
     */
    public $ID = 0;

    public $posted = null;

    /**
     * Get things going
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->hooks();
    }

    public function hooks()
    {
        if (isset($_POST['action']) && (isset($_POST['post_type']) && $_POST['post_type'] == 'project')) {
            add_action('wp_insert_post_data', [$this, 'init_update'], 99, 2);
        }
        if (isset($_POST['upstream-nonce']) && (isset($_POST['post_id']))) { // posted in frontend
            $this->init_update(null, null);
        }
    }


    /**
     * L
     */
    public function init_update($data, $postarr)
    {
        //working out frontend or backend posting
        $this->posted = $postarr ? $postarr : $_POST;
        $this->ID     = isset($this->posted['post_id']) ? $this->posted['post_id'] : $this->posted['ID'];


        // do some (really dodgy) quick adjustments if posted from frontend
        if (isset($this->posted['upstream-nonce']) || isset($this->posted['upstream_security'])) {
            $posted = $this->posted;
            $group  = '_upstream_project_' . $posted['type'];

            if (isset($posted['editing'])) {
                $posted['id'] = $posted['editing'];
            }

            // reset our posted variable
            $this->posted            = [];
            $this->posted[$group][0] = $posted;

            if (isset($posted['action']) && $posted['action'] == 'upstream_frontend_delete_item') {
                $this->posted['frontend'] = 'delete';
            }

            // remove keys not required
            $remove = [
                'upstream-nonce',
                'upstream_security',
                'action',
                '_wp_http_referer',
                'post_id',
                'type',
                'editing',
                'row',
                'upstream-files-nonce',
            ];
            foreach ($remove as $key) {
                unset($this->posted[$group][0][$key]);
            }
        }

        if (isset($postarr)) {
            $this->posted['admin'] = true;
        }

        //If this is an auto draft
        if (isset($this->posted['post_status']) && $this->posted['post_status'] == 'auto-draft') {
            return $data;
        }

        // ignore quick edit
        if (isset($this->posted['action']) && $this->posted['action'] == 'inline-save') {
            return $data;
        }

        $this->update_project();

        return $data;
    }


    /**
     * Update a project
     */
    public function update_project()
    {
        $activity = [];
        $time     = current_time('timestamp');
        $user_id  = upstream_current_user_id();


        // start to loop through each POSTED item
        foreach ($this->posted as $key => $new_value) {

            // skip some of wordpress standard fields that we don't need
            if ($this->match($key, ['nonce', 'action', 'refer', 'hidden'])) {
                continue;
            }

            /*
             * first check for UpStream fields
             */
            if ($this->match($key, '_upstream_project')) {


                // get the old value so we can compare
                $old_value = $this->get_meta($key);

                /*
                 * check the simple string fields first
                 */
                if ( ! is_array($new_value) || $key == '_upstream_project_client_users') {

                    // handle date formatting differences first
                    if ($this->match($key, ['project_start', 'project_end', 'date'])) {
                        //$old_value = upstream_format_date( $old_value );
                        $new_value = upstream_timestamp_from_date($new_value);
                    }

                    // if we are adding a new item
                    if ( ! $old_value && ! empty($new_value)) {
                        $activity['single'][$key]['add'] = $new_value;
                        continue;
                    }

                    // add the activity to our array
                    if ($old_value != $new_value) {
                        $activity['single'][$key]['from'] = $old_value;
                        $activity['single'][$key]['to']   = $new_value;
                    }
                }

                /*
                 * check the array fields
                 */
                if (is_array($new_value) && $key != '_upstream_project_client_users') {

                    // deleted from frontend
                    if (isset($this->posted['frontend']) && $this->posted['frontend'] == 'delete') {
                        if ($new_value) {
                            foreach ($old_value as $old_old => $old_item) {
                                // if the old id is not in the new items, we have deleted it
                                if (isset($new_value[0]['id']) && $new_value[0]['id'] == $old_item['id']) {
                                    $activity['group'][$key]['remove'][] = $old_item;
                                }
                            }
                        }
                    }

                    // deleted from admin
                    if (isset($this->posted['admin']) && $this->posted['admin'] == true) {
                        if ($old_value) {
                            foreach ($old_value as $old_index => $old_item) {
                                // if the old id is not in the new items, we have deleted it
                                if (isset($old_item['id']) && ! $this->in_array_r($old_item['id'], $new_value)) {
                                    $activity['group'][$key]['remove'][] = $old_item;
                                }
                            }
                        }
                    }

                    // loop through each new item
                    foreach ($new_value as $new_index => $new_item) {

                        // see if our new item matches any existing
                        $item_id       = isset($new_item['id']) ? $new_item['id'] : null;
                        $existing_item = upstream_project_item_by_id($this->ID, $item_id);

                        // if we are adding a new item
                        if ( ! $existing_item) {

                            // ignore if all fields are empty
                            if (array_filter($new_item)) {
                                $activity['group'][$key]['add'][] = $new_item;
                            }
                        }

                        if ($existing_item) {

                            // loop through each new item field
                            foreach ($new_item as $new_item_field_key => $new_item_field_val) {

                                // check for date fields
                                if ($this->match($new_item_field_key, ['date'])) {
                                    // convert date to timestamp
                                    $new_item_field_val = upstream_timestamp_from_date($new_item_field_val);
                                }

                                // we've added a new field
                                // existing item is NOT set
                                if (
                                    ! isset($existing_item[$new_item_field_key])
                                    && ! empty($new_item_field_val)) {
                                    $activity['group'][$key][$existing_item['id']][$new_item_field_key]['add'] = $new_item_field_val;
                                    continue;
                                }

                                // we've removed a field
                                // existing item is set
                                // new item is NOT set
                                if (
                                    (isset($existing_item[$new_item_field_key]) && ! isset($existing_item[$new_item_field_key]))
                                    && ! empty($existing_item[$new_item_field_key])) {
                                    $activity['group'][$key][$existing_item['id']][$new_item_field_key]['remove'] = $existing_item[$new_item_field_key];
                                    continue;
                                }

                                // we've edited a field
                                if (isset($existing_item[$new_item_field_key]) && $existing_item[$new_item_field_key] != $new_item_field_val) {
                                    $activity['group'][$key][$existing_item['id']][$new_item_field_key]['from'] = $existing_item[$new_item_field_key];
                                    $activity['group'][$key][$existing_item['id']][$new_item_field_key]['to']   = $new_item_field_val;
                                }
                            }
                        }
                    }
                } // end is_array check
            }
        }

        if (empty($activity)) {
            return;
        }

        $data[$time]['fields']  = $activity;
        $data[$time]['user_id'] = $user_id;

        $existing = get_post_meta($this->ID, '_upstream_project_activity', true);
        if ($existing) {
            $update = ($existing + $data);
        } else {
            $update = $data;
        }

        $updated = update_post_meta($this->ID, '_upstream_project_activity', $update);
    }

    /**
     * Helper function to find matching strings
     * $needle can be a string or an array with multiple strings
     */
    public function match($haystack, $needle)
    {
        if ( ! $needle) {
            return;
        }

        // push single string into array for simplicity
        if ( ! is_array($needle)) {
            $needle = [$needle];
        }

        foreach ($needle as $string) {
            if (strpos($haystack, $string) !== false) {
                return true;
            }
        }
    }

    /**
     * Get a meta value
     *
     * @since 1.0.0
     *
     * @param string $meta the meta field (without prefix)
     *
     * @return mixed
     */
    public function get_meta($meta)
    {

        // to allow frontend use
        if (strpos($meta, '_upstream_project_') !== false) {
            $meta = str_replace('_upstream_project_', '', $meta);
        }

        $result = get_post_meta($this->ID, '_upstream_project_' . $meta, true);
        if ( ! $result) {
            $result = null;
        }

        return $result;
    }

    public function in_array_r($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r(
                        $needle,
                        $item,
                        $strict
                    ))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get activity
     */
    public function get_activity($post_id)
    {

        // set the post id
        $this->ID = $post_id;

        // make the field names readable
        $find    = ['_', 'upstream'];
        $replace = [' ', ''];

        // get the activity data
        $activity = $this->get_meta('_upstream_project_activity');

        if ( ! $activity) {
            return;
        }

        $activity = array_reverse($activity, true);

        $activity = array_slice($activity, 0, $this->number_of_items(), true);

        // loop through each timestamp
        foreach ($activity as $time => $update) {

            // get the day and time there was some activity
            echo '<div class="activity-item">';
            echo '<h4>' . esc_html(upstream_format_date($time) . ' ' . upstream_format_time($time)) . '</h4>';
            echo '<span class="user">' . esc_html(upstream_users_name($update['user_id'])) . ' (' . upstream_user_item(
                    $update['user_id'],
                    'role'
                ) . ')</span>';

            /*
             * single field
             */
            if (isset($update['fields']['single'])) {
                foreach ($update['fields']['single'] as $field_id => $data) {
                    $single_name = ucwords(str_replace($find, $replace, $field_id));

                    if (isset($data['add'])) {
                        $the_val       = $this->format_fields($field_id, $data['add']);
                        $single_output = sprintf(__('New: %s', 'upstream'), $the_val);
                    }

                    if (isset($data['from'])) {
                        $from          = $this->format_fields($field_id, $data['from']);
                        $to            = $this->format_fields($field_id, $data['to']);
                        $single_output = sprintf(__('Edit: %s to %s', 'upstream'), $from, $to);
                    }
                }

                echo '<span class="item-name">' . esc_html($single_name) . '</span>';
                echo wp_kses_post($single_output);
            }

            /*
             * group item
             *
             */
            if (isset($update['fields']['group'])) {
                foreach ($update['fields']['group'] as $group_id => $data) {
                    $group_name = ucwords(str_replace($find, $replace, $group_id));
                    echo '<span class="item-name">' . esc_html($group_name) . '</span>';
                    foreach ($data as $item_id => $fields) {

                        /*
                         * deleted an item
                         */
                        if ($item_id == 'remove') {
                            $item_removed = '';
                            foreach ($fields as $key => $item) {
                                // skip empty files
                                if ((isset($item['file_id']) && $item['file_id'] == '0') && (isset($item['title']) && empty($item['title']))) {
                                    $group_name = '';
                                    continue;
                                }

                                if ($group_id === '_upstream_project_milestones') {
                                    $title = $item['milestone'];
                                } else {
                                    $title = $item['title'];
                                }

                                $item_removed .= '<span class="item">' . sprintf(
                                        __('Deleted: %s', 'upstream'),
                                        '<span class="highlight">' . $title . '</span>'
                                    ) . '</span>';
                            }

                            $group_output = $item_removed;
                            echo wp_kses_post($group_output);
                        }

                        /*
                         * add an item
                         */
                        if ($item_id == 'add') {
                            $item_added = '';
                            foreach ($fields as $key => $item) {
                                // skip empty files
                                if ((isset($item['file_id']) && $item['file_id'] == '0') && (isset($item['title']) && empty($item['title']))) {
                                    $group_name = '';
                                    continue;
                                }

                                if ($group_id === '_upstream_project_milestones') {
                                    if (isset($item['milestone']))
                                        $title = $item['milestone'];
                                    else if (isset($item['data']['milestone']))
                                        $title = $item['data']['milestone'];
                                } else {
                                    if (isset($item['title']))
                                        $title = $item['title'];
                                    else if (isset($item['data']['title']))
                                        $title = $item['data']['title'];
                                }

                                $item_added .= '<span class="item">' . sprintf(
                                        __('New Item: %s', 'upstream'),
                                        '<span class="highlight">' . $title . '</span>'
                                    ) . '</span>';
                            }

                            $group_output = $item_added;
                            echo wp_kses_post($group_output);
                        }

                        /*
                         * edit an item
                         */
                        if (strlen($item_id) > 5) {
                            foreach ($fields as $field_id => $field_data) {
                                $field_name   = ucwords(str_replace($find, $replace, $field_id));
                                $field_output = '';
                                if (isset($field_data['add'])) {
                                    $item = upstream_project_item_by_id($this->ID, $item_id);

                                    $the_val      = $this->format_fields($field_id, $field_data['add']);
                                    $field_output .= '<span class="item">' . sprintf(
                                            __(
                                                'New: %s - %s on %s',
                                                'upstream'
                                            ),
                                            $field_name,
                                            (is_array($the_val) ? json_encode($the_val) : $the_val),
                                            '<span class="highlight">' . $item['title'] . '</span>'
                                        ) . '</span>';
                                }

                                if (isset($field_data['from'])) {
                                    $item = upstream_project_item_by_id($this->ID, $item_id);
                                    $from = $this->format_fields($field_id, $field_data['from']);
                                    $to   = $this->format_fields($field_id, $field_data['to']);

                                    $field_output .= '<span class="item">' . sprintf(
                                            __(
                                                'Edit: %s from %s to %s on %s',
                                                'upstream'
                                            ),
                                            $field_name,
                                            is_array($from) ? count($from) : $from,
                                            is_array($to) ? count($to) : $to,
                                            '<span class="highlight">' . $item['title'] . '</span>'
                                        ) . '</span>';
                                }

                                $group_output = $field_output;
                                echo wp_kses_post($group_output);
                            }
                        }
                    }
                }
            }


            echo '</div>';
        } // end items
    }

    /**
     * @param int    $projectId
     * @param string $metaName
     * @param        $action
     * @param mixed  $item
     */
    function add_activity($projectId, $metaName, $action, $item)
    {
        // Update Project activity.
        $activity = (array)get_post_meta($projectId, '_upstream_project_activity', true);

        $log = [
            'fields'  => [
                'group' => [
                    $metaName => [
                        $action => [$item],
                    ],
                ],
            ],
            'user_id' => get_current_user_id(),
        ];

        $now            = time();
        $activity[$now] = $log;

        update_post_meta($projectId, '_upstream_project_activity', $activity);
    }

    /**
     * Get activity
     */
    public function number_of_items()
    {
        $number = isset($_GET['activity_items']) ? $_GET['activity_items'] : 5;
        $number = $number == 'all' ? 99999999 : $number;

        return (int)$number;
    }

    public function format_fields($field_id, $val)
    {
        $field   = str_replace('_upstream_project_', '', $field_id);
        $the_val = '';

        if (strpos($field, 'date') !== false) {
            $field = 'date';
        }

        switch ($field) {
            case 'client_users':
                $prefix = $users = '';
                foreach ($val as $index => $user_id) {
                    $users  .= $prefix . '' . upstream_users_name($user_id) . '';
                    $prefix = '& ';
                }
                $the_val = $users;
                break;

            case 'client':
                $the_val = get_the_title($val);
                break;

            case 'start':
            case 'end':
            case 'date':
                $the_val = upstream_format_date($val);
                break;

            case 'assigned_to':
            case 'owner':
                if ( ! is_array($val)) {
                    $val = (array)$val;
                }

                $val     = array_unique(array_filter($val));
                $the_val = [];
                foreach ($val as $user_id) {
                    $user_id = (int)$user_id;
                    if ($user_id > 0) {
                        $the_val[] = upstream_users_name($user_id);
                    }
                }

                $the_val = implode(', ', $the_val);

                break;

            case 'milestone':
                $item    = upstream_project_item_by_id($this->ID, $val);
                $the_val = isset($item['title']) ? $item['title'] : $item['milestone'];
                break;

            default:
                $the_val = $val;
                break;
        }

        $the_val = empty($the_val) ? '(' . __('none', 'upstream') . ')' : $the_val;

        return $the_val;
    }
}
