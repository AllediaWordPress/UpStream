<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


/**
 * Get Default Labels
 *
 * @return array $defaults Default labels
 * @since 1.0.0
 */
function upstream_get_default_labels()
{
    $option = get_option('upstream_general');

    $defaults = [
        'projects'             => [
            'singular' => isset($option['project']['single']) ? $option['project']['single'] : __(
                'Project',
                'upstream'
            ),
            'plural'   => isset($option['project']['plural']) ? $option['project']['plural'] : __(
                'Projects',
                'upstream'
            ),
        ],
        'clients'              => [
            'singular' => isset($option['client']['single']) ? $option['client']['single'] : __(
                'Client',
                'upstream'
            ),
            'plural'   => isset($option['client']['plural']) ? $option['client']['plural'] : __(
                'Clients',
                'upstream'
            ),
        ],
        'milestones'           => [
            'singular' => isset($option['milestone']['single']) ? $option['milestone']['single'] : __(
                'Milestone',
                'upstream'
            ),
            'plural'   => isset($option['milestone']['plural']) ? $option['milestone']['plural'] : __(
                'Milestones',
                'upstream'
            ),
        ],
        'milestone_categories' => [
            'singular' => isset($option['milestone_categories']['single']) ? $option['milestone_categories']['single'] : __(
                'Milestone Category',
                'upstream'
            ),
            'plural'   => isset($option['milestone_categories']['plural']) ? $option['milestone_categories']['plural'] : __(
                'Milestone Categories',
                'upstream'
            ),
        ],
        'tasks'                => [
            'singular' => isset($option['task']['single']) ? $option['task']['single'] : __('Task', 'upstream'),
            'plural'   => isset($option['task']['plural']) ? $option['task']['plural'] : __('Tasks', 'upstream'),
        ],
        'bugs'                 => [
            'singular' => isset($option['bug']['single']) ? $option['bug']['single'] : __('Bug', 'upstream'),
            'plural'   => isset($option['bug']['plural']) ? $option['bug']['plural'] : __('Bugs', 'upstream'),
        ],
        'files'                => [
            'singular' => isset($option['file']['single']) ? $option['file']['single'] : __('File', 'upstream'),
            'plural'   => isset($option['file']['plural']) ? $option['file']['plural'] : __('Files', 'upstream'),
        ],
        'discussion'           => [
            'singular' => isset($option['discussion']['single']) ? $option['discussion']['single'] : __('Discussion',
                'upstream'),
            'plural'   => isset($option['discussion']['plural']) ? $option['discussion']['plural'] : __('Discussions',
                'upstream'),
        ],
    ];

    return apply_filters('upstream_default_labels', $defaults);
}

/**
 * Get Project Labels
 *
 * @param bool $lowercase
 *
 * @return string $defaults['singular'] Singular label
 * @since 1.0.0
 *
 */
function upstream_project_label($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['projects']['singular']) : $defaults['projects']['singular'];

    return __($label, 'upstream');
}

function upstream_project_label_plural($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['projects']['plural']) : $defaults['projects']['plural'];

    return __($label, 'upstream');
}

/**
 * Get Client Label
 *
 * @param bool $lowercase
 *
 * @return string $defaults['singular'] Singular label
 * @since 1.0.0
 *
 */
function upstream_client_label($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['clients']['singular']) : $defaults['clients']['singular'];

    return __($label, 'upstream');
}

function upstream_client_label_plural($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['clients']['plural']) : $defaults['clients']['plural'];

    return __($label, 'upstream');
}

/**
 * Get Milestone Label
 *
 * @param bool $lowercase
 *
 * @return string $defaults['singular'] Singular label
 * @since 1.0.0
 *
 */
function upstream_milestone_label($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['milestones']['singular']) : $defaults['milestones']['singular'];

    return __($label, 'upstream');
}

function upstream_milestone_label_plural($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['milestones']['plural']) : $defaults['milestones']['plural'];

    return __($label, 'upstream');
}

/**
 * Get Milestone Category Label
 *
 * @param bool $lowercase
 *
 * @return string $defaults['singular'] Singular label
 * @since 1.0.0
 *
 */
function upstream_milestone_category_label($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['milestone_categories']['singular']) : $defaults['milestone_categories']['singular'];

    return __($label, 'upstream');
}

function upstream_milestone_category_label_plural($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['milestone_categories']['plural']) : $defaults['milestone_categories']['plural'];

    return __($label, 'upstream');
}

/**
 * Get Task Label
 *
 * @param bool $lowercase
 *
 * @return string $defaults['singular'] Singular label
 * @since 1.0.0
 *
 */
function upstream_task_label($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['tasks']['singular']) : $defaults['tasks']['singular'];

    return __($label, 'upstream');
}

function upstream_task_label_plural($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['tasks']['plural']) : $defaults['tasks']['plural'];

    return __($label, 'upstream');
}

/**
 * Get Bug Label
 *
 * @param bool $lowercase
 *
 * @return string $defaults['singular'] Singular label
 * @since 1.0.0
 *
 */
function upstream_bug_label($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['bugs']['singular']) : $defaults['bugs']['singular'];

    return __($label, 'upstream');
}

function upstream_bug_label_plural($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['bugs']['plural']) : $defaults['bugs']['plural'];

    return __($label, 'upstream');
}

/**
 * Get file Label
 *
 * @param bool $lowercase
 *
 * @return string $defaults['singular'] Singular label
 * @since 1.0.0
 *
 */
function upstream_file_label($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['files']['singular']) : $defaults['files']['singular'];

    return __($label, 'upstream');
}

function upstream_file_label_plural($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['files']['plural']) : $defaults['files']['plural'];

    return __($label, 'upstream');
}

/**
 * Get discussion Label
 *
 * @param bool $lowercase
 *
 * @return string $defaults['singular'] Singular label
 * @since 1.0.0
 *
 */
function upstream_discussion_label($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['discussion']['singular']) : $defaults['discussion']['singular'];

    return $label;
}

function upstream_discussion_label_plural($lowercase = false)
{
    $defaults = upstream_get_default_labels();

    $label = ($lowercase) ? strtolower($defaults['discussion']['plural']) : $defaults['discussion']['plural'];

    return $label;
}

/**
 * Change default "Enter title here" input
 *
 * @param string $title Default title placeholder text
 *
 * @return string $title New placeholder text
 * @since 1.0.0
 *
 */
function upstream_change_default_title($title)
{
    $screen = get_current_screen();

    switch ($screen->post_type) {
        case 'project':
            $label = upstream_project_label();
            $title = sprintf(__('Enter %s name here', 'upstream'), $label);
            break;
        case 'client':
            $label = upstream_client_label();
            $title = sprintf(__('Enter %s name here', 'upstream'), $label);
            break;

    }

    return $title;
}

add_filter('enter_title_here', 'upstream_change_default_title');

/**
 * Get the singular and plural labels for a project taxonomy
 *
 * @param string $taxonomy The Taxonomy to get labels for
 *
 * @return array            Associative array of labels (name = plural)
 * @since  1.0.0
 *
 */
function upstream_get_taxonomy_labels($taxonomy = 'project_category')
{
    $allowed_taxonomies = apply_filters('upstream_allowed_project_taxonomies', ['project_category']);

    if ( ! in_array($taxonomy, $allowed_taxonomies)) {
        return false;
    }

    $labels   = [];
    $taxonomy = get_taxonomy($taxonomy);

    if (false !== $taxonomy) {
        $singular = $taxonomy->labels->singular_name;
        $name     = $taxonomy->labels->name;

        $labels = [
            'name'          => $name,
            'singular_name' => $singular,
        ];
    }

    return apply_filters('upstream_get_taxonomy_labels', $labels, $taxonomy);
}


/**
 * Updated Messages
 *
 * Returns an array of with all updated messages.
 *
 * @param array $messages Post updated message
 *
 * @return  array $messages New post updated messages
 * @since   1.0.0
 *
 */
function upstream_updated_messages($messages)
{
    global $post_ID;

    $postURL          = get_permalink($post_ID);
    $anchorTagOpening = '<a href="' . $postURL . '" target="_blank" rel="noopener noreferrer">';
    $anchorTagClosing = '</a>';

    $postTypeLabelProject = upstream_project_label();
    $postTypeLabelClient  = upstream_client_label();

    $messages['project'] = [
        1 => sprintf(
            __('%2$s updated. %1$sView %2$s%3$s', 'upstream'),
            $anchorTagOpening,
            $postTypeLabelProject,
            $anchorTagClosing
        ),
        4 => sprintf(
            __('%2$s updated. %1$sView %2$s%3$s', 'upstream'),
            $anchorTagOpening,
            $postTypeLabelProject,
            $anchorTagClosing
        ),
        6 => sprintf(
            __('%2$s published. %1$sView %2$s%3$s', 'upstream'),
            $anchorTagOpening,
            $postTypeLabelProject,
            $anchorTagClosing
        ),
        7 => sprintf(
            __('%2$s saved. %1$sView %2$s%3$s', 'upstream'),
            $anchorTagOpening,
            $postTypeLabelProject,
            $anchorTagClosing
        ),
        8 => sprintf(
            __('%2$s submitted. %1$sView %2$s%3$s', 'upstream'),
            $anchorTagOpening,
            $postTypeLabelProject,
            $anchorTagClosing
        ),
    ];

    $messages['client'] = [
        1 => sprintf(__('%1$s updated.', 'upstream'), $postTypeLabelClient),
        4 => sprintf(__('%1$s updated.', 'upstream'), $postTypeLabelClient),
        6 => sprintf(__('%1$s published.', 'upstream'), $postTypeLabelClient),
        7 => sprintf(__('%1$s saved.', 'upstream'), $postTypeLabelClient),
        8 => sprintf(__('%1$s submitted.', 'upstream'), $postTypeLabelClient),
    ];

    return $messages;
}

add_filter('post_updated_messages', 'upstream_updated_messages');

/**
 * Updated bulk messages
 *
 * @param array $bulk_messages Post updated messages
 * @param array $bulk_counts   Post counts
 *
 * @return  array $bulk_messages New post updated messages
 * @since 2.3
 *
 */
function upstream_bulk_updated_messages($bulk_messages, $bulk_counts)
{
    $itemsUpdatedCount   = (int)$bulk_counts['updated'];
    $itemsLockedCount    = (int)$bulk_counts['locked'];
    $itemsDeletedCount   = (int)$bulk_counts['deleted'];
    $itemsTrashedCount   = (int)$bulk_counts['trashed'];
    $itemsUntrashedCount = (int)$bulk_counts['untrashed'];

    $postTypeClientLabelSingular = upstream_client_label();
    $postTypeClientLabelPlural   = upstream_client_label_plural();

    $postTypeProjectLabelSingular = upstream_project_label();
    $postTypeProjectLabelPlural   = upstream_project_label_plural();

    $bulk_messages['client'] = [
        'updated'   => sprintf(
            _n('%1$s %2$s updated.', '%1$s %3$s updated.', $itemsUpdatedCount, 'upstream'),
            $itemsUpdatedCount,
            $postTypeClientLabelSingular,
            $postTypeClientLabelPlural
        ),
        'locked'    => sprintf(
            _n(
                '%1$s %2$s not updated, somebody is editing it.',
                '%1$s %3$s not updated, somebody is editing them.',
                $itemsLockedCount,
                'upstream'
            ),
            $itemsLockedCount,
            $postTypeClientLabelSingular,
            $postTypeClientLabelPlural
        ),
        'deleted'   => sprintf(
            _n(
                '%1$s %2$s permanently deleted.',
                '%1$s %3$s permanently deleted.',
                $itemsDeletedCount,
                'upstream'
            ),
            $itemsDeletedCount,
            $postTypeClientLabelSingular,
            $postTypeClientLabelPlural
        ),
        'trashed'   => sprintf(
            _n(
                '%1$s %2$s moved to the Trash.',
                '%1$s %3$s moved to the Trash.',
                $itemsTrashedCount,
                'upstream'
            ),
            $itemsTrashedCount,
            $postTypeClientLabelSingular,
            $postTypeClientLabelPlural
        ),
        'untrashed' => sprintf(
            _n(
                '%1$s %2$s restored from the Trash.',
                '%1$s %3$s restored from the Trash.',
                $itemsUntrashedCount,
                'upstream'
            ),
            $itemsUntrashedCount,
            $postTypeClientLabelSingular,
            $postTypeClientLabelPlural
        ),
    ];

    $bulk_messages['project'] = [
        'updated'   => sprintf(
            _n('%1$s %2$s updated.', '%1$s %3$s updated.', $itemsUpdatedCount, 'upstream'),
            $itemsUpdatedCount,
            $postTypeProjectLabelSingular,
            $postTypeProjectLabelPlural
        ),
        'locked'    => sprintf(
            _n(
                '%1$s %2$s not updated, somebody is editing it.',
                '%1$s %3$s not updated, somebody is editing them.',
                $itemsLockedCount,
                'upstream'
            ),
            $itemsLockedCount,
            $postTypeProjectLabelSingular,
            $postTypeProjectLabelPlural
        ),
        'deleted'   => sprintf(
            _n(
                '%1$s %2$s permanently deleted.',
                '%1$s %3$s permanently deleted.',
                $itemsDeletedCount,
                'upstream'
            ),
            $itemsDeletedCount,
            $postTypeProjectLabelSingular,
            $postTypeProjectLabelPlural
        ),
        'trashed'   => sprintf(
            _n(
                '%1$s %2$s moved to the Trash.',
                '%1$s %3$s moved to the Trash.',
                $itemsTrashedCount,
                'upstream'
            ),
            $itemsTrashedCount,
            $postTypeProjectLabelSingular,
            $postTypeProjectLabelPlural
        ),
        'untrashed' => sprintf(
            _n(
                '%1$s %2$s restored from the Trash.',
                '%1$s %3$s restored from the Trash.',
                $itemsUntrashedCount,
                'upstream'
            ),
            $itemsUntrashedCount,
            $postTypeProjectLabelSingular,
            $postTypeProjectLabelPlural
        ),
    ];

    return $bulk_messages;
}

add_filter('bulk_post_updated_messages', 'upstream_bulk_updated_messages', 10, 2);

/**
 * Display UpStream notices-errors near the top of admin pages.
 *
 * @since   1.9.0
 */
function upstream_admin_notices_errors()
{
    $errors = get_transient('upstream_errors');

    if ( ! empty($errors)): ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php echo $errors; ?>
            </p>
        </div>
        <?php
        delete_transient('upstream_errors');
    endif;
}

add_filter('admin_notices', 'upstream_admin_notices_errors');
