<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! upstream_are_files_disabled()
     && ! upstream_disable_files()):

    $collapseBox = isset($pluginOptions['collapse_project_files'])
                   && (bool)$pluginOptions['collapse_project_files'] === true;

    $collapseBoxState = \UpStream\Frontend\getSectionCollapseState('files');

    if ( ! is_null($collapseBoxState)) {
        $collapseBox = $collapseBoxState === 'closed';
    }

    $itemType      = 'file';
    $currentUserId = get_current_user_id();
    $users         = upstream_admin_get_all_project_users();

    $rowset    = [];
    $projectId = upstream_post_id();

    $meta = (array)get_post_meta($projectId, '_upstream_project_files', true);
    foreach ($meta as $data) {
        if ( ! isset($data['id'])
             || ! isset($data['created_by'])
            || !upstream_override_access_object(true, UPSTREAM_ITEM_TYPE_FILE, $data['id'], UPSTREAM_ITEM_TYPE_PROJECT, $projectId, UPSTREAM_PERMISSIONS_ACTION_VIEW)
        ) {
            continue;
        }

        $data['created_by']   = (int)$data['created_by'];
        $data['created_time'] = isset($data['created_time']) ? (int)$data['created_time'] : 0;
        $data['title']        = isset($data['title']) ? (string)$data['title'] : '';
        $data['file_id']      = isset($data['file_id']) ? (int)$data['file_id'] : 0;
        $data['description']  = isset($data['description']) ? (string)$data['description'] : '';

        $rowset[$data['id']] = $data;
    }

    $l = [
        'LB_TITLE'       => __('Title', 'upstream'),
        'LB_NONE'        => __('none', 'upstream'),
        'LB_DESCRIPTION' => __('Description', 'upstream'),
        'LB_COMMENTS'    => __('Comments', 'upstream'),
        'LB_FILE'        => __('File', 'upstream'),
        'LB_UPLOADED_AT' => __('Upload Date', 'upstream'),
    ];

    $areCommentsEnabled = upstreamAreCommentsEnabledOnFiles();

    $tableSettings = [
        'id'              => 'files',
        'type'            => 'file',
        'data-ordered-by' => 'created_at',
        'data-order-dir'  => 'DESC',
    ];

    $columnsSchema = \UpStream\Frontend\getFilesFields($areCommentsEnabled);
    ?>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel" data-section="files">
            <div class="x_title">
                <h2>
                    <i class="fa fa-bars sortable_handler"></i>
                    <i class="fa fa-file"></i> <?php echo upstream_file_label_plural(); ?>
                </h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li>
                        <a class="collapse-link">
                            <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
                        </a>
                    </li>
                    <?php do_action('upstream_project_files_top_right', $projectId); ?>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
                <div class="c-data-table table-responsive">
                    <form class="form-inline c-data-table__filters" data-target="#files">
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
                                    <a href="#files-filters" role="button" class="btn btn-default btn-xs"
                                       data-toggle="collapse" aria-expanded="false" aria-controls="files-filters">
                                        <i class="fa fa-filter"></i> <?php _e('Toggle Filters', 'upstream'); ?>
                                    </a>
                                    <button type="button" class="btn btn-default dropdown-toggle btn-xs upstream-export-button"
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
                                <a href="#files-filters" role="button" class="btn btn-default btn-xs"
                                   data-toggle="collapse" aria-expanded="false" aria-controls="files-filters">
                                    <i class="fa fa-filter"></i> <?php _e('Toggle Filters', 'upstream'); ?>
                                </a>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default dropdown-toggle btn-xs upstream-export-button"
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
                        <div id="files-filters" class="collapse">
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
                                    <select class="form-control o-select2" data-column="created_by"
                                            data-placeholder="<?php _e('Uploader', 'upstream'); ?>" multiple>
                                        <option value></option>
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
                                           placeholder="<?php echo $l['LB_UPLOADED_AT']; ?>"
                                           id="files-filter-uploaded_at_from">
                                </div>
                                <input type="hidden" id="files-filter-uploaded_at_from_timestamp"
                                       data-column="created_time" data-compare-operator=">=">
                            </div>

                            <?php do_action(
                                'upstream:project.files.filters',
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
                        'file',
                        $projectId
                    ); ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
