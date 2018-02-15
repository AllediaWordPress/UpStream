<?php
// Prevent direct access.
if (!defined('ABSPATH')) exit;

if (!upstream_are_tasks_disabled()
    && !upstream_disable_tasks()):

$collapseBox = isset($pluginOptions['collapse_project_tasks'])
    && (bool)$pluginOptions['collapse_project_tasks'] === true;

$tasksStatuses = get_option('upstream_tasks');
$statuses = array();
foreach ($tasksStatuses['statuses'] as $status) {
    $statuses[$status['name']] = $status;
}

$itemType = 'task';
$currentUserId = get_current_user_id();
$users = upstreamGetUsersMap();

$rowset = array();
$projectId = upstream_post_id();

$areCommentsEnabled = upstreamAreCommentsEnabledOnTasks();

$areMilestonesEnabled = !upstream_are_milestones_disabled() && !upstream_disable_milestones();
$milestonesColors = array();
$milestones = array();

if ($areMilestonesEnabled) {
    $milestonesList = get_option('upstream_milestones');
    foreach ($milestonesList['milestones'] as $milestone) {
        $milestonesColors[$milestone['title']] = $milestone['color'];
    }
    unset($milestonesList);

    $meta = (array)get_post_meta($projectId, '_upstream_project_milestones', true);
    foreach ($meta as $data) {
        if (!isset($data['id'])
            || !isset($data['created_by'])
            || !isset($data['milestone'])
        ) {
            continue;
        }

        $milestones[$data['id']] = array(
            'title' => $data['milestone'],
            'color' => $milestonesColors[$data['milestone']],
            'id'    => $data['id']
        );
    }
}

$meta = (array)get_post_meta($projectId, '_upstream_project_tasks', true);
foreach ($meta as $data) {
    if (!isset($data['id'])
        || !isset($data['created_by'])
    ) {
        continue;
    }

    $data['created_by'] = (int)$data['created_by'];
    $data['created_time'] = isset($data['created_time']) ? (int)$data['created_time'] : 0;
    $data['assigned_to'] = isset($data['assigned_to']) ? (int)$data['assigned_to'] : 0;
    $data['assigned_to_name'] = isset($users[$data['assigned_to']]) ? $users[$data['assigned_to']] : '';
    $data['progress'] = isset($data['progress']) ? (float)$data['progress'] : 0.00;
    $data['notes'] = isset($data['notes']) ? (string)$data['notes'] : '';
    $data['status'] = isset($data['status']) ? (string)$data['status'] : '';
    $data['milestone'] = isset($data['milestone']) ? (string)$data['milestone'] : '';
    $data['start_date'] = !isset($data['start_date']) || !is_numeric($data['start_date']) || $data['start_date'] < 0 ? 0 : (int)$data['start_date'];
    $data['end_date'] = !isset($data['end_date']) || !is_numeric($data['end_date']) || $data['end_date'] < 0 ? 0 : (int)$data['end_date'];

    $rowset[$data['id']] = $data;
}
unset($data, $meta);

$l = array(
    'LB_MILESTONE' => upstream_milestone_label(),
    'LB_TITLE' => _x('Title', "Task's title", 'upstream'),
    'LB_NONE'  => __('none', 'upstream'),
    'LB_NOTES'         => __('Notes', 'upstream'),
    'LB_COMMENTS'      => __('Comments', 'upstream'),
    'MSG_INVALID_USER' => sprintf(
        _x('invalid %s', '%s: column name. Error message when data reference is not found', 'upstream'),
        strtolower(__('User'))
    ),
    'MSG_INVALID_MILESTONE' => __('invalid milestone', 'upstream'),
    'LB_START_DATE'    => __('Starting at', 'upstream'),
    'LB_END_DATE'      => __('Ending at', 'upstream')
);

$l['MSG_INVALID_MILESTONE'] = sprintf(
    _x('invalid %s', '%s: column name. Error message when data reference is not found', 'upstream'),
    strtolower($l['LB_MILESTONE'])
);

$tableSettings = array(
    'id'              => 'tasks',
    'type'            => 'task',
    'data-ordered-by' => 'start_date',
    'data-order-dir'  => 'DESC'
);

$columnsSchema = \UpStream\Frontend\getTasksFields($statuses, $milestones, $areMilestonesEnabled, $areCommentsEnabled);
?>
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2>
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
                <input type="search" class="form-control" placeholder="<?php echo $l['LB_TITLE']; ?>" data-column="title" data-compare-operator="contains">
              </div>
            </div>
            <div class="form-group">
              <div class="btn-group">
                <a href="#tasks-filters" role="button" class="btn btn-default btn-xs" data-toggle="collapse" aria-expanded="false" aria-controls="tasks-filters">
                  <i class="fa fa-filter"></i> <?php _e('Toggle Filters', 'upstream'); ?>
                </a>
                <button type="button" class="btn btn-default dropdown-toggle btn-xs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fa fa-download"></i> <?php _e('Export', 'upstream'); ?>
                  <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right">
                  <li>
                    <a href="#" data-action="export" data-type="txt">
                      <i class="fa fa-file-text-o"></i>&nbsp;&nbsp;<?php _e('Plain Text', 'upstream'); ?>
                    </a>
                  </li>
                  <li>
                    <a href="#" data-action="export" data-type="csv">
                      <i class="fa fa-file-code-o"></i>&nbsp;&nbsp;<?php _e('CSV', 'upstream'); ?>
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="visible-xs">
            <div>
              <a href="#tasks-filters" role="button" class="btn btn-default btn-xs" data-toggle="collapse" aria-expanded="false" aria-controls="tasks-filters">
                <i class="fa fa-filter"></i> <?php _e('Toggle Filters', 'upstream'); ?>
              </a>
              <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle btn-xs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fa fa-download"></i> <?php _e('Export', 'upstream'); ?>
                  <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right">
                  <li>
                    <a href="#" data-action="export" data-type="txt">
                      <i class="fa fa-file-text-o"></i>&nbsp;&nbsp;<?php _e('Plain Text', 'upstream'); ?>
                    </a>
                  </li>
                  <li>
                    <a href="#" data-action="export" data-type="csv">
                      <i class="fa fa-file-code-o"></i>&nbsp;&nbsp;<?php _e('CSV', 'upstream'); ?>
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
                <input type="search" class="form-control" placeholder="<?php echo $l['LB_TITLE']; ?>" data-column="title" data-compare-operator="contains">
              </div>
            </div>
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-addon">
                  <i class="fa fa-user"></i>
                </div>
                <select class="form-control o-select2" data-column="assigned_to" multiple data-placeholder="<?php _e('Assignee', 'upstream'); ?>">
                  <option value></option>
                  <option value="__none__"><?php _e('Nobody', 'upstream'); ?></option>
                  <option value="<?php echo $currentUserId; ?>"><?php _e('Me', 'upstream'); ?></option>
                  <optgroup label="<?php _e('Users'); ?>">
                    <?php foreach ($users as $user_id => $userName): ?>
                      <?php if ($user_id === $currentUserId) continue; ?>
                      <option value="<?php echo $user_id; ?>"><?php echo $userName; ?></option>
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
                <select class="form-control o-select2" data-column="status" data-placeholder="<?php _e('Status', 'upstream'); ?>" multiple>
                  <option value></option>
                  <option value="__none__"><?php _e('None', 'upstream'); ?></option>
                  <optgroup label="<?php _e('Status', 'upstream'); ?>">
                    <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo $status['name']; ?>"><?php echo $status['name']; ?></option>
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
                <select class="form-control o-select2" data-column="milestone" data-placeholder="<?php echo $l['LB_MILESTONE']; ?>" multiple>
                  <option value></option>
                  <option value="__none__"><?php _e('None', 'upstream'); ?></option>
                  <optgroup label="<?php echo upstream_milestone_label_plural(); ?>">
                    <?php foreach ($milestones as $milestone_id => $milestone): ?>
                    <option value="<?php echo $milestone_id; ?>"><?php echo $milestone['title']; ?></option>
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
                <input type="text" class="form-control o-datepicker" placeholder="<?php echo $l['LB_START_DATE']; ?>" id="tasks-filter-start_date">
              </div>
              <input type="hidden" id="tasks-filter-start_date_timestamp" data-column="start_date" data-compare-operator=">=">
            </div>
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-addon">
                  <i class="fa fa-calendar"></i>
                </div>
                <input type="text" class="form-control o-datepicker" placeholder="<?php echo $l['LB_END_DATE']; ?>" id="tasks-filter-end_date">
              </div>
              <input type="hidden" id="tasks-filter-end_date_timestamp" data-column="end_date" data-compare-operator="<=">
            </div>

            <?php do_action('upstream:project.tasks.filters', $tableSettings, $columnsSchema, $projectId); ?>
          </div>
        </form>
        <?php \UpStream\Frontend\renderTable($tableSettings, $columnsSchema, $rowset, 'task', $projectId); ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
