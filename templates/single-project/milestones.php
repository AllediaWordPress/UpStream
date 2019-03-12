<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! upstream_are_milestones_disabled()
     && ! upstream_disable_milestones()):

    $collapseBox = isset($pluginOptions['collapse_project_milestones'])
                   && (bool)$pluginOptions['collapse_project_milestones'] === true;

    $collapseBoxState = \UpStream\Frontend\getSectionCollapseState('milestones');

    if ( ! is_null($collapseBoxState)) {
        $collapseBox = $collapseBoxState === 'closed';
    }

    $itemType      = 'milestone';
    $currentUserId = get_current_user_id();
    $users         = upstream_admin_get_all_project_users();

    $projectId = upstream_post_id();

    $projectMilestones = UpStream_View::getMilestones($projectId);

    $l = [
        'LB_MILESTONE'     => upstream_milestone_label(),
        'LB_TASKS'         => upstream_task_label_plural(),
        'LB_START_DATE'    => __('Starting after', 'upstream'),
        'LB_END_DATE'      => __('Ending before', 'upstream'),
        'LB_NONE'          => __('none', 'upstream'),
        'LB_OPEN'          => _x('Open', 'Task status', 'upstream'),
        'LB_NOTES'         => __('Notes', 'upstream'),
        'LB_COMMENTS'      => __('Comments', 'upstream'),
        'MSG_INVALID_USER' => __('invalid user', 'upstream'),
    ];

    $areCommentsEnabled = upstreamAreCommentsEnabledOnMilestones();

    $tableSettings = [
        'id'              => 'milestones',
        'type'            => 'milestone',
        'data-ordered-by' => 'start_date',
        'data-order-dir'  => 'DESC',
    ];
    $columnsSchema = \UpStream\Frontend\getMilestonesFields();
    ?>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel" data-section="milestones">
            <div class="x_title">
                <h2>
                    <i class="fa fa-bars sortable_handler"></i>
                    <i class="fa fa-flag"></i> <?php echo upstream_milestone_label_plural(); ?>
                </h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li>
                        <a class="collapse-link">
                            <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
                        </a>
                    </li>
                    <?php do_action('upstream_project_milestones_top_right'); ?>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
                <div class="c-data-table table-responsive">
                    <form class="form-inline c-data-table__filters" data-target="#milestones">
                        <div class="hidden-xs">
                            <?php
                            \UpStream\Frontend\renderTableFilter('search', 'milestone', [
                                'attrs' => [
                                    'placeholder' => $l['LB_MILESTONE'],
                                    'width'       => 200,
                                ],
                            ]);
                            ?>
                            <div class="form-group">
                                <div class="btn-group">
                                    <a href="#milestones-filters" role="button" class="btn btn-default btn-xs"
                                       data-toggle="collapse" aria-expanded="false" aria-controls="milestones-filters">
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
                                <a href="#milestones-filters" role="button" class="btn btn-default btn-xs"
                                   data-toggle="collapse" aria-expanded="false" aria-controls="milestones-filters">
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
                        <div id="milestones-filters" class="collapse">
                            <div class="form-group visible-xs">
                              <?php
                              \UpStream\Frontend\renderTableFilter('search', 'milestone', [
                                  'attrs' => [
                                      'placeholder' => $l['LB_MILESTONE'],
                                      'width'       => 200,
                                  ],
                              ], false);
                              ?>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-user"></i>
                                    </div>
                                    <select class="form-control o-select2" data-column="assigned_to"
                                            data-placeholder="<?php _e('Assignee', 'upstream'); ?>" multiple>
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
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input type="text" class="form-control o-datepicker"
                                           placeholder="<?php echo $l['LB_START_DATE']; ?>"
                                           id="milestones-filter-start_date">
                                </div>
                                <input type="hidden" id="milestones-filter-start_date_timestamp"
                                       data-column="start_date" data-compare-operator=">=">
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input type="text" class="form-control o-datepicker"
                                           placeholder="<?php echo $l['LB_END_DATE']; ?>"
                                           id="milestones-filter-end_date">
                                </div>
                                <input type="hidden" id="milestones-filter-end_date_timestamp" data-column="end_date"
                                       data-compare-operator="<=">
                            </div>

                            <?php do_action(
                                'upstream:project.milestones.filters',
                                $tableSettings,
                                $columnsSchema,
                                $projectId
                            ); ?>
                        </div>
                    </form>
                    <?php \UpStream\Frontend\renderTable(
                        $tableSettings,
                        $columnsSchema,
                        $projectMilestones,
                        'milestone',
                        $projectId
                    ); ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
