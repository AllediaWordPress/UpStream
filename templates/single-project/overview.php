<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

$user_id          = (int)get_current_user_id();
$project_id       = (int)upstream_post_id();
$progressValue    = upstream_project_progress();
$currentTimestamp = time();
$counter          = \UpStream\Factory::getProjectCounter($project_id);

$areMilestonesEnabled = ! upstream_are_milestones_disabled() && ! upstream_disable_milestones();
if ($areMilestonesEnabled) {
    $milestonesCounts = [
        'open'     => 0,
        'mine'     => 0,
        'overdue'  => 0,
        'finished' => 0,
        'total'    => 0,
    ];

    $all = $counter->getItemsOfType('milestones');

    $milestonesCounts['total'] = count($all);

    if ($milestonesCounts['total'] > 0) {
        $milestonesCounts['mine'] = $counter->getTotalAssignedToCurrentUserOfType('milestones');
        $milestonesCounts['open'] = $counter->getTotalOpenItemsOfType('milestones');

        foreach ($all as $milestone) {
            $progress = isset($milestone['progress']) ? (float)$milestone['progress'] : 0;

            if ($progress < 100) {
                if (isset($milestone['end_date'])
                    && (int)$milestone['end_date'] > 0
                    && (int)$milestone['end_date'] < $currentTimestamp
                ) {
                    $milestonesCounts['overdue']++;
                }
            } else {
                $milestonesCounts['finished']++;
            }
        }
    }
}

$areTasksEnabled = ! upstream_are_tasks_disabled() && ! upstream_disable_tasks();
if ($areTasksEnabled) {
    $tasksCounts = [
        'open'    => 0,
        'mine'    => 0,
        'overdue' => 0,
        'closed'  => 0,
        'total'   => 0,
    ];

    $tasksOptions = get_option('upstream_tasks');
    $tasksMap     = [];
    foreach ($tasksOptions['statuses'] as $task) {
        $tasksMap[$task['id']] = $task['type'];
    }
    unset($tasksOptions);

    $tasks = get_post_meta($project_id, '_upstream_project_tasks');
    $tasks = ! empty($tasks) ? (array)$tasks[0] : [];

    if (isset($tasks[0]) && ! isset($tasks[0]['id'])) {
        $tasks = (array)$tasks[0];
    }

    $tasksCounts['total'] = count($tasks);
    if ($tasksCounts['total'] > 0) {
        foreach ($tasks as $task) {
            if (isset($task['assigned_to'])) {
                $assignedTo = $task['assigned_to'];

                if (is_array($assignedTo) && in_array($user_id, $assignedTo)) {
                    $tasksCounts['mine']++;
                }
            }

            $progress = isset($task['progress']) ? (float)$task['progress'] : 0;
            if ($progress < 100) {
                if (isset($task['status'])
                    && isset($tasksMap[$task['status']])
                    && $tasksMap[$task['status']] === "closed"
                ) {
                    $tasksCounts['closed']++;
                } else {
                    $tasksCounts['open']++;

                    if (isset($task['end_date'])
                        && (int)$task['end_date'] > 0
                        && (int)$task['end_date'] < $currentTimestamp
                    ) {
                        $tasksCounts['overdue']++;
                    }
                }
            } else {
                $tasksCounts['closed']++;
            }
        }
    }
}

$areBugsEnabled = ! upstream_disable_bugs() && ! upstream_are_bugs_disabled();
if ($areBugsEnabled) {
    $bugsCounts = [
        'open'    => 0,
        'mine'    => 0,
        'overdue' => 0,
        'closed'  => 0,
        'total'   => 0,
    ];

    $bugsOptions = get_option('upstream_bugs');
    $bugsMap     = [];
    foreach ($bugsOptions['statuses'] as $bug) {
        $bugsMap[$bug['id']] = $bug['type'];
    }
    unset($bugsOptions);

    $bugs = get_post_meta($project_id, '_upstream_project_bugs');
    $bugs = ! empty($bugs) ? (array)$bugs[0] : [];

    if (isset($bugs[0]) && ! isset($bugs[0]['id'])) {
        $bugs = (array)$bugs[0];
    }

    $bugsCounts['total'] = count($bugs);
    if ($bugsCounts['total'] > 0) {
        foreach ($bugs as $bug) {
            if (isset($bug['assigned_to'])) {
                $assignedTo = $bug['assigned_to'];

                if (is_array($assignedTo) && in_array($user_id, $assignedTo)) {
                    $bugsCounts['mine']++;
                }
            }

            if (isset($bug['status'])
                && ! empty($bug['status'])
                && isset($bugsMap[$bug['status']])
                && $bugsMap[$bug['status']] === "closed"
            ) {
                $bugsCounts['closed']++;
            } else {
                $bugsCounts['open']++;

                if (isset($bug['due_date'])
                    && (int)$bug['due_date'] > 0
                    && (int)$bug['due_date'] < $currentTimestamp
                ) {
                    $bugsCounts['overdue']++;
                }
            }
        }
    }
}
?>

<div class="col-xs-12 col-sm-12 col-md-12 col-lg-7 c-upstream-overview">
    <?php if ($areMilestonesEnabled || $areTasksEnabled || $areBugsEnabled): ?>
        <?php if ($areMilestonesEnabled): ?>
            <div class="hidden-xs hidden-sm col-md-4 col-lg-4" style="min-width: 185px;">
                <div class="panel panel-default" style="margin-bottom: 10px;">
                    <div class="panel-body" style="display: flex; position: relative;">
                        <div data-toggle="tooltip" title="<?php _e('Open', 'upstream'); ?>">
                            <span class="label label-primary"><?php echo $milestonesCounts['open']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Assigned to me', 'upstream'); ?>">
                            <span class="label label-info"><?php echo $milestonesCounts['mine']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Overdue', 'upstream'); ?>">
                            <span class="label label-danger"><?php echo $milestonesCounts['overdue']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Completed', 'upstream'); ?>">
                            <span class="label label-success"><?php echo $milestonesCounts['finished']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Total', 'upstream'); ?>">
                            <span class="label"
                                  style="background-color: #ecf0f1; color: #3A4E66;"><?php echo $milestonesCounts['total']; ?></span>
                        </div>
                        <i class="fa fa-flag fa-2x" data-toggle="tooltip"
                           title="<?php printf(
                               '%s %s',
                               upstream_milestone_label_plural(),
                               __('Overview', 'upstream')
                           ); ?>"
                           style="position: absolute; color: #ECF0F1; right: 8px; margin-top: -2px"></i>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($areTasksEnabled): ?>
            <div class="hidden-xs hidden-sm col-md-4 col-lg-4" style="min-width: 185px;">
                <div class="panel panel-default" style="margin-bottom: 10px;">
                    <div class="panel-body" style="display: flex; position: relative;">
                        <div data-toggle="tooltip" title="<?php _e('Open', 'upstream'); ?>">
                            <span class="label label-primary"><?php echo $tasksCounts['open']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Assigned to me', 'upstream'); ?>">
                            <span class="label label-info"><?php echo $tasksCounts['mine']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Overdue', 'upstream'); ?>">
                            <span class="label label-danger"><?php echo $tasksCounts['overdue']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Closed', 'upstream'); ?>">
                            <span class="label label-success"><?php echo $tasksCounts['closed']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Total', 'upstream'); ?>">
                            <span class="label"
                                  style="background-color: #ecf0f1; color: #3A4E66;"><?php echo $tasksCounts['total']; ?></span>
                        </div>
                        <i class="fa fa-wrench fa-2x" data-toggle="tooltip"
                           title="<?php printf(
                               '%s %s',
                               upstream_task_label_plural(),
                               __('Overview', 'upstream')
                           ); ?>"
                           style="position: absolute; color: #ECF0F1; right: 8px; margin-top: -2px"></i>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($areBugsEnabled): ?>
            <div class="hidden-xs hidden-sm col-md-4 col-lg-4" style="min-width: 185px;">
                <div class="panel panel-default" style="margin-bottom: 10px;">
                    <div class="panel-body" style="display: flex; position: relative;">
                        <div data-toggle="tooltip" title="<?php _e('Open', 'upstream'); ?>">
                            <span class="label label-primary"><?php echo $bugsCounts['open']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Assigned to me', 'upstream'); ?>">
                            <span class="label label-info"><?php echo $bugsCounts['mine']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Overdue', 'upstream'); ?>">
                            <span class="label label-danger"><?php echo $bugsCounts['overdue']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Closed', 'upstream'); ?>">
                            <span class="label label-success"><?php echo $bugsCounts['closed']; ?></span>
                        </div>
                        <div data-toggle="tooltip" title="<?php _e('Total', 'upstream'); ?>">
                            <span class="label"
                                  style="background-color: #ecf0f1; color: #3A4E66;"><?php echo $bugsCounts['total']; ?></span>
                        </div>
                        <i class="fa fa-bug fa-2x" data-toggle="tooltip"
                           title="<?php printf(
                               '%s %s',
                               upstream_bug_label_plural(),
                               __('Overview', 'upstream')
                           ); ?>"
                           style="position: absolute; color: #ECF0F1; right: 8px; margin-top: -2px"></i>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
