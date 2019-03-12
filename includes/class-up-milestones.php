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
        add_action('before_upstream_init', [$this, 'createPostType']);
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
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
            'all_items'             => sprintf(__('All %s', 'upstream'), $pluralLabel),
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
            'show_in_menu'       => true,
            'rewrite'            => ['slug' => strtolower($singularLabel)],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'supports'           => ['title', 'comments'],
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
     * @since 1.24.0
     *
     * @param \WP_Post $post
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderMetaBox($post)
    {
        $upstream = \UpStream::instance();

        // Projects
        $projectsInstances = get_posts(['post_type' => 'project', 'posts_per_page' => 0]);
        $projects          = [];
        if ( ! empty($projectsInstances)) {
            foreach ($projectsInstances as $project) {
                $projects[$project->ID] = $project->post_title;
            }
        }

        $milestone = Factory::getMilestone($post->ID);

        $context = [
            'field_prefix' => '_upstream_milestone_',
            'members'      => (array)upstream_project_users_dropdown(),
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
            ],
            'data'         => [
                'assigned_to' => get_post_meta($post->ID, 'upst_assigned_to', false),
                'start_date'  => $milestone->getStartDate('upstream'),
                'end_date'    => $milestone->getEndDate('upstream'),
                'notes'       => $milestone->getNotes(),
                'project_id'  => $milestone->getProjectId(),
            ],
        ];

        echo $upstream->twigRender('milestone-form-fields.twig', $context);
    }

    /**
     * @param int $postId
     *
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

        // Assigned to
        $assignedTo = array_map('intval', (array)$data['assigned_to']);

        // Start date
        $startDateFieldName = 'start_date';
        $startDate          = ! empty($data[$startDateFieldName]) ? sanitize_text_field($data[$startDateFieldName]) : '';

        // End date
        $endDateFieldName = 'end_date';
        $endDate          = ! empty($data[$endDateFieldName]) ? sanitize_text_field($data[$endDateFieldName]) : '';

        // Notes
        $notes = wp_kses_post($data['notes']);

        // Store the values
        Factory::getMilestone($postId)
               ->setProjectId($projectId)
               ->setAssignedTo($assignedTo)
               ->setStartDate($startDate)
               ->setEndDate($endDate)
               ->setNotes($notes);
    }

    /**
     * @param $columns
     *
     * @since 1.24.0
     *
     * @return array
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
        if ($column === 'project') {
            $projectId = get_post_meta($postId, 'upst_project_id', true);

            if (empty($projectId)) {
                return;
            }

            $project = get_post($projectId);

            echo $project->post_title;
        } elseif ($column === 'assigned_to') {
            $usersId = get_post_meta($postId, 'upst_assigned_to', false);

            if ( ! empty($usersId)) {

            }
        }
    }

    /**
     * @return bool
     */
    public function hasAnyMilestone()
    {
        $posts = get_posts(
            [
                'post_type'   => Milestone::POST_TYPE,
                'post_status' => 'publish',
            ]
        );

        return count($posts) > 0;
    }
}
