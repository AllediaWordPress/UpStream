<?php
// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

$project_id = (int)upstream_post_id();
$project = getUpStreamProjectDetailsById($project_id);

$projectTimeframe = "";
$projectDateStartIsNotEmpty = $project->dateStart > 0;
$projectDateEndIsNotEmpty = $project->dateEnd > 0;
if ($projectDateStartIsNotEmpty || $projectDateEndIsNotEmpty) {
    if (!$projectDateEndIsNotEmpty) {
        $projectTimeframe = '<i class="text-muted">' . __(
                'Start Date',
                'upstream'
            ) . ': </i>' . esc_html(upstream_format_date($project->dateStart));
    } elseif (!$projectDateStartIsNotEmpty) {
        $projectTimeframe = '<i class="text-muted">' . __(
                'End Date',
                'upstream'
            ) . ': </i>' . esc_html(upstream_format_date($project->dateEnd));
    } else {
        $projectTimeframe = esc_html(upstream_format_date($project->dateStart) . ' - ' . upstream_format_date($project->dateEnd));
    }
}

$pluginOptions = get_option('upstream_general');
$collapseDetails = isset($pluginOptions['collapse_project_details']) && (bool)$pluginOptions['collapse_project_details'] === true;
$collapseDetailsState = \UpStream\Frontend\getSectionCollapseState('details');

if (!is_null($collapseDetailsState)) {
    $collapseDetails = $collapseDetailsState === 'closed';
}

$isClientsDisabled = is_clients_disabled();
?>

<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
    <div class="x_panel details-panel" data-section="details">
        <div class="x_title">
            <h2>
                <i class="fa fa-bars sortable_handler"></i>
                <?php printf(
                    '<i class="fa fa-info-circle"></i> ' . __('%s Details', 'upstream'),
                    esc_html(upstream_project_label())
                ); ?>
                <?php do_action('upstream:frontend.project.details.after_title', $project); ?>
            </h2>
            <ul class="nav navbar-right panel_toolbox">
                <li>
                    <a class="collapse-link">
                        <i class="fa fa-chevron-<?php echo $collapseDetails ? 'down' : 'up'; ?>"></i>
                    </a>
                </li>
            </ul>
            <div class="clearfix"></div>
        </div>
        <div class="x_content" style="display: <?php echo $collapseDetails ? 'none' : 'block'; ?>;">
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-2 col-lg-2">
                    <p class="title"><?php esc_html_e('ID', 'upstream'); ?></p>
                    <span><?php echo $project_id; ?></span>
                </div>

                <?php if (!empty($projectTimeframe)): ?>
                    <div class="col-xs-12 col-sm-6 col-md-2 col-lg-2">
                        <p class="title"><?php esc_html_e('Timeframe', 'upstream'); ?></p>
                        <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project_id, null, 0, 'start', UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                            upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project_id, null, 0, 'end', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>
                        <span><?php echo $projectTimeframe; /* already sanitized */ ?></span>
                        <?php else: ?>
                            <span class="label up-o-label" style="background-color:#666;color:#fff">(hidden)</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$isClientsDisabled && $project->client_id > 0): ?>
                    <div class="col-xs-12 col-sm-6 col-md-2 col-lg-2">
                        <p class="title"><?php echo esc_html(upstream_client_label()); ?></p>
                        <span><?php echo $project->client_id > 0 && !empty($project->clientName) ? esc_html($project->clientName) : '<i class="text-muted">(' . esc_html__(
                                    'none',
                                    'upstream'
                                ) . ')</i>'; ?></span>
                    </div>
                <?php endif; ?>

                <div class="col-xs-12 col-sm-6 col-md-2 col-lg-2">
                    <p class="title"><?php esc_html_e('Progress', 'upstream'); ?></p>
                    <span>
                    <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project_id, null, 0, 'progress', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>
                        <?php echo esc_html($project->progress); ?>% <?php esc_html_e('complete', 'upstream'); ?>
                    <?php else: ?>
                        <span class="label up-o-label" style="background-color:#666;color:#fff">(hidden)</span>
                    <?php endif; ?>
                    </span>
                </div>
                <?php if ($project->owner_id > 0): ?>
                    <div class="col-xs-12 col-sm-6 col-md-2 col-lg-2">
                        <p class="title"><?php esc_html_e('Owner', 'upstream'); ?></p>
                        <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project_id, null, 0, 'progress', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>
                            <span><?php echo $project->owner_id > 0 ? upstream_user_avatar($project->owner_id) : '<i class="text-muted">(' . esc_html__(
                                        'none',
                                        'upstream'
                                    ) . ')</i>'; ?></span>
                        <?php else: ?>
                            <span class="label up-o-label" style="background-color:#666;color:#fff">(hidden)</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$isClientsDisabled && $project->client_id > 0): ?>
                    <div class="col-xs-12 col-sm-6 col-md-2 col-lg-2">
                        <p class="title"><?php printf(__('%s Users', 'upstream'), esc_html(upstream_client_label())); ?></p>
                        <?php if (is_array($project->clientUsers) && count($project->clientUsers) > 0): ?>
                            <?php upstream_output_client_users() ?>
                        <?php else: ?>
                            <span><i class="text-muted">(<?php esc_html_e('none', 'upstream'); ?>)</i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="col-xs-12 col-sm-6 col-md-2 col-lg-2">
                    <p class="title"><?php esc_html_e('Members', 'upstream'); ?></p>
                    <?php upstream_output_project_members(); ?>
                </div>

                <?php do_action('upstream:frontend.project.render_details', $project->id); ?>
            </div>
            <?php if (!empty($project->description)): ?>
                <div>
                    <p class="title"><?php esc_html_e('Description', 'upstream'); ?></p>
                    <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project_id, null, 0, 'description', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>
                        <blockquote
                                style="font-size: 1em;"><?php echo upstream_esc_w( nl2br(htmlspecialchars_decode($project->description))); ?></blockquote>
                    <?php else: ?>
                        <span class="label up-o-label" style="background-color:#666;color:#fff">(hidden)</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
