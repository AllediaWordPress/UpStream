<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! upstream_are_tasks_disabled()
     && ! upstream_disable_tasks()):

    $collapseBox = isset($pluginOptions['collapse_project_tasks'])
                   && (bool)$pluginOptions['collapse_project_tasks'] === true;

    $collapseBoxState = \UpStream\Frontend\getSectionCollapseState('tasks');

    if ( ! is_null($collapseBoxState)) {
        $collapseBox = $collapseBoxState === 'closed';
    }

    $archiveClosedItems = upstream_archive_closed_items();

    $tasksStatuses = get_option('upstream_tasks');
    $statuses      = [];
    $openStatuses  = [];
    foreach ($tasksStatuses['statuses'] as $status) {
        // If closed items will be archived, we do not need to display closed statuses.
        if ($archiveClosedItems && 'open' !== $status['type']) {
            continue;
        }

        $statuses[$status['id']] = $status;

        if ('open' === $status['type']) {
            $openStatuses[] = $status['id'];
        }
    }

    $itemType      = 'task';
    $currentUserId = get_current_user_id();
    $users         = upstream_admin_get_all_project_users();

    $projectId = upstream_post_id();

    $areCommentsEnabled = upstreamAreCommentsEnabledOnTasks();

    $areMilestonesEnabled = ! upstream_are_milestones_disabled() && ! upstream_disable_milestones();
    $milestones           = [];

    if ($areMilestonesEnabled) {
        $milestones = \UpStream\Milestones::getInstance()->getMilestonesFromProject($projectId);
    }

    $rowset = UpStream_View::getTasks($projectId);

    // If should archive closed items, we filter the rowset.
    if ($archiveClosedItems) {
        foreach ($rowset as $id => $task) {
            if ( ! isset($task['status'])) {
                continue;
            }
            if ( ! in_array($task['status'], $openStatuses) && ! empty($task['status'])) {
                unset($rowset[$id]);
            }
        }
    }

    $l = [
        'LB_MILESTONE'          => upstream_milestone_label(),
        'LB_TITLE'              => __('Title', 'upstream'),
        'LB_NONE'               => __('none', 'upstream'),
        'LB_NOTES'              => __('Notes', 'upstream'),
        'LB_COMMENTS'           => __('Comments', 'upstream'),
        'MSG_INVALID_USER'      => sprintf(
            _x('invalid %s', '%s: column name. Error message when data reference is not found', 'upstream'),
            strtolower(__('User'))
        ),
        'MSG_INVALID_MILESTONE' => __('invalid milestone', 'upstream'),
        'LB_START_DATE'         => __('Starting after', 'upstream'),
        'LB_END_DATE'           => __('Ending before', 'upstream'),
    ];

    $l['MSG_INVALID_MILESTONE'] = sprintf(
        _x('invalid %s', '%s: column name. Error message when data reference is not found', 'upstream'),
        strtolower($l['LB_MILESTONE'])
    );

    $tableSettings = [
        'id'              => 'tasks',
        'type'            => 'task',
        'data-ordered-by' => 'start_date',
        'data-order-dir'  => 'DESC',
    ];

    $columnsSchema = \UpStream\Frontend\getTasksFields(
        $statuses,
        $milestones,
        $areMilestonesEnabled,
        $areCommentsEnabled
    );

    $filter_closed_items = upstream_filter_closed_items();
    ?>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel" data-section="tasks">
            <div class="x_title">
                <h2>
                    <i class="fa fa-bars sortable_handler"></i>
                    <i class="fa fa-wrench"></i> <?php echo upstream_task_label_plural(); ?>
                </h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li>
                        <a class="collapse-link">
                            <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
                        </a>
                    </li>
                    <?php do_action('upstream_project_tasks_top_right'); ?>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
                <div class="c-data-table table-responsive">
                    <form class="form-inline c-data-table__filters" data-target="#tasks">
                        <div class="hidden-xs">
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-search"></i>
                                    </div>
                                    <input type="search" class="form-control"
                                           placeholder="<?php echo $l['LB_TITLE']; ?>" data-column="title"
                                           data-compare-operator="contains">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="btn-group">
                                    <a href="#tasks-filters" role="button" class="btn btn-default btn-xs"
                                       data-toggle="collapse" aria-expanded="false" aria-controls="tasks-filters">
                                        <i class="fa fa-filter"></i> <?php _e('Toggle Filters', 'upstream'); ?>
                                    </a>
                                    <button type="button"
                                            class="btn btn-default dropdown-toggle btn-xs upstream-export-button"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-download"></i> <?php _e('Export', 'upstream'); ?>
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li>
                                            <a href="#" data-action="export" data-type="txt">
                                                <i class="fa fa-file-text-o"></i>&nbsp;&nbsp;<?php _e(
                                                    'Plain Text',
                                                    'upstream'
                                                ); ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" data-action="export" data-type="csv">
                                                <i class="fa fa-file-code-o"></i>&nbsp;&nbsp;<?php _e(
                                                    'CSV',
                                                    'upstream'
                                                ); ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="visible-xs">
                            <div>
                                <a href="#tasks-filters" role="button" class="btn btn-default btn-xs"
                                   data-toggle="collapse" aria-expanded="false" aria-controls="tasks-filters">
                                    <i class="fa fa-filter"></i> <?php _e('Toggle Filters', 'upstream'); ?>
                                </a>
                                <div class="btn-group">
                                    <button type="button"
                                            class="btn btn-default dropdown-toggle btn-xs upstream-export-button"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-download"></i> <?php _e('Export', 'upstream'); ?>
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li>
                                            <a href="#" data-action="export" data-type="txt">
                                                <i class="fa fa-file-text-o"></i>&nbsp;&nbsp;<?php _e(
                                                    'Plain Text',
                                                    'upstream'
                                                ); ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" data-action="export" data-type="csv">
                                                <i class="fa fa-file-code-o"></i>&nbsp;&nbsp;<?php _e(
                                                    'CSV',
                                                    'upstream'
                                                ); ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div id="tasks-filters" class="collapse">
                            <div class="form-group visible-xs">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-search"></i>
                                    </div>
                                    <input type="search" class="form-control"
                                           placeholder="<?php echo $l['LB_TITLE']; ?>" data-column="title"
                                           data-compare-operator="contains">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-user"></i>
                                    </div>
                                    <select class="form-control o-select2" data-column="assigned_to" multiple
                                            data-placeholder="<?php _e('Assignee', 'upstream'); ?>">
                                        <option value></option>
                                        <option value="__none__"><?php _e('Nobody', 'upstream'); ?></option>
                                        <option value="<?php echo $currentUserId; ?>"><?php _e(
                                                'Me',
                                                'upstream'
                                            ); ?></option>
                                        <optgroup label="<?php _e('Users'); ?>">
                                            <?php foreach ($users as $user_id => $userName): ?>
                                                <?php if ($user_id === $currentUserId) {
                                                    continue;
                                                } ?>
                                                <option
                                                        value="<?php echo $user_id; ?>"><?php echo $userName; ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-bookmark"></i>
                                    </div>
                                    <select class="form-control o-select2" data-column="status"
                                            data-placeholder="<?php _e('Status', 'upstream'); ?>" multiple>
                                        <option value="__none__" <?php echo $filter_closed_items ? 'selected' : ''; ?>><?php _e('None',
                                                'upstream'); ?></option>
                                        <optgroup label="<?php _e('Status', 'upstream'); ?>">
                                            <?php foreach ($statuses as $status): ?>
                                                <?php
                                                $attr = ' ';
                                                if ($filter_closed_items && 'open' === $status['type']) :
                                                    $attr .= ' selected';
                                                endif;
                                                ?>
                                                <option
                                                        value="<?php echo esc_attr($status['id']); ?>"<?php echo $attr; ?>><?php echo esc_html($status['name']); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                            <?php
                            if ($areMilestonesEnabled): ?>
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                            <i class="fa fa-flag"></i>
                                        </div>
                                        <select class="form-control o-select2" data-column="milestone"
                                                data-placeholder="<?php echo $l['LB_MILESTONE']; ?>" multiple>
                                            <option value></option>
                                            <option value="__none__"><?php _e('None', 'upstream'); ?></option>
                                            <optgroup label="<?php echo upstream_milestone_label_plural(); ?>">
                                                <?php foreach ($milestones as $milestone): ?>
                                                    <?php $milestone = \UpStream\Factory::getMilestone($milestone); ?>
                                                    <option
                                                            value="<?php echo $milestone->getId(); ?>"><?php echo $milestone->getName(); ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input type="text" class="form-control o-datepicker"
                                           placeholder="<?php echo $l['LB_START_DATE']; ?>"
                                           id="tasks-filter-start_date">
                                </div>
                                <input type="hidden" id="tasks-filter-start_date_timestamp" data-column="start_date"
                                       data-compare-operator=">=">
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input type="text" class="form-control o-datepicker"
                                           placeholder="<?php echo $l['LB_END_DATE']; ?>" id="tasks-filter-end_date">
                                </div>
                                <input type="hidden" id="tasks-filter-end_date_timestamp" data-column="end_date"
                                       data-compare-operator="<=">
                            </div>

                            <?php do_action(
                                'upstream:project.tasks.filters',
                                $tableSettings,
                                $columnsSchema,
                                $projectId
                            ); ?>
                        </div>
                    </form>
                    <?php \UpStream\Frontend\renderTable(
                        $tableSettings,
                        $columnsSchema,
                        $rowset,
                        'task',
                        $projectId
                    ); ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
