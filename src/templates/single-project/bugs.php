<?php
// Prevent direct access.
if (!defined('ABSPATH')) exit;

if (!upstream_are_bugs_disabled()
    && !upstream_disable_bugs()):

$collapseBox = isset($pluginOptions['collapse_project_bugs'])
    && (bool)$pluginOptions['collapse_project_bugs'] === true;

$bugsSettings = get_option('upstream_bugs');
$bugsStatuses = $bugsSettings['statuses'];
$statuses = array();
foreach ($bugsStatuses as $status) {
    $statuses[$status['name']] = $status;
}

$bugsSeverities = $bugsSettings['severities'];
$severities = array();
foreach ($bugsSeverities as $severity) {
    $severities[$severity['name']] = $severity;
}
unset($bugsSeverities);

$itemType = 'bug';
$currentUserId = get_current_user_id();
$users = upstreamGetUsersMap();

$rowset = array();
$projectId = upstream_post_id();

$meta = (array)get_post_meta($projectId, '_upstream_project_bugs', true);
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
    $data['description'] = isset($data['description']) ? (string)$data['description'] : '';
    $data['severity'] = isset($data['severity']) ? (string)$data['severity'] : '';
    $data['status'] = isset($data['status']) ? (string)$data['status'] : '';
    $data['start_date'] = !isset($data['start_date']) || !is_numeric($data['start_date']) || $data['start_date'] < 0 ? 0 : (int)$data['start_date'];
    $data['end_date'] = !isset($data['end_date']) || !is_numeric($data['end_date']) || $data['end_date'] < 0 ? 0 : (int)$data['end_date'];

    $rowset[$data['id']] = $data;
}
unset($data, $meta);

$l = array(
    'LB_TITLE'         => _x('Title', "Bug's title", 'upstream'),
    'LB_NONE'          => __('none', 'upstream'),
    'LB_DESCRIPTION'   => __('Description', 'upstream'),
    'LB_COMMENTS'      => __('Comments', 'upstream'),
    'MSG_INVALID_USER' => sprintf(
        _x('invalid %s', '%s: column name. Error message when data reference is not found', 'upstream'),
        strtolower(__('User'))
    ),
    'LB_DUE_DATE'      => __('Due Date', 'upstream')
);

$areCommentsEnabled = upstreamAreCommentsEnabledOnBugs();

$tableSettings = array(
    'id'              => 'bugs',
    'type'            => 'bug',
    'data-ordered-by' => 'due_date',
    'data-order-dir'  => 'DESC'
);

function getBugsTableColumns(&$severities, &$statuses, &$areCommentsEnabled)
{
    $tableColumns = array(
        'title' => array(
            'type'        => 'raw',
            'isOrderable' => true,
            'label'       => __('Title', 'upstream')
        ),
        'assigned_to' => array(
            'type'        => 'user',
            'isOrderable' => true,
            'label'       => __('Assigned To', 'upstream')
        ),
        'severity'       => array(
            'type'  => 'custom',
            'label' => __('Severity', 'upstream'),
            'isOrderable' => true,
            'renderCallback' => function($columnName, $columnValue, $column, $row, $rowType, $projectId) use (&$severities) {
                if (strlen($columnValue) > 0) {
                    if (isset($severities[$columnValue])) {
                        $columnValue = sprintf('<span class="label up-o-label" style="background-color: %s;">%s</span>', $severities[$columnValue]['color'], $severities[$columnValue]['name']);
                    } else {
                        $columnValue = sprintf('<i class="@todo">%s</i>', __('invalid severity', 'upstream'));
                    }
                } else {
                    $columnValue = sprintf('<i class="s-text-color-gray">%s</i>', __('none', 'upstream'));
                }

                return $columnValue;
            }
        ),
        'status'       => array(
            'type'  => 'custom',
            'label' => __('Status', 'upstream'),
            'isOrderable' => true,
            'renderCallback' => function($columnName, $columnValue, $column, $row, $rowType, $projectId) use (&$statuses) {
                if (strlen($columnValue) > 0) {
                    if (isset($statuses[$columnValue])) {
                        $columnValue = sprintf('<span class="label up-o-label" style="background-color: %s;">%s</span>', $statuses[$columnValue]['color'], $statuses[$columnValue]['name']);
                    } else {
                        $columnValue = sprintf('<i class="@todo">%s</i>', __('invalid status', 'upstream'));
                    }
                } else {
                    $columnValue = sprintf('<i class="s-text-color-gray">%s</i>', __('none', 'upstream'));
                }

                return $columnValue;
            }
        ),
        'due_date'  => array(
            'type'        => 'date',
            'isOrderable' => true,
            'label'       => __('Due Date', 'upstream')
        ),
        'file'    => array(
            'type'        => 'file',
            'isOrderable' => false,
            'label'       => __('File', 'upstream')
        )
    );

    $hiddenTableColumns = array(
        'description' => array(
            'type'     => 'wysiwyg',
            'label'    => __('Description', 'upstream'),
            'isHidden' => true
        ),
        'comments'    => array(
            'type'     => 'comments',
            'label'    => __('Comments'),
            'isHidden' => true
        )
    );

    if (!$areCommentsEnabled) {
        unset($hiddenTableColumns['comments']);
    }

    $schema = array(
        'visibleColumns' => $tableColumns,
        'hiddenColumns'  => $hiddenTableColumns
    );

    return $schema;
}

$columnsSettings = getBugsTableColumns($severities, $statuses, $areCommentsEnabled);
?>
<div class="col-md-12 col-sm-12 col-xs-12">
  <div class="x_panel">
    <div class="x_title">
      <h2>
        <i class="fa fa-bug"></i> <?php echo upstream_bug_label_plural(); ?>
      </h2>
      <ul class="nav navbar-right panel_toolbox">
        <li>
          <a class="collapse-link">
            <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
          </a>
        </li>
        <?php do_action('upstream_project_bugs_top_right'); ?>
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
                <input type="search" class="form-control" placeholder="<?php echo $l['LB_TITLE']; ?>" data-column="title" data-compare-operator="contains">
              </div>
            </div>
            <div class="form-group">
              <div class="btn-group">
                <a href="#bugs-filters" role="button" class="btn btn-default" data-toggle="collapse" aria-expanded="false" aria-controls="bugs-filters">
                  <i class="fa fa-filter"></i> <?php _e('Toggle Filters', 'upstream'); ?>
                </a>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
              <a href="#bugs-filters" role="button" class="btn btn-default" data-toggle="collapse" aria-expanded="false" aria-controls="bugs-filters">
                <i class="fa fa-filter"></i> <?php _e('Toggle Filters', 'upstream'); ?>
              </a>
              <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
          <div id="bugs-filters" class="collapse">
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
                <select class="form-control o-select2" data-column="assigned_to" data-placeholder="<?php _e('Assignee', 'upstream'); ?>" multiple>
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
                  <i class="fa fa-asterisk"></i>
                </div>
                <select class="form-control o-select2" data-column="severity" data-placeholder="<?php _e('Severity', 'upstream'); ?>" multiple>
                  <option value></option>
                  <option value="__none__"><?php _e('None', 'upstream'); ?></option>
                  <optgroup label="<?php _e('Severity', 'upstream'); ?>">
                    <?php foreach ($severities as $severity): ?>
                    <option value="<?php echo $severity['name']; ?>"><?php echo $severity['name']; ?></option>
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
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-addon">
                  <i class="fa fa-calendar"></i>
                </div>
                <input type="text" class="form-control o-datepicker" placeholder="<?php echo $l['LB_DUE_DATE']; ?>" id="tasks-filter-due_date_from">
              </div>
              <input type="hidden" id="tasks-filter-due_date_from_timestamp" data-column="due_date" data-compare-operator=">=">
            </div>
          </div>
        </form>
        <?php \UpStream\WIP\renderTable($tableSettings, $columnsSettings, $rowset, 'bug', $projectId); ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
