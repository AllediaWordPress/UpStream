<?php

namespace UpStream;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Traits\Singleton;

/**
 * This class will act as a controller handling incoming requests regarding comments on UpStream items.
 *
 * @since   1.24.0
 */
class Milestones
{
    use Singleton;

    /**
     *
     */
    protected $postTypeCreated = false;

    /**
     * Class constructor.
     *
     * @since   1.24.0
     */
    public function __construct()
    {
        $this->attachHooks();
    }

    /**
     * Attach all relevant actions to handle comments.
     *
     * @since   1.24.0
     */
    private function attachHooks()
    {
        if (upstream_disable_milestones()) {
            return;
        }

        add_action('before_upstream_init', [$this, 'createPostType']);
        add_action('add_meta_boxes', [$this, 'addMetaBox'], 8);
        add_action('save_post', [$this, 'savePost']);

        $postType = $this->getPostType();

        add_filter('manage_' . $postType . '_posts_columns', [$this, 'manage_posts_columns'], 10);
        add_action('manage_' . $postType . '_posts_custom_column', [$this, 'render_post_columns'], 10, 2);
    }

    /**
     * Return the post type name.
     *
     * @return string
     * @since 1.24.0
     */
    public function getPostType()
    {
        return Milestone::POST_TYPE;
    }

    /**
     * @param int $projectId
     *
     * @return bool
     * @throws \Exception
     */
    public static function migrateLegacyMilestonesForProject($projectId)
    {
        // Migrate the milestones.
        $defaultMilestones = get_option('upstream_milestones', []);

        if ( ! empty($defaultMilestones)) {
            $defaultMilestones = $defaultMilestones['milestones'];
            $legacyMilestones  = [];

            // Organize the milestones by id
            foreach ($defaultMilestones as $milestoneData) {
                $legacyMilestones[$milestoneData['id']] = $milestoneData;
            }

            global $wpdb;

            // Get the project's milestones to convert them into the new post types.
            $projectMilestones = get_post_meta($projectId, '_upstream_project_milestones', true);
            $projectTasks      = get_post_meta($projectId, '_upstream_project_tasks', true);

            try {
                if ( ! empty($projectMilestones)) {
                    // Check if the backup register doesn't exist.
                    $legacyMilestonesBackup = get_post_meta($projectId, '_upstream_project_milestones_legacy', true);
                    if (empty($legacyMilestonesBackup)) {
                        // Move the milestones to a backup register, temporarily.
                        update_post_meta($projectId, '_upstream_project_milestones_legacy', $projectMilestones);
                    }

                    $wpdb->query('START TRANSACTION');

                    $updatedTasks = false;

                    foreach ($projectMilestones as $projectMilestone) {

                        $data = $legacyMilestones[$projectMilestone['milestone']];

                        // Check if we already have this milestone in the project.
                        $migratedMilestone = get_posts([
                            'post_type'   => Milestone::POST_TYPE,
                            'post_parent' => $projectId,
                            'post_status' => 'publish',
                            'meta_key'    => Milestone::META_LEGACY_MILESTONE_CODE,
                            'meta_value'  => $projectMilestone['milestone'],
                        ]);

                        // If the milestone already exists, abort
                        if ( ! empty($migratedMilestone)) {
                            continue;
                        }

                        // The milestone doesn't exist. Let's create it.
                        $milestone = Factory::createMilestone($data['title'])
                                            ->setLegacyId($projectMilestone['id'])
                                            ->setLegacyMilestoneCode($projectMilestone['milestone'])
                                            ->setStartDate($projectMilestone['start_date'])
                                            ->setEndDate($projectMilestone['end_date'])
                                            ->setAssignedTo($projectMilestone['assigned_to'])
                                            ->setNotes($projectMilestone['notes'])
                                            ->setCreatedTimeInUtc((int)$projectMilestone['notes'] === 1)
                                            ->setProgress((float)$projectMilestone['progress'])
                                            ->setTaskCount((int)$projectMilestone['task_count'])
                                            ->setTaskOpen((int)$projectMilestone['task_open'])
                                            ->setColor($data['color'])
                                            //->setOrder($data['title'])
                                            ->setProjectId($projectId);

                        // Look for all the tasks to convert the milestone ID.
                        if ( ! empty($projectTasks)) {
                            foreach ($projectTasks as &$task) {
                                if ($task['milestone'] === $milestone->getLegacyId()) {
                                    $task['milestone'] = $milestone->getId();
                                    // Keep the legacy reference for a while.
                                    $task['milestone_legacy'] = $milestone->getLegacyId();

                                    $updatedTasks = true;
                                }
                            }
                        }
                    }

                    update_post_meta($projectId, '_upstream_milestones_migrated', 1);

                    // Remove the legacy Milestones
                    delete_post_meta($projectId, '_upstream_project_milestones');

                    // Update the tasks in the project
                    if ($updatedTasks) {
                        update_post_meta($projectId, '_upstream_project_tasks', $projectTasks);
                    }

                    $wpdb->query('COMMIT');
                } else {
                    update_post_meta($projectId, '_upstream_project_milestones_legacy', []);
                }
            } catch (\Exception $e) {
                $wpdb->query('ROLLBACK');

                throw new Exception('Error found while migrating a milestone. ' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * @param int $projectId
     *
     * @return bool
     * @throws \Exception
     */
    public static function fixMilestoneOrdersOnProject($projectId)
    {
        try {
            $projectMilestones = self::getInstance()->getMilestonesFromProject($projectId);

            if ( ! empty($projectMilestones)) {
                global $wpdb;

                $wpdb->query('START TRANSACTION');

                foreach ($projectMilestones as $projectMilestone) {
                    $milestone = Factory::getMilestone($projectMilestone);

                    //$milestone->setOrder($milestone->getName());
                }

                $wpdb->query('COMMIT');
            }
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');

            throw new Exception('Error found while fixing the order on a milestone. ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Create the post type for milestones.
     *
     * @since 1.24.0
     */
    public function createPostType()
    {
        if ($this->postTypeCreated) {
            return;
        }

        $singularLabel = upstream_milestone_label();
        $pluralLabel   = upstream_milestone_label_plural();

        $labels = [
            'name'                  => $pluralLabel,
            'singular_name'         => $singularLabel,
            'add_new'               => sprintf(_x('Add new %s', 'upstream'), $singularLabel),
            'edit_item'             => sprintf(__('Edit %s', 'upstream'), $singularLabel),
            'new_item'              => sprintf(__('New %s', 'upstream'), $singularLabel),
            'view_item'             => sprintf(__('View %s', 'upstream'), $singularLabel),
            'view_items'            => sprintf(__('View %s', 'upstream'), $pluralLabel),
            'search_items'          => sprintf(__('Search %s', 'upstream'), $pluralLabel),
            'not_found'             => sprintf(__('No %s found', 'upstream'), $pluralLabel),
            'not_found_in_trash'    => sprintf(__('No %s found in Trash', 'upstream'), $singularLabel),
            'parent_item_colon'     => sprintf(__('Parent %s:', 'upstream'), $singularLabel),
            'all_items'             => sprintf(__('%s', 'upstream'), $pluralLabel),
            'archives'              => sprintf(__('%s Archives', 'upstream'), $singularLabel),
            'attributes'            => sprintf(__('%s Attributes', 'upstream'), $singularLabel),
            'insert_into_item'      => sprintf(__('Insert into %s', 'upstream'), $singularLabel),
            'uploaded_to_this_item' => sprintf(__('Uploaded to this %s', 'upstream'), $singularLabel),
            'featured_image'        => __('Featured Image', 'upstream'),
            'set_featured_image'    => __('Set featured image', 'upstream'),
            'remove_featured_image' => __('Remove featured image', 'upstream'),
            'use_featured_image'    => __('Use as featured image', 'upstream'),
            'menu_name'             => $pluralLabel,
            'filter_items_list'     => $pluralLabel,
            'items_list_navigation' => $pluralLabel,
            'items_list'            => $pluralLabel,
            'name_admin_bar'        => $pluralLabel,
        ];

        $args = [
            'labels'             => $labels,
            // 'description' => '',
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=project',
            'rewrite'            => ['slug' => strtolower($singularLabel)],
            'capability_type'    => 'milestone',
            'has_archive'        => true,
            'hierarchical'       => false,
            'supports'           => ['title', 'comments'],
            'map_meta_cap'       => true,
        ];

        register_post_type($this->getPostType(), $args);

        $this->postTypeCreated = true;
    }

    /**
     * Add meta boxes to the post type.
     *
     * @param string $postType
     *
     * @since 1.24.0
     */
    public function addMetaBox($postType)
    {
        if ($this->getPostType() !== $postType) {
            return;
        }

        add_meta_box(
            'upstream_mimlestone_data',
            __('Data', 'upstream'),
            [$this, 'renderMetaBox'],
            $this->getPostType(),
            'advanced',
            'high'
        );
    }

    /**
     * Render the metabox for data.
     *
     * @param \WP_Post $post
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @since 1.24.0
     *
     */
    public function renderMetaBox($post)
    {
        $upstream = \UpStream::instance();

        // Projects
        $projectsInstances = get_posts(['post_type' => 'project', 'posts_per_page' => -1]);
        $projects          = [];
        if ( ! empty($projectsInstances)) {
            foreach ($projectsInstances as $project) {
                $projects[$project->ID] = $project->post_title;
            }
        }

        $milestone = Factory::getMilestone($post->ID);

        $context = [
            'field_prefix' => '_upstream_milestone_',
            'members'      => (array)$this->projectUsersDropdown(),
            'projects'     => $projects,
            'permissions'  => [
                'edit_assigned_to' => current_user_can('milestone_assigned_to_field'),
                'edit_start_date'  => current_user_can('milestone_start_date_field'),
                'edit_end_date'    => current_user_can('milestone_end_date_field'),
                'edit_notes'       => current_user_can('milestone_notes_field'),
                'edit_project'     => current_user_can('edit_projects'),
            ],
            'labels'       => [
                'assigned_to' => __('Assigned To', 'upstream'),
                'none'        => __('None', 'upstream'),
                'start_date'  => __('Start Date', 'upstream'),
                'end_date'    => __('End Date', 'upstream'),
                'notes'       => __('Notes', 'upstream'),
                'project'     => __('Project', 'upstream'),
                'color'       => __('Color', 'upstream'),
            ],
            'data'         => [
                'assigned_to' => get_post_meta($post->ID, 'upst_assigned_to', false),
                'start_date'  => $milestone->getStartDate('upstream'),
                'end_date'    => $milestone->getEndDate('upstream'),
                'notes'       => $milestone->getNotes(),
                'project_id'  => $milestone->getProjectId(),
                'color'       => $milestone->getColor(),
                'id'          => $milestone->getId(),
            ],
        ];

        echo $upstream->twigRender('milestone-form-fields.twig', $context);
    }

    /**
     * Returns all users with select roles.
     * For use in dropdowns.
     */
    protected function projectUsersDropdown()
    {
        $options = [
            '' => __('None', 'upstream'),
        ];

        $projectUsers = upstream_admin_get_all_project_users();

        $options += $projectUsers;

        return $options;
    }

    /**
     * @param int $postId
     *
     * @throws \Exception
     * @since 1.24.0
     */
    public function savePost($postId)
    {
        if ( ! isset($_POST['milestone_data'])) {
            return;
        }

        $data = $_POST['milestone_data'];

        // Project
        $projectIdFieldName = 'project_id';
        $projectId          = (int)$data[$projectIdFieldName];

        // Start date
        $startDateFieldName = 'start_date';
        $startDate          = ! empty($data[$startDateFieldName]) ? sanitize_text_field($data[$startDateFieldName]) : '';

        // End date
        $endDateFieldName = 'end_date';
        $endDate          = ! empty($data[$endDateFieldName]) ? sanitize_text_field($data[$endDateFieldName]) : '';

        // Notes
        $notes = wp_kses_post($data['notes']);

        $color = sanitize_text_field($data['color']);

        // Store the values
        $milestone = Factory::getMilestone($postId);
        $milestone->setProjectId($projectId)
                  ->setStartDate($startDate)
                  ->setEndDate($endDate)
                  ->setNotes($notes)
                  ->setColor($color);

        // If there is no assigned user, there won't be any key assigned_to in the $data array.
        if (isset($data['assigned_to'])) {
            $assignedTo = array_map('intval', (array)$data['assigned_to']);

            $milestone->setAssignedTo($assignedTo);
        }

        /**
         * @param int   $projectId
         * @param array $data
         */
        do_action('upstream_save_milestone', $postId, $data);
    }

    /**
     * @param $columns
     *
     * @return array
     * @since 1.24.0
     *
     */
    public function manage_posts_columns($columns)
    {
        $columns['project']     = __('Project', 'upstream');
        $columns['assigned_to'] = __('Assigned To', 'upstream');
        $columns['start_date']  = __('Start Date', 'upstream');
        $columns['end_date']    = __('End Date', 'upstream');

        return $columns;
    }

    /**
     * @param $column
     * @param $postId
     *
     * @since 1.24.0
     */
    public function render_post_columns($column, $postId)
    {
        $milestone = Factory::getMilestone($postId);

        if ($column === 'project') {
            $project = get_post($milestone->getProjectId());

            if (!empty($project)) {
                echo $project->post_title;
            }
        }

        if ($column === 'assigned_to') {
            $usersId = $milestone->getAssignedTo();

            if (empty($usersId)) {
                echo '<span><i class="text-muted">' . __('none', 'upstream') . '</i></span>';

                return;
            }

            $users = [];

            foreach ($usersId as $id) {
                $u = get_user_by('id', $id);
                // RSD: fix error where $u is null
                if ($u) {
                    $users[] = $u->display_name;
                }
            }

            echo implode(', ', $users);
        }

        if ($column === 'start_date') {
            echo $milestone->getStartDate('upstream');
        }

        if ($column === 'end_date') {
            echo $milestone->getEndDate('upstream');
        }
    }

    /**
     * @return bool
     */
    public function hasAnyMilestone()
    {
        $posts = get_posts(
            [
                'post_type'      => Milestone::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
            ]
        );

        return count($posts) > 0;
    }

    /**
     * @param $projectId
     *
     * @return array|mixed|null
     *
     * @throws Exception
     */
    public function getMilestonesAsRowset($projectId)
    {
        $projectMilestones = $this->getMilestonesFromProject($projectId);
        $data              = [];

        if ( ! empty($projectMilestones)) {
            foreach ($projectMilestones as $milestone) {
                $milestone = \UpStream\Factory::getMilestone($milestone);

                $row = $milestone->convertToLegacyRowset();

                $data[$row['id']] = $row;
            }

            $data = apply_filters('upstream_project_milestones', $data, $projectId);
        }

        return $data;
    }

    /**
     * @param int  $projectId
     * @param bool $returnAsLegacyDataset
     *
     * @return array
     * @throws Exception
     */
    public function getMilestonesFromProject($projectId, $returnAsLegacyDataset = false)
    {
        $posts = get_posts(
            [
                'post_type'      => Milestone::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_key'       => Milestone::META_PROJECT_ID,
                'meta_value'     => $projectId,
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
            ]
        );

        $milestones = [];

        if ( ! empty($posts)) {
            foreach ($posts as $post) {
                if ($returnAsLegacyDataset) {
                    $data = Factory::getMilestone($post)->convertToLegacyRowset();
                } else {
                    $data = $post;
                }

                $milestones[$post->ID] = $data;
            }
        }

        return $milestones;
    }

    /**
     * @param $categories
     *
     * @return string
     */
    public function getCategoriesNames($categories)
    {
        $names = [];

        if (is_array($categories) && ! empty($categories)) {
            foreach ($categories as $category) {
                if (is_numeric($category)) {
                    $category = get_term($category);
                }

                if (is_object($category)) {
                    $names[] = $category->name;
                }
            }
        }

        return implode(', ', $names);
    }
}
