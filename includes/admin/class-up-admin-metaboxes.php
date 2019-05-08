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

                if ( ! empty($milestones)) {
                    foreach ($milestones as $milestone) {
                        $milestone = \UpStream\Factory::getMilestone($milestone);

                        $milestoneData = $milestone->convertToLegacyRowset();

                        $data[] = $milestoneData;
                    }
                }
            }

            return $data;
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
            if ($object['field_id'] === '_upstream_project_milestones') {
                if (isset($object['value']) && is_array($object['value'])) {
                    $currentMilestoneIds = [];

                    foreach ($object['value'] as $milestoneData) {
                        // If doesn't have an id, we create the milestone.
                        if ( ! isset($milestoneData['id']) || EMPTY($milestoneData['id'])) {
                            $milestone = \UpStream\Factory::createMilestone($milestoneData['milestone']);

                            $milestone->setProjectId($object['id']);
                        } else {
                            // Update the milestone.
                            $milestone = \UpStream\Factory::getMilestone($milestoneData['id']);

                            $milestone->setName($milestoneData['milestone']);
                        }

                        if (empty($milestone)) {
                            continue;
                        }

                        $milestone->setOrder($milestone->getName());

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

                        if (isset($milestoneData['notes'])) {
                            $milestone->setNotes($milestoneData['notes']);
                        } else {
                            $milestone->setNotes('');
                        }

                        if (isset($milestoneData['color'])) {
                            $milestone->setColor($milestoneData['color']);
                        } else {
                            $milestone->setColor('');
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
