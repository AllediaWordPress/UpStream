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
     * The post type;
     */
    const DEFAULT_POST_TYPE = 'upst_milestone';

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
    }

    /**
     * Return the post type name.
     *
     * @return string
     * @since 1.24.0
     */
    public function getPostType()
    {
        return self::DEFAULT_POST_TYPE;
    }

    /**
     * Create the post type for milestones.
     *
     * @since 1.24.0
     */
    public function createPostType()
    {
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
            'supports'           => ['title', 'comments', 'custom-fields'],
        ];

        register_post_type($this->getPostType(), $args);
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
     */
    public function renderMetaBox($post)
    {
        $upstream = \UpStream::instance();

        // Start date
        $startDate = strtotime(get_post_meta($post->ID, 'upst_start_date', true));
        $startDate = upstream_format_date($startDate);

        // End date
        $endDate = strtotime(get_post_meta($post->ID, 'upst_end_date', true));
        $endDate = upstream_format_date($endDate);

        $context = [
            'field_prefix' => '_upstream_milestone_',
            'members'      => (array)upstream_project_users_dropdown(),
            'permissions'  => [
                'edit_assigned_to' => upstream_permissions('milestone_assigned_to_field'),
                'edit_start_date'  => upstream_permissions('milestone_start_date_field'),
                'edit_end_date'    => upstream_permissions('milestone_end_date_field'),
                'edit_notes'       => upstream_permissions('milestone_notes_field'),
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
                'assigned_to' => get_post_meta($post->ID, 'upst_assigned_to', true),
                'start_date'  => $startDate,
                'end_date'    => $endDate,
                'notes'       => get_post_meta($post->ID, 'upst_notes', true),
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

        // Assigned to
        update_post_meta($postId, 'upst_assigned_to', array_map('intval', (array)$data['assigned_to']));

        // Start date
        $startDateFieldName = 'start_date';
        $startDate          = ! empty($data[$startDateFieldName]) ? sanitize_text_field($data[$startDateFieldName]) : '';
        if ( ! empty($startDate)) {
            $startDate = upstream_date_unixtime($startDate);
            $startDate = upstream_format_date($startDate, 'Y-m-d');
        }

        update_post_meta($postId, 'upst_start_date', $startDate);

        // End date
        $endDateFieldName = 'end_date';
        $endDate          = ! empty($data[$endDateFieldName]) ? sanitize_text_field($data[$endDateFieldName]) : '';
        if ( ! empty($endDate)) {
            $endDate = upstream_date_unixtime($endDate);
            $endDate = upstream_format_date($endDate, 'Y-m-d');
        }

        update_post_meta($postId, 'upst_end_date', $endDate);

        // Notes
        $notes = wp_kses_post($data['notes']);
        update_post_meta($postId, 'upst_notes', $notes);
    }
}
