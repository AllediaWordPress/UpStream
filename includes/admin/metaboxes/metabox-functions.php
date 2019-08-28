<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


/* ======================================================================================
                                        METABOX FIELD VALIDATION
   ====================================================================================== */
/*
 * CMB2 js validation for "required" fields
 * Uses js to validate CMB2 fields that have the 'data-validation' attribute set to 'required'
 */
/**
 * Documentation in the wiki:
 *
 * @link https://github.com/WebDevStudios/CMB2/wiki/Plugin-code-to-add-JS-validation-of-%22required%22-fields
 */
function upstream_form_do_js_validation($post_id, $cmb)
{
    static $added = false;
    // Only add this to the page once (not for every metabox)
    if ($added) {
        return;
    }
    $added = true; ?>

    <script type="text/javascript">

        jQuery(document).ready(function ($) {

            $form = $(document.getElementById('post'));
            $htmlbody = $('html, body');
            $toValidate = $('[data-validation]');

            if (!$toValidate.length) {
                return;
            }

            function checkValidation (evt) {

                var labels = [];
                var $first_error_row = null;
                var $row = null;

                function add_required ($row, $this) {

                    setTimeout(function () {
                        $row.css({
                            'box-shadow': '0 0 2px #dc3232',
                            'border-right': '4px solid #dc3232'
                        });
                        $this.css({'border-color': '#dc3232'});
                    }, 500);

                    $first_error_row = $first_error_row ? $first_error_row : $this;

                    // if it has been deleted dynamically
                    if ($(document).find($first_error_row).length == 0) {
                        $first_error_row = null;
                    }

                }

                function remove_required ($row, $this) {
                    $row.css({background: ''});
                }

                $toValidate.each(function () {

                    var $this = $(this);
                    var val = $this.val();

                    if ($this.parents('.cmb-repeatable-grouping')) {
                        $item = $this.parents('.cmb-repeatable-grouping');
                        $row = $item.find('.cmb-group-title');

                        if ($item.is(':hidden')) {
                            return true;
                        }
                    }

                    if ($this.is('[type="button"]') || $this.is('.cmb2-upload-file-id')) {
                        return true;
                    }

                    if ('required' === $this.data('validation')) {

                        if ($row.is('.cmb-type-file-list')) {
                            var has_LIs = $row.find('ul.cmb-attach-list li').length > 0;
                            if (!has_LIs) {
                                add_required($row, $this);
                            } else {
                                remove_required($row, $this);
                            }
                        } else {
                            if (!val) {
                                add_required($row, $this);
                            } else {
                                remove_required($row, $this);
                            }
                        }
                    }

                });

                if ($first_error_row) {
                    evt.preventDefault();

                    $('#major-publishing-actions .notice').remove();
                    $('#major-publishing-actions').append($('<div class="notice notice-error"><?php _e(
                        'Missing some required fields',
                        'upstream'
                    ) ?></div>').hide().fadeIn(500));

                    $htmlbody.delay(500).animate({
                        scrollTop: ($first_error_row.offset().top - 100)
                    }, 500);
                } else {
                    $form.find('input, textarea, button, select').prop({'disabled': false, 'readonly': false});
                }

            }

            $form.on('submit', checkValidation);

        });
    </script>

    <?php
}

add_action('cmb2_after_form', 'upstream_form_do_js_validation', 10, 2);

/* ======================================================================================
                                        OVERVIEW
   ====================================================================================== */

/**
 * Returns data for the overview section.
 *
 * @return
 */
function upstream_output_overview_counts($field_args, $field)
{
    $project_id         = $field->object_id ? (int)$field->object_id : upstream_post_id();
    $user_id            = (int)get_current_user_id();
    $itemTypeMetaPrefix = "_upstream_project_";
    $itemType           = str_replace($itemTypeMetaPrefix, "", $field_args['id']);

    $isDisabled = (string)get_post_meta($project_id, $itemTypeMetaPrefix . 'disable_' . $itemType, true);
    if ($isDisabled === "on") {
        return;
    }

    $countMine = 0;
    $countOpen = 0;

    $counter = new Upstream_Counter($project_id);

    $rowset = $counter->getItemsOfType($itemType);

    if ($itemType === "milestones") {
        if ( ! empty($rowset)) {
            foreach ($rowset as $row) {
                if (isset($row['assigned_to'])) {
                    $assignedTo = $row['assigned_to'];

                    if (
                        (is_array($assignedTo) && in_array($user_id, $assignedTo))
                        || ((int)$row['assigned_to'] === $user_id)
                    ) {
                        $countMine++;
                    }
                }
            }
        }

        $countOpen = count((array)$rowset);
    } elseif (is_array($rowset) && count($rowset) > 0) {
        $options  = get_option('upstream_' . $itemType);
        $statuses = isset($options['statuses']) ? $options['statuses'] : [];

        $statuses = wp_list_pluck($statuses, 'type', 'id');

        foreach ($rowset as $row) {
            if (isset($row['assigned_to'])) {
                $assignedTo = $row['assigned_to'];

                if (
                    (is_array($assignedTo) && in_array($user_id, $assignedTo))
                    || ((int)$row['assigned_to'] === $user_id)
                ) {
                    $countMine++;
                }
            }

            if (
                ! isset($row['status'])
                || empty($row['status'])
                || (
                    isset($statuses[$row['status']]) && $statuses[$row['status']] === "open"
                )
            ) {
                $countOpen++;
            }
        }
    } ?>
    <div class="counts <?php echo esc_attr($itemType); ?>">
        <h4>
            <span class="count open total"><?php echo $countOpen; ?></span> <?php _e('Open', 'upstream'); ?>
        </h4>
        <h4>
            <span
                    class="count open<?php echo esc_attr($countMine > 0 ? ' mine' : ''); ?>"><?php echo (int)$countMine ?></span> <?php _e(
                'Mine',
                'upstream'
            ); ?>
        </h4>
    </div>
    <?php
}

/* ======================================================================================
                                        ACTIVITY
   ====================================================================================== */

/**
 * Returns the buttons for the activity section
 *
 * @return
 */
function upstream_activity_buttons($field_args, $field)
{

    // active class
    $class = ' button-primary';
    $_10   = '';
    $_20   = '';
    $_all  = '';

    if ( ! isset($_GET['activity_items']) || (isset($_GET['activity_items']) && $_GET['activity_items'] == '10')) {
        $_10 = $class;
    }
    if (isset($_GET['activity_items']) && $_GET['activity_items'] == '20') {
        $_20 = $class;
    }
    if (isset($_GET['activity_items']) && $_GET['activity_items'] == 'all') {
        $_all = $class;
    }

    $edit_buttons = '<div class="button-wrap">';
    $edit_buttons .= '<a class="button button-small' . esc_attr($_10) . '" href="' . esc_url(add_query_arg(
            'activity_items',
            '10'
        )) . '" >' . __('Last 10', 'upstream') . '</a> ';
    $edit_buttons .= '<a class="button button-small' . esc_attr($_20) . '" href="' . esc_url(add_query_arg(
            'activity_items',
            '20'
        )) . '" >' . __('Last 20', 'upstream') . '</a> ';
    $edit_buttons .= '<a class="button button-small' . esc_attr($_all) . '" href="' . esc_url(add_query_arg(
            'activity_items',
            'all'
        )) . '" >' . __('View All', 'upstream') . '</a> ';
    $edit_buttons .= '</div>';

    return $edit_buttons;
}

/**
 * Returns data for the activity section.
 *
 * @return
 */
function upstream_output_activity($field_args, $field)
{
    $activity = \UpStream\Factory::getActivity();

    return $activity->get_activity($field->object_id);
}

/* ======================================================================================
                                        MILESTONES
   ====================================================================================== */
/**
 * Outputs some hidden data in the metabox so we can use it dynamically
 *
 * @return
 */
function upstream_admin_output_milestone_hidden_data($field_args, $field)
{
    global $post;

    // get the current saved milestones
    $milestones = \UpStream\Milestones::getInstance()->getMilestonesFromProject($post->ID);

    echo '<ul class="hidden milestones">';
    foreach ($milestones as $milestone) {
        $milestone = \UpStream\Factory::getMilestone($milestone);

        echo '<li>
            <span class="title">' . esc_html($milestone->getName()) . '</span>
            <span class="color">' . esc_html($milestone->getColor()) . '</span>';

        $progress = $milestone->getProgress();
        if ( ! empty($progress)) {
            // if we have progress
            echo '<span class="m-progress">' . $progress . '</span>';
        }
        echo '</li>';

        unset($milestone);
    }
    echo '</ul>';
}

/**
 * Returns the current saved milestones.
 * For use in dropdowns.
 *
 * @param $field
 *
 * @return array
 */
function upstream_admin_get_project_milestones($field)
{
    $projectMilestones = \UpStream\Milestones::getInstance()->getMilestonesFromProject($field->object_id);

    $data = [];

    if (count($projectMilestones) > 0) {
        foreach ($projectMilestones as $milestone) {
            $milestone = \UpStream\Factory::getMilestone($milestone);

            $data[$milestone->getId()] = $milestone->getName();

            unset($milestone);
        }
    }

    return $data;
}

/* ======================================================================================
                                        TASKS
   ====================================================================================== */
/**
 * Returns the task status names as set in the options.
 * Used in the Status dropdown within a task.
 *
 * @return array
 */
function upstream_admin_get_task_statuses()
{
    $option   = get_option('upstream_tasks');
    $statuses = isset($option['statuses']) ? $option['statuses'] : '';
    $array    = [];
    if ($statuses) {
        foreach ($statuses as $status) {
            $array[$status['id']] = $status['name'];
        }
    }

    return $array;
}

/**
 * Outputs some hidden data so we can use it dynamically
 *
 * @return
 */
function upstream_admin_output_task_hidden_data()
{
    $option   = get_option('upstream_tasks');
    $statuses = isset($option['statuses']) ? $option['statuses'] : '';
    if ($statuses) {
        echo '<ul class="hidden statuses">';
        foreach ($statuses as $status) {
            echo '<li>
                <span class="status">' . esc_html($status['name']) . '</span>
                <span class="color">' . esc_html($status['color']) . '</span>
                </li>';
        }
        echo '</ul>';
    }
}


/* ======================================================================================
                                        BUGS
   ====================================================================================== */
/**
 * Returns the bug status names as set in the options.
 * Used in the Status dropdown within a bug.
 *
 * @return
 */
function upstream_admin_get_bug_statuses()
{
    $option   = get_option('upstream_bugs');
    $statuses = isset($option['statuses']) ? $option['statuses'] : '';
    $array    = [];
    if ($statuses) {
        foreach ($statuses as $status) {
            if (isset($status['name'])) {
                $array[$status['id']] = $status['name'];
            }
        }
    }

    return $array;
}

/**
 * Returns the bug severity names as set in the options.
 * Used in the Severity dropdown within a bug.
 *
 * @return
 */
function upstream_admin_get_bug_severities()
{
    $option     = get_option('upstream_bugs');
    $severities = isset($option['severities']) ? $option['severities'] : '';
    $array      = [];
    if ($severities) {
        foreach ($severities as $severity) {
            if (isset($severity['name'])) {
                $array[$severity['id']] = $severity['name'];
            }
        }
    }

    return $array;
}

/**
 * Outputs some hidden data in the metabox so we can use it dynamically
 *
 * @return
 */
function upstream_admin_output_bug_hidden_data()
{
    $option     = get_option('upstream_bugs');
    $statuses   = isset($option['statuses']) ? $option['statuses'] : '';
    $severities = isset($option['severities']) ? $option['severities'] : '';
    if ($statuses) {
        echo '<ul class="hidden statuses">';
        foreach ($statuses as $status) {
            echo '<li>
                <span class="status">' . esc_html($status['name']) . '</span>
                <span class="color">' . esc_html($status['color']) . '</span>
            </li>';
        }
        echo '</ul>';
    }
    if ($severities) {
        echo '<ul class="hidden severities">';
        foreach ($severities as $severity) {
            echo '<li>
                <span class="severity">' . esc_html($severity['name']) . '</span>
                <span class="color">' . esc_html($severity['color']) . '</span>
            </li>';
        }
        echo '</ul>';
    }
}

/* ======================================================================================
                                        DISCUSSION
   ====================================================================================== */

function upstreamRenderCommentsBox(
    $item_id = "",
    $itemType = "project",
    $project_id = 0,
    $renderControls = true,
    $returnAsHtml = false
) {
    $project_id = (int)$project_id;
    if ($project_id <= 0) {
        $project_id = upstream_post_id();
        if ($project_id <= 0) {
            return;
        }
    }

    if (is_object($itemType)) {
        $itemType = "project";
    }

    $itemType = trim(strtolower($itemType));
    if (
        ! in_array($itemType, ['project', 'milestone', 'task', 'bug', 'file'])
        || ($itemType !== "project" && empty($item_id))
    ) {
        return;
    }

    $rowsetUsers = get_users();
    $users       = [];
    foreach ($rowsetUsers as $user) {
        $users[(int)$user->ID] = (object)[
            'id'     => (int)$user->ID,
            'name'   => $user->display_name,
            'avatar' => getUserAvatarURL($user->ID),
        ];
    }
    unset($rowsetUsers);

    $user                     = wp_get_current_user();
    $userHasAdminCapabilities = isUserEitherManagerOrAdmin();
    $userCanComment           = ! $userHasAdminCapabilities ? user_can($user, 'publish_project_discussion') : true;
    $userCanModerate          = ! $userHasAdminCapabilities ? user_can($user, 'moderate_comments') : true;
    $userCanDelete            = ! $userHasAdminCapabilities ? ($userCanModerate || user_can(
            $user,
            'delete_project_discussion'
        )) : true;

    $commentsStatuses = ['approve'];
    if ($userHasAdminCapabilities || $userCanModerate) {
        $commentsStatuses[] = 'hold';
    }

    $queryParams = [
        'post_id' => $project_id,
        'orderby' => 'comment_date_gmt',
        'order'   => 'DESC',
        'type'    => '',
        'status'  => $commentsStatuses,
    ];

    if ($itemType === "project") {
        $queryParams['meta_key']   = "type";
        $queryParams['meta_value'] = $itemType;
    } else {
        $queryParams['meta_query'] = [
            'relation' => 'AND',
            [
                'key'   => 'type',
                'value' => $itemType,
            ],
            [
                'key'   => 'id',
                'value' => $item_id,
            ],
        ];
    }

    $rowset = (array)get_comments($queryParams);

    $commentsCache = [];
    if (count($rowset) > 0) {
        $dateFormat        = get_option('date_format');
        $timeFormat        = get_option('time_format');
        $theDateTimeFormat = $dateFormat . ' ' . $timeFormat;
        $currentTimestamp  = time();

        foreach ($rowset as $row) {
            $author = $users[(int)$row->user_id];

            $date          = DateTime::createFromFormat('Y-m-d H:i:s', $row->comment_date_gmt);
            $dateTimestamp = $date->getTimestamp();

            $comment = json_decode(json_encode([
                'id'             => (int)$row->comment_ID,
                'parent_id'      => (int)$row->comment_parent,
                'content'        => $row->comment_content,
                'state'          => (int)$row->comment_approved,
                'replies'        => [],
                'created_by'     => $author,
                'created_at'     => [
                    'timestamp' => $dateTimestamp,
                    'utc'       => $row->comment_date_gmt,
                    'localized' => $date->format($theDateTimeFormat),
                    'humanized' => sprintf(
                        _x('%s ago', '%s = human-readable time difference', 'upstream'),
                        human_time_diff($dateTimestamp, $currentTimestamp)
                    ),
                ],
                'currentUserCap' => [
                    'can_reply'    => $userCanComment,
                    'can_moderate' => $userCanModerate,
                    'can_delete'   => $userCanDelete,
                ],
            ]));

            if ($author->id == $user->ID) {
                $comment->currentUserCap->can_delete = true;
            }

            $commentsCache[$comment->id] = $comment;
        }

        foreach ($commentsCache as $comment) {
            if ($comment->parent_id > 0) {
                if (isset($commentsCache[$comment->parent_id])) {
                    $commentsCache[$comment->parent_id]->replies[] = $comment;
                } else {
                    unset($commentsCache[$comment->id]);
                }
            }
        }
    }

    if ($returnAsHtml) {
        ob_start();
    }

    $commentsCacheCount = count($commentsCache);

    if ($commentsCacheCount === 0
        && ! is_admin()
    ) {
        printf('<p data-empty><i class="s-text-color-gray">%s</i></p>', __('none', 'upstream'));
    } ?>

    <div class="c-comments" data-type="<?php echo $itemType; ?>" <?php echo $renderControls ? 'data-nonce' : ''; ?>>
        <?php
        if ($commentsCacheCount > 0) {
            if (is_admin()) {
                foreach ($commentsCache as $comment) {
                    if ($comment->parent_id === 0) {
                        upstream_admin_display_message_item($comment, $commentsCache, $renderControls);
                    }
                }
            } else {
                foreach ($commentsCache as $comment) {
                    if ($comment->parent_id === 0) {
                        upstream_display_message_item($comment, $commentsCache, $renderControls);
                    }
                }
            }
        } ?>
    </div>
    <?php

    if ($returnAsHtml) {
        $contentHtml = ob_get_contents();
        ob_end_clean();

        return $contentHtml;
    }
}

function upstream_admin_display_message_item($comment, $comments = [], $renderControls = true)
{
    global $wp_embed;

    $isApproved              = (int)$comment->state === 1;
    $currentUserCapabilities = (object)[
        'can_reply'    => isset($comment->currentUserCap->can_reply) ? (bool)$comment->currentUserCap->can_reply : false,
        'can_moderate' => isset($comment->currentUserCap->can_moderate) ? (bool)$comment->currentUserCap->can_moderate : false,
        'can_delete'   => isset($comment->currentUserCap->can_delete) ? (bool)$comment->currentUserCap->can_delete : false,
    ]; ?>
    <div class="o-comment s-status-<?php echo $isApproved ? 'approved' : 'unapproved'; ?>"
         id="comment-<?php echo $comment->id; ?>" data-id="<?php echo $comment->id; ?>">
        <div class="o-comment__body">
            <div class="o-comment__body__left">
                <img class="o-comment__user_photo" src="<?php echo $comment->created_by->avatar; ?>" width="30">
                <?php if ( ! $isApproved && $currentUserCapabilities->can_moderate): ?>
                    <div class="u-text-center">
                        <i class="fa fa-eye-slash u-color-gray"
                           title="<?php _e(
                               "This comment and its replies are not visible by regular users.",
                               'upstream'
                           ); ?>" style="margin-top: 2px;"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="o-comment__body__right">
                <div class="o-comment__body__head">
                    <div class="o-comment__user_name"><?php echo $comment->created_by->name; ?></div>
                    <div class="o-comment__reply_info"></div>
                    <div class="o-comment__date"><?php echo $comment->created_at->humanized; ?>&nbsp;<small>
                            (<?php echo $comment->created_at->localized; ?>)
                        </small>
                    </div>
                </div>
                <div
                        class="o-comment__content"><?php echo $wp_embed->autoembed(wpautop($comment->content)); ?></div>
                <div class="o-comment__body__footer">
            <?php
            if ($renderControls) {
                $controls = [];
                if ($currentUserCapabilities->can_moderate) {
                    if ($isApproved) {
                        $controls[0] = [
                            'action' => 'unapprove',
                            'nonce'  => "unapprove_comment",
                            'label'  => __('Unapprove'),
                        ];
                    } else {
                        $controls[2] = [
                            'action' => 'approve',
                            'nonce'  => "approve_comment",
                            'label'  => __('Approve'),
                        ];
                    }
                }

                if ($currentUserCapabilities->can_reply) {
                    $controls[1] = [
                        'action' => 'reply',
                        'nonce'  => "add_comment_reply",
                        'label'  => __('Reply'),
                    ];
                }

                if ($currentUserCapabilities->can_delete) {
                    $controls[] = [
                        'action' => 'trash',
                        'nonce'  => "trash_comment",
                        'label'  => __('Delete'),
                    ];
                }

                if (count($controls) > 0) {
                    foreach ($controls as $control) {
                        printf(
                            '<a href="#" class="o-comment-control" data-action="comment.%s" data-nonce="%s">%s</a>',
                            $control['action'],
                            wp_create_nonce('upstream:project.' . $control['nonce'] . ':' . $comment->id),
                            $control['label']
                        );
                    }
                }
            } ?>
          </div>
            </div>
        </div>
        <div class="o-comment-replies">
        <?php if (isset($comment->replies) && count($comment->replies) > 0): ?>
        <?php foreach ($comment->replies as $commentReply): ?>
          <?php upstream_admin_display_message_item($commentReply, $comments, $renderControls); ?>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php
}

function upstream_display_message_item($comment, $comments = [], $renderControls = true)
{
    global $wp_embed;

    $isApproved              = (int)$comment->state === 1;
    $currentUserCapabilities = (object)[
        'can_reply'    => isset($comment->currentUserCap->can_reply) ? (bool)$comment->currentUserCap->can_reply : false,
        'can_moderate' => isset($comment->currentUserCap->can_moderate) ? (bool)$comment->currentUserCap->can_moderate : false,
        'can_delete'   => isset($comment->currentUserCap->can_delete) ? (bool)$comment->currentUserCap->can_delete : false,
    ]; ?>
    <div class="o-comment s-status-<?php echo $isApproved ? 'approved' : 'unapproved'; ?>"
         id="comment-<?php echo $comment->id; ?>" data-id="<?php echo $comment->id; ?>">
        <div class="o-comment__body">
            <div class="o-comment__body__left">
                <img class="o-comment__user_photo" src="<?php echo $comment->created_by->avatar; ?>" width="30">
                <?php if ( ! $isApproved && $currentUserCapabilities->can_moderate): ?>
                    <div class="u-text-center">
                        <i class="fa fa-eye-slash u-color-gray" data-toggle="tooltip"
                           title="<?php _e(
                               "This comment and its replies are not visible by regular users.",
                               'upstream'
                           ); ?>" style="margin-top: 2px;"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="o-comment__body__right">
                <div class="o-comment__body__head">
                    <div class="o-comment__user_name"><?php echo $comment->created_by->name; ?></div>
                    <div class="o-comment__reply_info"></div>
                    <div class="o-comment__date" data-toggle="tooltip"
                         title="<?php echo $comment->created_at->localized; ?>"><?php echo $comment->created_at->humanized; ?></div>
                </div>
                <div
                        class="o-comment__content"><?php echo $wp_embed->autoembed(wpautop($comment->content)); ?></div>
                <div class="o-comment__body__footer">
            <?php
            if ($renderControls) {
                do_action('upstream:project.comments.comment_controls', $comment);
            } ?>
          </div>
            </div>
        </div>
        <div class="o-comment-replies">
        <?php if (isset($comment->replies) && count($comment->replies) > 0): ?>
        <?php foreach ($comment->replies as $commentReply): ?>
          <?php upstream_display_message_item($commentReply, $comments, $renderControls); ?>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php
}

/* ======================================================================================
                                        GENERAL
   ====================================================================================== */

/*
 * Adds field attributes, and permissions data (mainly) depending on users capabilities.
 * Used heavily in JS to enable/disable fields, groups and delete buttons.
 * Also used to add Avatars to group items.
 */
function upstream_add_field_attributes($args, $field)
{

    /*
     * Add the disabled/readonly attributes to the field
     * if the user does not have permission for that field
     */
    if (isset($args['permissions'])) {
        if ( ! upstream_admin_permissions($args['permissions'])) {
            $field->args['attributes']['disabled']      = 'disabled';
            $field->args['attributes']['readonly']      = 'readonly';
            $field->args['attributes']['data-disabled'] = 'true';
        } else {
            $field->args['attributes']['data-disabled'] = 'false';
        }
    }

    /*
     * Adding/removing attributes for repeatable groups.
     */
    if (isset($field->group->args['repeatable']) && $field->group->args['repeatable'] == '1') :

        $i          = filter_var($field->args['id'], FILTER_SANITIZE_NUMBER_INT);
        $created_by = isset($field->group->value[$i]['created_by']) ? (int)$field->group->value[$i]['created_by'] : 0;
        $assignees  = isset($field->group->value[$i]['assigned_to']) ? $field->group->value[$i]['assigned_to'] : [];
        if ( ! is_array($assignees)) {
            $assignees = (array)$assignees;
        }

        $assignees = array_map('intval', array_unique(array_filter($assignees)));

        $currentUserId = (int)upstream_current_user_id();
        // if the user is assigned to or item is created by
        if ($created_by === $currentUserId
            || in_array($currentUserId, $assignees)
        ) {
            // clear the disabled attributes
            unset($field->args['attributes']['disabled']);
            unset($field->args['attributes']['readonly']);
            $field->args['attributes']['data-disabled'] = 'false';

            // data-owner attribute is used for the delete button
            if ($field->args['_id'] == 'id') {
                $field->args['attributes']['data-owner'] = 'true';
            }
        }
        // to ensure admin and managers can delete anything
        if (upstream_admin_permissions()) {
            $field->args['attributes']['data-owner'] = 'true';
        }

        // add users avatars
        $user_createdby = upstream_user_data($created_by, true);
        if ($field->args['_id'] == 'id') {
            $field->args['attributes']['data-user_created_by']   = $user_createdby['full_name'];
            $field->args['attributes']['data-avatar_created_by'] = $user_createdby['avatar'];

            $field->args['attributes']['data-user_assigned']   = '';
            $field->args['attributes']['data-avatar_assigned'] = '';
            if (count($assignees) > 0) {
                $usersData = [];
                foreach ($assignees as $user_id) {
                    $userData = upstream_user_data($user_id, true);

                    $usersData[] = [
                        'name'   => $userData['full_name'],
                        'avatar' => $userData['avatar'],
                    ];
                }

                $field->args['attributes']['data-assignees'] = json_encode(['data' => $usersData]);
            }
        }

    endif;
}

/**
 * Check if a group is empty.
 *
 * @return
 */
function upstream_empty_group($type)
{
    if (isset($_GET['post_type']) && $_GET['post_type'] != 'project') {
        return '';
    }

    $meta = get_post_meta(upstream_post_id(), "_upstream_project_{$type}", true);
    if ($meta == null || empty($meta) || empty($meta[0])) {
        return '1';
    } else {
        return '';
    }
}

/**
 * Returns the project status names as set in the options.
 * Used in the Status dropdown for the project.
 *
 * @return
 */
function upstream_admin_get_project_statuses()
{
    $option   = get_option('upstream_projects');
    $statuses = isset($option['statuses']) ? $option['statuses'] : '';
    $array    = [];
    if ($statuses) {
        foreach ($statuses as $status) {
            if (isset($status['type'])) {
                $array[$status['id']] = $status['name'];
            }
        }
    }

    return $array;
}

/**
 * Return the array of user roles
 *
 * @return array
 */
function upstream_get_project_roles()
{
    $options = (array)get_option('upstream_general');

    if ( ! isset($options['project_user_roles']) || empty($options['project_user_roles'])) {
        $roles = [
            'upstream_manager',
            'upstream_user',
            'administrator',
        ];
    } else {
        $roles = (array)$options['project_user_roles'];
    }

    $roles = apply_filters('upstream_user_roles_for_projects', $roles);

    return $roles;
}

/**
 * Returns all users with select roles.
 * For use in dropdowns.
 */
function upstream_admin_get_all_project_users()
{
    $projectClientUsers = [];
    $projectId          = upstream_post_id();
    if ($projectId > 0) {
        $projectClientId = (int)get_post_meta($projectId, '_upstream_project_client', true);
        if ($projectClientId > 0) {
            $projectClientUsersIds = array_filter(array_map(
                'intval',
                (array)get_post_meta($projectId, '_upstream_project_client_users', true)
            ));
            if (count($projectClientUsersIds) > 0) {
                $projectClientUsers = (array)get_users([
                    'include' => $projectClientUsersIds,
                    'fields'  => ['ID', 'display_name'],
                ]);
            }
        }
    }

    $roles = upstream_get_project_roles();

    $args = [
        'fields'   => ['ID', 'display_name'],
        'role__in' => $roles,
    ];

    $systemUsers = get_users($args);

    $users = [];

    $rowset = array_merge($systemUsers, $projectClientUsers);
    if (count($rowset) > 0) {
        foreach ($rowset as $user) {
            $users[(int)$user->ID] = $user->display_name;
        }
    }

    return $users;
}

/**
 * Returns array of all clients.
 * For use in dropdowns.
 */
function upstream_admin_get_all_clients()
{
    $args    = [
        'post_type'      => 'client',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'no_found_rows'  => true, // for performance
    ];
    $clients = get_posts($args);
    $array   = ['' => __('Not Assigned', 'upstream')];
    if ($clients) {
        foreach ($clients as $client) {
            $array[$client->ID] = $client->post_title;
        }
    }

    return $array;
}

/**
 * Returns the current saved clients users.
 * For use in dropdowns.
 */
function upstream_admin_get_all_clients_users($field, $client_id = 0)
{
    // Get the currently selected client id.
    if (empty($client_id) || $client_id < 0) {
        $client_id = (int)get_post_meta($field->object_id, '_upstream_project_client', true);
    }

    if ($client_id > 0) {
        $usersList       = [];
        $clientUsersList = array_filter((array)get_post_meta($client_id, '_upstream_new_client_users', true));

        $clientUsersIdsList = [];
        foreach ($clientUsersList as $clientUser) {
            if ( ! empty($clientUser)) {
                $clientUsersIdsList[] = $clientUser['user_id'];
            }
        }

        if (count($clientUsersIdsList) > 0) {
            $rowset = (array)get_users([
                'fields'  => ['ID', 'display_name', 'user_email'],
                'include' => $clientUsersIdsList,
            ]);

            foreach ($rowset as $user) {
                $usersList[(int)$user->ID] = $user->display_name . ' <a href="mailto:' . esc_html($user->user_email) . '" target="_blank"><span class="dashicons dashicons-email-alt"></span></a>';
            }

            return $usersList;
        }
    }

    return [];
}

/**
 * Returns the current saved clients users as an array.
 *
 * @return array
 */
function upstream_get_all_client_users($client_id = 0)
{
    // Get the currently selected client id.
    if (empty($client_id) || $client_id < 0) {
        $client_id = (int)get_post_meta($field->object_id, '_upstream_project_client', true);
    }

    if ($client_id > 0) {
        $usersList       = [];
        $clientUsersList = array_filter((array)get_post_meta($client_id, '_upstream_new_client_users', true));

        $clientUsersIdsList = [];
        foreach ($clientUsersList as $clientUser) {
            if ( ! empty($clientUser)) {
                $clientUsersIdsList[] = $clientUser['user_id'];
            }
        }

        if (count($clientUsersIdsList) > 0) {
            $rowset = (array)get_users([
                'fields'  => ['ID', 'display_name', 'user_email'],
                'include' => $clientUsersIdsList,
            ]);

            foreach ($rowset as $user) {
                $usersList[] = [
                    'id'           => $user->ID,
                    'display_name' => $user->display_name,
                    'email'        => esc_html($user->user_email),
                ];
            }

            return $usersList;
        }
    }

    return [];
}

/**
 * AJAX function to return all selected clients users.
 * For use in dropdowns.
 */
add_action('wp_ajax_upstream_admin_ajax_get_clients_users', 'upstream_admin_ajax_get_clients_users');
function upstream_admin_ajax_get_clients_users()
{
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $client_id  = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;

    if ($project_id <= 0) {
        wp_send_json_error([
            'msg' => __('No project selected', 'upstream'),
        ]);
    } elseif ($client_id <= 0) {
        wp_send_json_error([
            'msg' => __('No client selected', 'upstream'),
        ]);
    } else {
        $field            = new stdClass();
        $field->object_id = $project_id;

        $data = upstream_admin_get_all_clients_users($field, $client_id);

        if (count($data) === 0) {
            wp_send_json_error([
                'msg' => __('No users found', 'upstream'),
            ]);
        } else {
            $output = "";

            $currentProjectClientUsers = (array)get_post_meta($project_id, '_upstream_project_client_users');
            $currentProjectClientUsers = ! empty($currentProjectClientUsers) ? $currentProjectClientUsers[0] : [];

            // Check if the users should be pre-selected by default.

            $userIndex = 0;
            foreach ($data as $user_id => $userName) {
                $checked = select_users_by_default() || in_array($user_id, $currentProjectClientUsers);

                $output .= sprintf(
                    '<li><input type="checkbox" value="%s" id="_upstream_project_client_users%d" name="_upstream_project_client_users[]" class="cmb2-option"%s> <label for="_upstream_project_client_users%2$d">%4$s</label></li>',
                    $user_id,
                    $userIndex,
                    ($checked ? ' checked' : ''),
                    $userName
                );
                $userIndex++;
            }

            wp_send_json_success($output);
        }
    }
}

function upstream_wp_get_clients()
{
    global $wpdb;

    $rowset = $wpdb->get_results(sprintf(
        '
        SELECT `ID`, `post_title`
        FROM `%s`
        WHERE `post_type` = "client"
        AND `post_status` = "publish"',
        $wpdb->prefix . 'posts'
    ));

    $data = [];

    foreach ($rowset as $row) {
        $data[$row->ID] = $row->post_title;
    }

    return $data;
}

/**
 * @return array
 * @throws \UpStream\Exception
 */
function upstream_admin_get_milestone_categories($args = [])
{
    $default = [
        'taxonomy'   => 'upst_milestone_category',
        'fields'     => 'all',
        'hide_empty' => false,
    ];

    $args = wp_parse_args($args, $default);

    $categories = [];
    $terms      = get_terms($args);

    // RSD: hopefully this will work to stop the errors here
    if (isset($terms->errors)) {
        $terms = get_terms( array('taxonomy' => 'upst_milestone_category','hide_empty' => false) );
    }


    if ( ! empty($terms)) {
        if ( ! empty($terms->errors)) {
            //throw new \UpStream\Exception($terms->get_error_message());
        }

        foreach ($terms as $term) {
            $categories[$term->term_id] = $term->name;
        }
    }

    return $categories;
}
