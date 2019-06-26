<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'upstream_register_permalink_settings');
add_action('admin_init', 'upstream_validate_permalink_settings');

/**
 * Returns the permalink base for projects and client.
 *
 * @parm string $base
 *
 * @return string|false
 */
function upstream_get_permalink_base($base)
{
    if ( ! in_array($base, ['projects', 'milestones', 'tasks', 'client'])) {
        return false;
    }

    /**
     * @var string $default
     */
    $default = trim(sanitize_title(apply_filters('upstream_' . $base . '_base', $base)));

    $base = trim(sanitize_title(__(get_option('upstream_' . $base . '_base', $default), 'upstream')));

    if (empty($base)) {
        $base = $default;
    }

    return $base;
}

/**
 * Returns the project base segment for permalinks.
 *
 * @return mixed
 */
function upstream_get_project_base()
{
    return upstream_get_permalink_base('projects');
}

/**
 * Returns the milestone base segment for permalinks.
 *
 * @return mixed
 */
function upstream_get_milestone_base()
{
    return upstream_get_permalink_base('milestones');
}

/**
 * Returns the task base segment for permalinks.
 *
 * @return mixed
 */
function upstream_get_task_base()
{
    return upstream_get_permalink_base('tasks');
}

/**
 * Returns the client base segment for permalinks.
 *
 * @return mixed
 */
function upstream_get_client_base()
{
    return upstream_get_permalink_base('client');
}

/**
 * Register settings for the permalink.
 */
function upstream_register_permalink_settings()
{

    /*
     * Section
     */
    add_settings_section(
        'upstream',
        __('UpStream Settings', 'upstream'),
        'upstream_permalink_settings_section',
        'permalink'
    );

    /*
     * Fields
     */
    add_settings_field(
        'upstream_projects_permalink',
        __('Projects base', 'upstream'),
        'upstream_print_project_permalink_field',
        'permalink',
        'upstream'
    );

    add_settings_field(
        'upstream_milestones_permalink',
        __('Milestones base', 'upstream'),
        'upstream_print_milestone_permalink_field',
        'permalink',
        'upstream'
    );

    add_settings_field(
        'upstream_tasks_permalink',
        __('Tasks base', 'upstream'),
        'upstream_print_task_permalink_field',
        'permalink',
        'upstream'
    );

    add_settings_field(
        'upstream_client_permalink',
        __('Client base', 'upstream'),
        'upstream_print_client_permalink_field',
        'permalink',
        'upstream'
    );
}

/**
 * Prints the field for the projects' permalink base.
 */
function upstream_print_project_permalink_field()
{
    $value = esc_attr(get_option('upstream_projects_base', ''));

    /**
     * @var string $default
     */
    $default = apply_filters('upstream_projects_base', 'projects');

    echo '<input name="upstream_projects_base" id="upstream_projects_base" type="text" class="regular-text code" value="' . $value . '" placeholder="' . $default . '">';
}

/**
 * Prints the field for the milestones' permalink base.
 */
function upstream_print_milestone_permalink_field()
{
    $value = esc_attr(get_option('upstream_milestones_base', ''));

    /**
     * @var string $default
     */
    $default = apply_filters('upstream_milestones_base', 'milestones');

    echo '<input name="upstream_milestones_base" id="upstream_milestones_base" type="text" class="regular-text code" value="' . $value . '" placeholder="' . $default . '">';
}

/**
 * Prints the field for the tasks' permalink base.
 */
function upstream_print_task_permalink_field()
{
    $value = esc_attr(get_option('upstream_tasks_base', ''));

    /**
     * @var string $default
     */
    $default = apply_filters('upstream_tasks_base', 'tasks');

    echo '<input name="upstream_tasks_base" id="upstream_tasks_base" type="text" class="regular-text code" value="' . $value . '" placeholder="' . $default . '">';
}

/**
 * Prints the field for the client's permalink base.
 */
function upstream_print_client_permalink_field()
{
    $value = esc_attr(get_option('upstream_client_base', ''));

    /**
     * @var string $default
     */
    $default = apply_filters('upstream_client_base', 'client');

    echo '<input name="upstream_client_base" id="upstream_client_base" type="text" class="regular-text code" value="' . $value . '" placeholder="' . $default . '">';
}

/**
 * Validates and save permalink settings.
 */
function upstream_validate_permalink_settings()
{
    if ( ! array_key_exists('permalink_structure', $_POST)) {
        return;
    }

    if ( ! array_key_exists('upstream_nonce', $_POST)) {
        return;
    }

    if ( ! wp_verify_nonce($_POST['upstream_nonce'], 'upstream_permalink_settings')) {
        return;
    }

    if (array_key_exists('upstream_projects_base', $_POST)) {
        $option = sanitize_title($_POST['upstream_projects_base']);
        update_option('upstream_projects_base', $option);
    }

    if (array_key_exists('upstream_milestones_base', $_POST)) {
        $option = sanitize_title($_POST['upstream_milestones_base']);
        update_option('upstream_milestones_base', $option);
    }

    if (array_key_exists('upstream_tasks_base', $_POST)) {
        $option = sanitize_title($_POST['upstream_tasks_base']);
        update_option('upstream_tasks_base', $option);
    }

    if (array_key_exists('upstream_client_base', $_POST)) {
        $option = sanitize_title($_POST['upstream_client_base']);
        update_option('upstream_client_base', $option);
    }
}

/**
 * Prints the output for the permalink section.
 */
function upstream_permalink_settings_section()
{
    $nonce = wp_create_nonce('upstream_permalink_settings');

    echo '<input type="hidden" name="upstream_nonce" value="' . $nonce . '" />';
}
