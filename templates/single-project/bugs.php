<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! upstream_are_bugs_disabled()
     && ! upstream_disable_bugs()):

    $collapseBox = isset($pluginOptions['collapse_project_bugs'])
                   && (bool)$pluginOptions['collapse_project_bugs'] === true;

    $collapseBoxState = \UpStream\Frontend\getSectionCollapseState('bugs');

    if ( ! is_null($collapseBoxState)) {
        $collapseBox = $collapseBoxState === 'closed';
    }

    $archiveClosedItems = upstream_archive_closed_items();

    $bugsSettings = get_option('upstream_bugs');
    $bugsStatuses = $bugsSettings['statuses'];
    $statuses     = [];
    $openStatuses = [];
    foreach ($bugsStatuses as $status) {
        // If closed items will be archived, we do not need to display closed statuses.
        if ($archiveClosedItems && 'open' !== $status['type']) {
            continue;
        }

        $statuses[$status['id']] = $status;

        if ('open' === $status['type']) {
            $openStatuses[] = $status['id'];
        }
    }

    $bugsSeverities = $bugsSettings['severities'];
    $severities     = [];
    foreach ($bugsSeverities as $index => $severity) {
        $severity['order'] = $index;

        $severities[$severity['id']] = $severity;
    }
    unset($bugsSeverities);

    $itemType      = 'bug';
    $currentUserId = get_current_user_id();
    $users         = upstream_admin_get_all_project_users();

    $projectId = upstream_post_id();

    $rowset = UpStream_View::getBugs($projectId);

    // If should archive closed items, we filter the rowset.
    if ($archiveClosedItems) {
        foreach ($rowset as $id => $bug) {
            if ( ! in_array($bug['status'], $openStatuses) && ! empty($bug['status'])) {
                unset($rowset[$id]);
            }
        }
    }

    $l = [
        'LB_TITLE'         => __('Title', 'upstream'),
        'LB_NONE'          => __('none', 'upstream'),
        'LB_DESCRIPTION'   => __('Description', 'upstream'),
        'LB_COMMENTS'      => __('Comments', 'upstream'),
        'MSG_INVALID_USER' => sprintf(
            _x('invalid %s', '%s: column name. Error message when data reference is not found', 'upstream'),
            strtolower(__('User'))
        ),
        'LB_DUE_DATE'      => __('Due Date', 'upstream'),
    ];

    $areCommentsEnabled = upstreamAreCommentsEnabledOnBugs();

    $tableSettings = [
        'id'              => 'bugs',
        'type'            => 'bug',
        'data-ordered-by' => 'due_date',
        'data-order-dir'  => 'DESC',
    ];

    $columnsSchema = \UpStream\Frontend\getBugsFields($severities, $statuses, $areCommentsEnabled);

    $filter_closed_items = upstream_filter_closed_items();

    ?>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel" data-section="bugs">
            <div class="x_title">
                <h2>
                    <i class="fa fa-bars sortable_handler"></i>
                    <i class="fa fa-bug"></i> <?php echo esc_html(upstream_bug_label_plural()); ?>
                </h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li>
                        <a class="collapse-link">
                            <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
                        </a>
                    </li>
                    <?php do_action('upstream_project_bugs_top_right', $projectId); ?>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
                <div class="c-data-table table-responsive">
                    <form class="form-inline c-data-table__filters" data-target="#bugs">
                        <div class="hidden-xs">
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-search"></i>
                                    </div>
                                    <input type="search" class="form-control"
                                           placeholder="<?php echo esc_attr($l['LB_TITLE']); ?>" data-column="title"
                                           data-compare-operator="contains">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="btn-group">
                                    <a href="#bugs-filters" role="button" class="btn btn-default btn-xs"
                                       data-toggle="collapse" aria-expanded="false" aria-controls="bugs-filters">
                                        <i class="fa fa-filter"></i> <?php esc_html_e('Toggle Filters', 'upstream'); ?>
                                    </a>
                                    <button type="button" class="btn btn-default dropdown-toggle btn-xs upstream-export-button"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-download"></i> <?php esc_html_e('Export', 'upstream'); ?>
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li>
                                            <a href="#" data-action="export" data-type="txt">
                                                <i class="fa fa-file-text-o"></i>&nbsp;&nbsp;<?php esc_html_e(
                                                    'Plain Text',
                                                    'upstream'
                                                ); ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" data-action="export" data-type="csv">
                                                <i class="fa fa-file-code-o"></i>&nbsp;&nbsp;<?php esc_html_e(
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
                                <a href="#bugs-filters" role="button" class="btn btn-default btn-xs"
                                   data-toggle="collapse" aria-expanded="false" aria-controls="bugs-filters">
                                    <i class="fa fa-filter"></i> <?php esc_html_e('Toggle Filters', 'upstream'); ?>
                                </a>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default dropdown-toggle btn-xs upstream-export-button"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-download"></i> <?php esc_html_e('Export', 'upstream'); ?>
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li>
                                            <a href="#" data-action="export" data-type="txt">
                                                <i class="fa fa-file-text-o"></i>&nbsp;&nbsp;<?php esc_html_e(
                                                    'Plain Text',
                                                    'upstream'
                                                ); ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" data-action="export" data-type="csv">
                                                <i class="fa fa-file-code-o"></i>&nbsp;&nbsp;<?php esc_html_e(
                                                    'CSV',
                                                    'upstream'
                                                ); ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div id="bugs-filters" class="collapse">
                            <div class="form-group visible-xs">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-search"></i>
                                    </div>
                                    <input type="search" class="form-control"
                                           placeholder="<?php echo esc_attr($l['LB_TITLE']); ?>" data-column="title"
                                           data-compare-operator="contains">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-user"></i>
                                    </div>
                                    <select class="form-control o-select2" data-column="assigned_to"
                                            data-placeholder="<?php esc_attr_e('Assignee', 'upstream'); ?>" multiple>
                                        <option value></option>
                                        <option value="__none__"><?php esc_html_e('Nobody', 'upstream'); ?></option>
                                        <option value="<?php echo $currentUserId; ?>"><?php esc_html_e(
                                                'Me',
                                                'upstream'
                                            ); ?></option>
                                        <optgroup label="<?php esc_attr_e('Users'); ?>">
                                            <?php foreach ($users as $user_id => $userName): ?>
                                                <?php if ($user_id === $currentUserId) {
                                                    continue;
                                                } ?>
                                                <option
                                                        value="<?php echo $user_id; ?>"><?php echo esc_html($userName); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-asterisk"></i>
                                    </div>
                                    <select class="form-control o-select2" data-column="severity"
                                            data-placeholder="<?php esc_attr_e('Severity', 'upstream'); ?>" multiple>
                                        <option value></option>
                                        <option value="__none__"><?php esc_html_e('None', 'upstream'); ?></option>
                                        <optgroup label="<?php esc_attr_e('Severity', 'upstream'); ?>">
                                            <?php foreach ($severities as $severity): ?>
                                                <option
                                                        value="<?php echo esc_attr($severity['id']); ?>"><?php echo esc_html($severity['name']); ?></option>
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
                                            data-placeholder="<?php esc_attr_e('Status', 'upstream'); ?>" multiple>
                                        <option value></option>
                                        <option value="__none__" <?php echo $filter_closed_items ? 'selected' : ''; ?>><?php esc_html_e('None',
                                                'upstream'); ?></option>
                                        <optgroup label="<?php esc_html_e('Status', 'upstream'); ?>">
                                            <?php foreach ($statuses as $status): ?>
                                                <?php
                                                $attr = ' ';
                                                if ($filter_closed_items && 'open' === $status['type']) :
                                                    $attr .= ' selected';
                                                endif;
                                                ?>
                                                <option value="<?php echo esc_attr($status['id']); ?>"<?php echo $attr; ?>><?php echo esc_html($status['name']); ?></option>
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
                                           placeholder="<?php echo esc_attr($l['LB_DUE_DATE']); ?>"
                                           id="tasks-filter-due_date_from">
                                </div>
                                <input type="hidden" id="tasks-filter-due_date_from_timestamp" data-column="due_date"
                                       data-compare-operator=">=">
                            </div>

                            <?php do_action(
                                'upstream:project.bugs.filters',
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
                        'bug',
                        $projectId
                    ); ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
