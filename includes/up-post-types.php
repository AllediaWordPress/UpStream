<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


/**
 * Registers and sets up the Downloads custom post type
 *
 * @return void
 * @since 1.0
 */
function upstream_setup_post_types()
{
    $project_base = upstream_get_project_base();
    $client_base  = upstream_get_client_base();

    $project_labels = apply_filters('upstream_project_labels', [
        'name'                  => _x('%2$s', 'project post type name', 'upstream'),
        'singular_name'         => _x('%1$s', 'singular project post type name', 'upstream'),
        'add_new'               => __('New %1s', 'upstream'),
        'add_new_item'          => __('Add New %1$s', 'upstream'),
        'edit_item'             => __('Edit %1$s', 'upstream'),
        'new_item'              => __('New %1$s', 'upstream'),
        'all_items'             => __('%2$s', 'upstream'),
        'view_item'             => __('View %1$s', 'upstream'),
        'search_items'          => __('Search %2$s', 'upstream'),
        'not_found'             => __('No %2$s found', 'upstream'),
        'not_found_in_trash'    => __('No %2$s found in Trash', 'upstream'),
        'parent_item_colon'     => '',
        'menu_name'             => _x('%2$s', 'project post type menu name', 'upstream'),
        'featured_image'        => __('%1$s Image', 'upstream'),
        'set_featured_image'    => __('Set %1$s Image', 'upstream'),
        'remove_featured_image' => __('Remove %1$s Image', 'upstream'),
        'use_featured_image'    => __('Use as %1$s Image', 'upstream'),
        'filter_items_list'     => __('Filter %2$s list', 'upstream'),
        'items_list_navigation' => __('%2$s list navigation', 'upstream'),
        'items_list'            => __('%2$s list', 'upstream'),
    ]);

    foreach ($project_labels as $key => $value) {
        $project_labels[$key] = sprintf($value, upstream_project_label(), upstream_project_label_plural());
    }

    $project_args = [
        'labels'             => $project_labels,
        'public'             => false,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-arrow-up-alt',
        'menu_position'      => 56,
        'query_var'          => true,
        'rewrite'            => ['slug' => $project_base, 'with_front' => false],
        'capability_type'    => 'project',
        'map_meta_cap'       => true,
        'has_archive'        => $project_base,
        'hierarchical'       => false,
        'supports'           => apply_filters('upstream_project_supports', ['title', 'revisions', 'author']),
    ];
    register_post_type('project', apply_filters('upstream_project_post_type_args', $project_args));

    if (is_clients_disabled()) {
        return;
    }

    /* Client Post Type */
    $client_labels = apply_filters('upstream_client_labels', [
        'name'                  => _x('%2$s', 'project post type name', 'upstream'),
        'singular_name'         => _x('%1$s', 'singular project post type name', 'upstream'),
        'add_new'               => __('New %1s', 'upstream'),
        'add_new_item'          => __('Add New %1$s', 'upstream'),
        'edit_item'             => __('Edit %1$s', 'upstream'),
        'new_item'              => __('New %1$s', 'upstream'),
        'all_items'             => __('%2$s', 'upstream'),
        'view_item'             => __('View %1$s', 'upstream'),
        'search_items'          => __('Search %2$s', 'upstream'),
        'not_found'             => __('No %2$s found', 'upstream'),
        'not_found_in_trash'    => __('No %2$s found in Trash', 'upstream'),
        'parent_item_colon'     => '',
        'menu_name'             => _x('%2$s', 'project post type menu name', 'upstream'),
        'featured_image'        => __('%1$s Image', 'upstream'),
        'set_featured_image'    => __('Set %1$s Image', 'upstream'),
        'remove_featured_image' => __('Remove %1$s Image', 'upstream'),
        'use_featured_image'    => __('Use as %1$s Image', 'upstream'),
        'filter_items_list'     => __('Filter %2$s list', 'upstream'),
        'items_list_navigation' => __('%2$s list navigation', 'upstream'),
        'items_list'            => __('%2$s list', 'upstream'),
    ]);

    foreach ($client_labels as $key => $value) {
        $client_labels[$key] = sprintf($value, upstream_client_label(), upstream_client_label_plural());
    }

    $client_args = [
        'labels'             => $client_labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'query_var'          => true,
        'rewrite'            => ['slug' => $client_base, 'with_front' => false],
        'capability_type'    => 'client',
        'map_meta_cap'       => true,
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => apply_filters('upstream_client_supports', ['title', 'revisions']),
    ];
    register_post_type('client', apply_filters('upstream_client_post_type_args', $client_args));

    \UpStream\Milestones::getInstance()->createPostType();
}

add_action('init', 'upstream_setup_post_types', 1);

/**
 * Registers the custom taxonomies for the projects custom post type
 *
 * @return void
 * @since 1.0
 */
function upstream_setup_taxonomies()
{
    if (is_project_categorization_disabled()) {
        return;
    }

    $slug = defined('UPSTREAM_CAT_SLUG') ? UPSTREAM_CAT_SLUG : 'projects';

    /** Categories */
    $category_labels = [
        'name'              => _x('Category', 'taxonomy general name', 'upstream'),
        'singular_name'     => _x('Category', 'taxonomy singular name', 'upstream'),
        'search_items'      => sprintf(__('Search %s Categories', 'upstream'), upstream_project_label()),
        'all_items'         => sprintf(__('All %s Categories', 'upstream'), upstream_project_label()),
        'parent_item'       => sprintf(__('Parent %s Category', 'upstream'), upstream_project_label()),
        'parent_item_colon' => sprintf(__('Parent %s Category:', 'upstream'), upstream_project_label()),
        'edit_item'         => sprintf(__('Edit %s Category', 'upstream'), upstream_project_label()),
        'update_item'       => sprintf(__('Update %s Category', 'upstream'), upstream_project_label()),
        'add_new_item'      => sprintf(__('Add New %s Category', 'upstream'), upstream_project_label()),
        'new_item_name'     => sprintf(__('New %s Category Name', 'upstream'), upstream_project_label()),
        'menu_name'         => __('Categories', 'upstream'),
    ];

    $category_args = apply_filters(
        'upstream_project_category_args',
        [
            'hierarchical'      => true,
            'labels'            => apply_filters('_upstream_project_category_labels', $category_labels),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => 'project_category',
            'rewrite'           => ['slug' => $slug . '/category', 'with_front' => false, 'hierarchical' => true],
            'capabilities'      => [
                'manage_terms' => 'manage_project_terms',
                'edit_terms'   => 'edit_project_terms',
                'assign_terms' => 'assign_project_terms',
                'delete_terms' => 'delete_project_terms',
            ],
        ]
    );
    register_taxonomy('project_category', ['project'], $category_args);
    register_taxonomy_for_object_type('project_category', 'project');

    /** Tags **/
    $tagsLabels = [
        'name'                       => _x('Tags', 'taxonomy (tag) general name', 'upstream'),
        'singular_name'              => _x('Tag', 'taxonomy (tag) singular name', 'upstream'),
        'search_items'               => __('Search Tags', 'upstream'),
        'popular_items'              => __('Popular Tags'),
        'all_items'                  => __('All Tags', 'upstream'),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __('Edit Tag', 'upstream'),
        'update_item'                => __('Update Tag', 'upstream'),
        'add_new_item'               => __('Add New Tag', 'upstream'),
        'new_item_name'              => __('New Tag Name', 'upstream'),
        'add_or_remove_items'        => __('Add or remove tags'),
        'separate_items_with_commas' => __('Separate tags with commas'),
        'choose_from_most_used'      => __('Choose from the most used tags'),
        'menu_name'                  => __('Tags', 'upstream'),
    ];

    $args = [
        'hierarchical'      => false,
        'labels'            => apply_filters('_upstream_project_tags_labels', $tagsLabels),
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => 'upstream_tag',
        'rewrite'           => [
            'slug'         => 'upstream/tag',
            'with_front'   => false,
            'hierarchical' => false,
        ],
        'capabilities'      => [
            'manage_terms' => 'manage_project_terms',
            'edit_terms'   => 'edit_project_terms',
            'assign_terms' => 'assign_project_terms',
            'delete_terms' => 'delete_project_terms',
        ],
    ];

    register_taxonomy('upstream_tag', ['project'], $args);
    register_taxonomy_for_object_type('upstream_tag', 'project');

    /** Milestone Categories **/
    $tagsLabels = [
        'name'                       => upstream_milestone_category_label_plural(),
        'singular_name'              => upstream_milestone_category_label(),
        'search_items'               => sprintf(__('Search %s', 'upstream'),
            upstream_milestone_category_label_plural()),
        'popular_items'              => sprintf(__('Popular %s'), upstream_milestone_category_label_plural()),
        'all_items'                  => sprintf(__('All %s', 'upstream'), upstream_milestone_category_label_plural()),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => sprintf(__('Edit %s', 'upstream'), upstream_milestone_category_label()),
        'update_item'                => sprintf(__('Update %s', 'upstream'), upstream_milestone_category_label()),
        'add_new_item'               => sprintf(__('Add New %s', 'upstream'), upstream_milestone_category_label()),
        'new_item_name'              => sprintf(__('New %s Name', 'upstream'), upstream_milestone_category_label()),
        'add_or_remove_items'        => sprintf(__('Add or remove %s'), upstream_milestone_category_label_plural()),
        'separate_items_with_commas' => sprintf(__('Separate %s with commas'), upstream_milestone_category_label()),
        'choose_from_most_used'      => sprintf(__('Choose from the most used %s'),
            upstream_milestone_category_label()),
        'menu_name'                  => sprintf(__('%s', 'upstream'), upstream_milestone_category_label()),
    ];

    if ( ! upstream_disable_milestone_categories()) {
        $args = [
            'hierarchical'      => true,
            'labels'            => apply_filters('_upstream_milestone_categories_labels', $tagsLabels),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => 'upstream_milestone_category',
            'rewrite'           => [
                'slug'         => 'upstream/milestone_category',
                'with_front'   => false,
                'hierarchical' => false,
            ],
            'capabilities'      => [
                'manage_terms' => 'manage_project_terms',
                'edit_terms'   => 'edit_project_terms',
                'assign_terms' => 'assign_project_terms',
                'delete_terms' => 'delete_project_terms',
            ],
        ];

        register_taxonomy('upst_milestone_category', ['upst_milestone'], $args);
        register_taxonomy_for_object_type('upst_milestone_category', 'upst_milestone');
    }
}

add_action('init', 'upstream_setup_taxonomies', 1);

/**
 * Milestone taxonomies custom fields.
 */
function upstream_milestone_category_form_fields($taxonomy) {
    $value = '';
    if (is_object($taxonomy)) {
        $value = get_term_meta($taxonomy->term_id, 'color', true);
    }

    ?>
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="term_color"><?php _e('Default color', 'upstream'); ?></label>
        </th>
        <td>
            <input type="text" name="color" class="color-field" id="term_color" value="<?php echo $value; ?>" />
            <p class="description">Select a default color for milestones related to this category.</p>
        </td>
    </tr>
    <br>
    <?php
}

function upstream_save_milestone_category_form_fields($termId) {
    if (isset($_POST['color'])) {
        update_term_meta($termId, 'color', sanitize_text_field($_POST['color']));
    }
}

if ( ! is_project_categorization_disabled()) {
    add_action('upst_milestone_category_add_form_fields', 'upstream_milestone_category_form_fields');
    add_action('upst_milestone_category_edit_form_fields', 'upstream_milestone_category_form_fields');
    add_action('edit_terms', 'upstream_save_milestone_category_form_fields');
    add_action('create_term', 'upstream_save_milestone_category_form_fields');
}
