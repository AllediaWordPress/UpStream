<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! class_exists('UpStream_Admin_Metaboxes')) :

    /**
     * CMB2 Theme Options
     *
     * @version 0.1.0
     */
    class UpStream_Admin_Metaboxes
    {

        /**
         * Constructor
         *
         * @since 0.1.0
         */
        public function __construct()
        {
            if (upstreamShouldRunCmb2()) {
                add_action('cmb2_admin_init', [$this, 'register_metaboxes']);
                add_filter('cmb2_override_meta_value', [$this, 'getProjectMeta'], 10, 3);
                add_filter('cmb2_override_meta_save', [$this, 'setProjectMeta'], 10, 3);
                add_filter('cmb2_field_new_value', [$this, 'addVals'], 10, 3);
                add_filter('cmb2_save_field__upstream_project_start', [$this, 'cmb2_save_field__upstream_project_start'], 10, 3);
                add_filter('cmb2_save_field__upstream_project_end', [$this, 'cmb2_save_field__upstream_project_end'], 10, 3);
            }

            UpStream_Metaboxes_Clients::attachHooks();
        }

        /**
         * Add the options metabox to the array of metaboxes
         *
         * @since  0.1.0
         */
        public function register_metaboxes()
        {
            /**
             * Load the metaboxes for project post type
             */
            $project_metaboxes = new UpStream_Metaboxes_Projects();
            $project_metaboxes->get_instance();

            // Load all Client metaboxes (post_type="client").
            UpStream_Metaboxes_Clients::instantiate();
        }

        public function getProjectMeta($data, $id, $field)
        {
            // Override the milestone data for the metaboxes.
            if ($field['field_id'] === '_upstream_project_milestones') {
                $milestones = \UpStream\Milestones::getInstance()->getMilestonesFromProject($id);

                $data = [];

                if (!empty($milestones)) {
                    foreach ($milestones as $milestone) {
                        $milestone = \UpStream\Factory::getMilestone($milestone);

                        $milestoneData = $milestone->convertToLegacyRowset();

                        $data[] = $milestoneData;
                    }
                }
            } else if ($field['field_id'] === '_upstream_project_tasks') {
                $data = [];
                $data = get_metadata($field['type'], $field['id'], $field['field_id'], ($field['single'] || $field['repeat']));

                // RSD: this is for backward compatibility with timezones
                // TODO: remove this
                $offset = get_option('gmt_offset');

                for ($i = 0; $data && $i < count($data); $i++) {

                    if (isset($data[$i]['start_date']) && is_numeric($data[$i]['start_date'])) {
                        $startDateTimestamp = $data[$i]['start_date'];
                        $startDateTimestamp = $startDateTimestamp + ($offset > 0 ? $offset * 60 * 60 : 0);
                        $data[$i]['start_date'] = $startDateTimestamp;

                        if (!empty($data[$i]['start_date.YMD'])) {
                            $data[$i]['start_date'] = strtotime($data[$i]['start_date.YMD']);
                        }

                    }

                    if (isset($data[$i]['end_date']) && is_numeric($data[$i]['end_date'])) {
                        $endDateTimestamp = $data[$i]['end_date'];
                        $endDateTimestamp = $endDateTimestamp + ($offset > 0 ? $offset * 60 * 60 : 0);
                        $data[$i]['end_date'] = $endDateTimestamp;

                        if (!empty($data[$i]['end_date.YMD'])) {
                            $data[$i]['end_date'] = strtotime($data[$i]['end_date.YMD']);
                        }
                    }
                }
            } else if ($field['field_id'] === '_upstream_project_bugs') {
                $data = [];
                $data = get_metadata($field['type'], $field['id'], $field['field_id'], ($field['single'] || $field['repeat']));

                // RSD: this is for backward compatibility with timezones
                // TODO: remove this
                $offset = get_option('gmt_offset');

                for ($i = 0; $data && $i < count($data); $i++) {

                    if (isset($data[$i]['due_date']) && is_numeric($data[$i]['due_date'])) {
                        $dueDateTimestamp = $data[$i]['due_date'];
                        $dueDateTimestamp = $dueDateTimestamp + ($offset > 0 ? $offset * 60 * 60 : 0);
                        $data[$i]['due_date'] = $dueDateTimestamp;

                        if (!empty($data[$i]['due_date.YMD'])) {
                            $data[$i]['due_date'] = strtotime($data[$i]['due_date.YMD']);
                        }

                    }
                }
            }

            return $data;
        }

        public function addVals($new_value, $single, $args)
        {
            if (is_array($new_value)) {
                for ($i = 0; $i < count($new_value); $i++) {

                    if (!is_array($new_value[$i])) continue;

                    foreach ($new_value[$i] as $key => $value) {

                        if (stristr($key, 'date')) {
                            $r = sanitize_text_field($_POST[$args['id']][$i][$key]);
                            if (\UpStream_Model_Object::isValidDate($r)) {
                                $new_value[$i][$key . '.YMD'] = $r;
                            }
                        }

                    }

                }
            }

            return $new_value;
        }

        public function cmb2_save_field__upstream_project_start($updated, $action, $r)
        {
            $r = sanitize_text_field($_POST['_upstream_project_start']);
            $project_id = intval($_POST['post_ID']);
            /* if project ID isn't valid the access function will return false */
            if (\UpStream_Model_Object::isValidDate($r) && upstream_user_can_access_project(get_current_user_id(), $project_id)) {
                update_post_meta($_POST['post_ID'], '_upstream_project_start.YMD', $r);
            }
        }

        public function cmb2_save_field__upstream_project_end($updated, $action, $r)
        {
            $r = sanitize_text_field($_POST['_upstream_project_end']);
            $project_id = intval($_POST['post_ID']);
            /* if project ID isn't valid the access function will return false */
            if (\UpStream_Model_Object::isValidDate($r) && upstream_user_can_access_project(get_current_user_id(), $project_id)) {
                update_post_meta($project_id, '_upstream_project_end.YMD', $r);
            }
        }

        /**
         * @param $check
         * @param $object
         * @param $form
         *
         * @return bool
         * @throws \UpStream\Exception
         */
        public function setProjectMeta($check, $object, $form)
        {

            $object_type = "";


            if ($object['field_id'] === '_upstream_project_status') {

                do_action('upstream_item_pre_change', 'project', $object['id'], $object['id'], $object);

                do_action('upstream_save_metabox_field', $object);
                return $check;

            }


            if ($object['field_id'] === '_upstream_project_milestones') $object_type = 'milestone';
            else if ($object['field_id'] === '_upstream_project_tasks') $object_type = 'task';
            else if ($object['field_id'] === '_upstream_project_bugs') $object_type = 'bug';
            else if ($object['field_id'] === '_upstream_project_files') $object_type = 'file';

            if ($object_type) {
                if (isset($object['value']) && is_array($object['value'])) {
                    for ($i = 0; $i < count($object['value']); $i++) {
                        $item = $object['value'][$i];
                        if (isset($item['id'])) {
                            do_action('upstream_item_pre_change', $object_type, $item['id'], $object['id'], $item);
                        }
                    }
                }
            }


            if ($object['field_id'] === '_upstream_project_milestones') {
                if (isset($object['value']) && is_array($object['value'])) {
                    $currentMilestoneIds = [];

                    for ($i = 0; $i < count($object['value']); $i++) {

                        // $object is sanitized already by CMB2
                        $milestoneData = $object['value'][$i];

                        // If doesn't have an id, we create the milestone.
                        if ( ! isset($milestoneData['id']) || EMPTY($milestoneData['id'])) {
                            $milestone = \UpStream\Factory::createMilestone($milestoneData['milestone']);

                            $milestone->setProjectId($object['id']);
                            $object['value'][$i]['id'] = $milestone->getId();

                        } else {
                            // Update the milestone.
                            $milestone = \UpStream\Factory::getMilestone($milestoneData['id']);

                            $milestone->setName($milestoneData['milestone']);
                        }

                        if (empty($milestone)) {
                            continue;
                        }

                        //$milestone->setOrder($milestone->getName());

                        if ( ! upstream_disable_milestone_categories()) {
                            if (isset($milestoneData['categories'])) {
                                $milestone->setCategories($milestoneData['categories']);
                            } else {
                                $milestone->setCategories([]);
                            }
                        }

                        if (isset($milestoneData['assigned_to'])) {
                            $milestone->setAssignedTo($milestoneData['assigned_to']);
                        } else {
                            $milestone->setAssignedTo(0);
                        }

                        if (isset($milestoneData['start_date'])) {
                            $milestone->setStartDate($milestoneData['start_date']);
                        } else {
                            $milestone->setStartDate('');
                        }

                        if (isset($milestoneData['end_date'])) {
                            $milestone->setEndDate($milestoneData['end_date']);
                        } else {
                            $milestone->setEndDate('');
                        }


                        if (isset($milestoneData['start_date.YMD'])) {
                            $milestone->setStartDate__YMD($milestoneData['start_date.YMD']);
                        } else {
                            $milestone->setStartDate__YMD('');
                        }

                        if (isset($milestoneData['end_date.YMD'])) {
                            $milestone->setEndDate__YMD($milestoneData['end_date.YMD']);
                        } else {
                            $milestone->setEndDate__YMD('');
                        }


                        if (isset($milestoneData['notes'])) {
                            $milestone->setNotes($milestoneData['notes']);
                        } else {
                            $milestone->setNotes('');
                        }

                        // RSD: the colors get replaced because teh color widget isnt on this page
                        if (!isset($milestoneData['color'])) {
                        }
                        elseif (empty($milestoneData['color'])) {
                            $milestone->setColor('');
                        } else {
                            $milestone->setColor($milestoneData['color']);
                        }

                        $currentMilestoneIds[] = $milestone->getId();
                    }

                    // Check if we need to delete any Milestone. If it is not found on the post, it was removed.
                    $milestones = \UpStream\Milestones::getInstance()->getMilestonesFromProject($object['id']);
                    foreach ($milestones as $milestone) {
                        if ( ! in_array($milestone->ID, $currentMilestoneIds)) {
                            $milestone = \UpStream\Factory::getMilestone($milestone);
                            $milestone->delete();
                        }
                    }

                    $check = true;
                }
            }

            do_action('upstream_save_metabox_field', $object);

            return $check;
        }
    }

    new UpStream_Admin_Metaboxes();

endif;
