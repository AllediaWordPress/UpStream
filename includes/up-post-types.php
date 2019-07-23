<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


/**
 * Registers and sets up the Downloads custom post type
 *
 * @since 1.0
 * @return void
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