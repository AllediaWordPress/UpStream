<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * The Template for displaying all projects
 *
 * This template can be overridden by copying it to wp-content/themes/yourtheme/upstream/archive-project.php.
 */

// Some hosts disable this function, so let's make sure it is enabled before call it.
if (function_exists('set_time_limit')) {
    set_time_limit(1200);
}


try {
    if (!session_id()) {
        session_start();
    }
} catch (\Exception $e) {

}

add_action('init', function() {
    try {
        if (!session_id()) {
            session_start();
        }
    } catch (\Exception $e) {
    }
}, 9);

if (isset($_GET['report'])) {
    if (isset($_POST['submit'])) {
        include "report-display.php";
    } else {
        include "report-parameters.php";
    }
    return;
} elseif (isset($_GET['download'])) {
    upstream_upfs_download($_GET['download']);
    return;
}

$pluginOptions = get_option('upstream_projects');
$optionName = 'project_number_per_page';
$total_per_page = isset($pluginOptions[$optionName]) ? (int)$pluginOptions[$optionName] : 1000;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';

if ($page <= 1) $page = 1;
$display_start = ($page-1)*$total_per_page;

$project_page_url = get_post_type_archive_link('project');

$areClientsEnabled = ! is_clients_disabled();

$archiveClosedItems = upstream_archive_closed_items();

$i18n = [
    'LB_PROJECT'        => upstream_project_label(),
    'LB_PROJECTS'       => upstream_project_label_plural(),
    'LB_TASKS'          => upstream_task_label_plural(),
    'LB_BUGS'           => upstream_bug_label_plural(),
    'LB_LOGOUT'         => __('Log Out', 'upstream'),
    'LB_ENDS_AT'        => __('Ends at', 'upstream'),
    'MSG_SUPPORT'       => upstream_admin_support_label($pluginOptions),
    'LB_TITLE'          => __('Title', 'upstream'),
    'LB_TOGGLE_FILTERS' => __('Toggle Filters', 'upstream'),
    'LB_EXPORT'         => __('Export', 'upstream'),
    'LB_PLAIN_TEXT'     => __('Plain Text', 'upstream'),
    'LB_CSV'            => __('CSV', 'upstream'),
    'LB_CLIENT'         => upstream_client_label(),
    'LB_CLIENTS'        => upstream_client_label_plural(),
    'LB_STATUS'         => __('Status', 'upstream'),
    'LB_STATUSES'       => __('Statuses', 'upstream'),
    'LB_CATEGORIES'     => __('Categories'),
    'LB_PROGRESS'       => __('Progress', 'upstream'),
    'LB_NONE_UCF'       => __('None', 'upstream'),
    'LB_NONE'           => __('none', 'upstream'),
    'LB_COMPLETE'       => __('%s Complete', 'upstream'),
];

$currentUser = (object)upstream_user_data(@$_SESSION['upstream']['user_id']);

$projectStatuses = upstream_get_all_project_statuses();
$projectOrder    = [];

$statuses     = [];
$openStatuses = [];
// We start from 1 instead of 0 because the 0 position is used for "__none__".
$statusIndex = 1;
foreach ($projectStatuses as $statusId => $status) {
    $projectOrder[$statusIndex++] = $statusId;

    // If closed items will be archived, we do not need to display closed statuses.
    if ($archiveClosedItems && 'open' !== $status['type']) {
        continue;
    }

    $statuses[$status['id']] = $status;

    if ('open' === $status['type']) {
        $openStatuses[] = $status['id'];
    }
}

$projectsList = [];
$countPos = 0;
$totalProjects =0;
if (isset($currentUser->projects)) {
    if (is_array($currentUser->projects) && count($currentUser->projects) > 0) {

        $projects_list = $currentUser->projects;
        uasort($projects_list, function($a, $b) {
            if (!isset($a->post_title) || !isset($b->post_title)) return 0;
            return strcasecmp($a->post_title, $b->post_title);
        });

        foreach ($projects_list as $project_id => $project) {

            $project = new UpStream_Project($project_id);
            $start = $project->get_meta('start');
            if (!$start) $start = 0;
            $end = $project->get_meta('end');
            if (!$end) $end = 0;
            $prog = $project->get_meta('progress');
            if (!$prog) $prog = 0;
            $stat =  $project->get_meta('status');

            $data = (object)[
                'id'                 => $project_id,
                'author'             => (int)$project->post_author,
                'created_at'         => (string)$project->post_date_gmt,
                'modified_at'        => (string)$project->post_modified_gmt,
                'title'              => $project->post_title,
                'slug'               => $project->post_name,
                'status'             => $project->post_status,
                'permalink'          => get_permalink($project_id),
                'startDateTimestamp' => (int)$start,
                'endDateTimestamp'   => (int)$end,
                'progress'           => (float)round($prog),
                'status'             => (string)$stat,
                'clientName'         => null,
                'categories'         => [],
                'features'           => [
                    '',
                ],
            ];

            // If should archive closed items, we filter the rowset.
            if ($archiveClosedItems) {
                 if ( ! empty($data->status) && ! in_array($data->status, $openStatuses)) {
                    continue;
                }
            }

            if (!empty($search) && !stristr($project->post_title, $search)) {
                continue;
            }

            $totalProjects++;
            $countPos++;

            if ($countPos-1 < $display_start) continue;
            if ($countPos-1 >= $display_start+$total_per_page) continue;

            $data->startDate = (string)upstream_format_date($data->startDateTimestamp);
            $data->endDate   = (string)upstream_format_date($data->endDateTimestamp);

            if ($areClientsEnabled) {
                $data->clientName = trim((string)upstream_project_client_name($project_id));
            }

            if (isset($statuses[$data->status])) {
                $data->status = $statuses[$data->status];
            }

            $data->timeframe = $data->startDate;
            if ( ! empty($data->endDate)) {
                if ( ! empty($data->timeframe)) {
                    $data->timeframe .= ' - ';
                } else {
                    $data->timeframe = '<i>' . $i18n['LB_ENDS_AT'] . '</i>';
                }

                $data->timeframe .= $data->endDate;
            }

            $categories = (array)wp_get_object_terms($data->id, 'project_category');
            if (count($categories) > 0) {
                foreach ($categories as $category) {
                    $data->categories[$category->term_id] = $category->name;
                }
            }

            $projectsList[$project_id] = $data;
        }

        unset($project, $project_id);
    }

    unset($currentUser->projects);
}

$projectsListCount = count($projectsList);

if (!apply_filters('upstream_theme_override_header', false)) {
    upstream_get_template_part('global/header.php');
}

if (!apply_filters('upstream_theme_override_sidebar', false)) {
    upstream_get_template_part('global/sidebar.php');
}

if (!apply_filters('upstream_theme_override_topnav', false)) {
    upstream_get_template_part('global/top-nav.php');
}

$categories = (array)get_terms([
    'taxonomy'   => 'project_category',
    'hide_empty' => false,
]);

$projectsView = ! isset($_GET['view']);


// Filters
$tableSettings = [
    'id'              => 'projects',
    'type'            => 'project',
    'data-ordered-by' => 'start_date',
    'data-order-dir'  => 'DESC',
];
$columnsSchema = \UpStream\Frontend\getProjectFields();

$hiddenColumnsSchema = [];

foreach ($columnsSchema as $columnName => $columnArgs) {
    if (isset($columnArgs['isHidden']) && (bool)$columnArgs['isHidden'] === true) {
        $hiddenColumnsSchema[$columnName] = $columnArgs;
    }
}

$filter_closed_items = upstream_filter_closed_items();

$ordering = \UpStream\Frontend\getTableOrder('projects');
$orderBy  = '';
$orderDir = '';
if ( ! empty($ordering)) {
    $orderBy  = $ordering['column'];
    $orderDir = $ordering['orderDir'];
}
?>

    <div class="right_col" role="main">
    <div class="alerts">
        <?php do_action('upstream_frontend_projects_messages'); ?>
        <?php do_action('upstream_single_project_before_overview'); ?>
    </div>

    <?php do_action('upstream_archive_project_top'); ?>

    <div class="">
        <?php if ($projectsView) : ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="x_panel" data-section="projects">
                        <div class="x_title">
                            <h2><i class="fa fa-briefcase"></i> <?php echo esc_html($i18n['LB_PROJECTS']); ?></h2>
                            <ul class="nav navbar-right panel_toolbox">
                                <li>
                                    <a class="collapse-link">
                                        <i class="fa fa-chevron-up"></i>
                                    </a>
                                </li>
                                <?php do_action('upstream_project_project_top_right'); ?>
                            </ul>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <?php if ($projectsListCount > 0 || !empty($search)): ?>
                                <div class="c-data-table table-responsive">
                                    <form class="form-inline c-data-table__filters" data-target="#projects" method="get" action="<?php print $project_page_url ?>">
                                        <div class="hidden-xs">
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <div class="input-group-addon">
                                                        <i class="fa fa-search"></i>
                                                    </div>

                                                        <input type="search" class="form-control"
                                                               placeholder="<?php echo esc_attr($i18n['LB_TITLE']); ?>"
                                                               <?php if ($totalProjects > $total_per_page || !empty($search)) { ?>
                                                                    data-searchurl="<?php print esc_url(add_query_arg('search', '_SEARCH_STR_', $project_page_url)); ?>"
                                                               <?php } ?>
                                                                <?php if (!empty($search)) { ?>
                                                                    value="<?php print esc_attr($search); ?>"
                                                                <?php } ?>
                                                               data-column="title" data-compare-operator="contains">

                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="btn-group">
                                                    <a href="#projects-filters" role="button"
                                                       class="btn btn-default btn-xs"
                                                       data-toggle="collapse" aria-expanded="false"
                                                       aria-controls="projects-filters">
                                                        <i class="fa fa-filter"></i> <?php echo esc_html($i18n['LB_TOGGLE_FILTERS']); ?>
                                                    </a>
                                                    <button type="button"
                                                            class="btn btn-default dropdown-toggle btn-xs upstream-export-button"
                                                            data-toggle="dropdown" aria-haspopup="true"
                                                            aria-expanded="false">
                                                        <i class="fa fa-download"></i> <?php echo esc_html($i18n['LB_EXPORT']); ?>
                                                        <span class="caret"></span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right">
                                                        <li>
                                                            <a href="#" data-action="export" data-type="txt">
                                                                <i class="fa fa-file-text-o"></i>&nbsp;&nbsp;<?php echo esc_html($i18n['LB_PLAIN_TEXT']); ?>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" data-action="export" data-type="csv">
                                                                <i class="fa fa-file-code-o"></i>&nbsp;&nbsp;<?php echo esc_html($i18n['LB_CSV']); ?>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="visible-xs">
                                            <div>
                                                <a href="#projects-filters" role="button" class="btn btn-default btn-xs"
                                                   data-toggle="collapse" aria-expanded="false"
                                                   aria-controls="projects-filters">
                                                    <i class="fa fa-filter"></i> <?php echo esc_html($i18n['LB_TOGGLE_FILTERS']); ?>
                                                </a>
                                                <div class="btn-group">
                                                    <button type="button"
                                                            class="btn btn-default dropdown-toggle btn-xs upstream-export-button"
                                                            data-toggle="dropdown" aria-haspopup="true"
                                                            aria-expanded="false">
                                                        <i class="fa fa-download"></i> <?php echo esc_html($i18n['LB_EXPORT']); ?>
                                                        <span class="caret"></span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right">
                                                        <li>
                                                            <a href="#" data-action="export" data-type="txt">
                                                                <i class="fa fa-file-text-o"></i>&nbsp;&nbsp;<?php echo esc_html($i18n['LB_PLAIN_TEXT']); ?>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" data-action="export" data-type="csv">
                                                                <i class="fa fa-file-code-o"></i>&nbsp;&nbsp;<?php echo esc_html($i18n['LB_CSV']); ?>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="projects-filters" class="collapse">
                                            <div class="form-group visible-xs">
                                                <div class="input-group">
                                                    <div class="input-group-addon">
                                                        <i class="fa fa-search"></i>
                                                    </div>
                                                    <input type="search" class="form-control"
                                                           placeholder="<?php echo esc_attr($i18n['LB_TITLE']); ?>"
                                                           data-column="title" data-compare-operator="contains">
                                                </div>
                                            </div>
                                            <?php if ( ! is_clients_disabled()) : ?>
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">
                                                            <i class="fa fa-user"></i>
                                                        </div>
                                                        <input type="search" class="form-control"
                                                               placeholder="<?php echo esc_attr($i18n['LB_CLIENTS']); ?>"
                                                               data-column="client" data-compare-operator="contains">
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <div class="input-group-addon">
                                                        <i class="fa fa-bookmark"></i>
                                                    </div>
                                                    <select class="form-control o-select2" data-column="status"
                                                            data-placeholder="<?php echo esc_attr($i18n['LB_STATUS']); ?>"
                                                            multiple>
                                                        <option value></option>
                                                        <option
                                                                value="__none__" <?php echo $filter_closed_items ? 'selected' : ''; ?>><?php echo esc_html($i18n['LB_NONE_UCF']); ?></option>
                                                        <optgroup label="<?php echo esc_html($i18n['LB_STATUSES']); ?>">
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
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <div class="input-group-addon">
                                                        <i class="fa fa-tags"></i>
                                                    </div>
                                                    <select class="form-control o-select2" data-column="categories"
                                                            data-placeholder="<?php echo esc_attr($i18n['LB_CATEGORIES']); ?>"
                                                            multiple data-compare-operator="contains">
                                                        <option value></option>
                                                        <option
                                                                value="__none__"><?php echo esc_html($i18n['LB_NONE_UCF']); ?></option>
                                                        <optgroup
                                                                label="<?php echo esc_html($i18n['LB_CATEGORIES']); ?>">
                                                            <?php foreach ($categories as $category): ?>
                                                                <option
                                                                        value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                                                            <?php endforeach; ?>
                                                        </optgroup>
                                                    </select>
                                                </div>
                                            </div>

                                            <?php do_action('upstream:project.filters', $tableSettings,
                                                $columnsSchema); ?>
                                        </div>
                                    </form>
                                    <table id="projects"
                                           class="o-data-table table table-bordered table-responsive table-hover is-orderable"
                                           cellspacing="0"
                                           width="100%"
                                           data-type="project"
                                           data-ordered-by="<?php echo esc_attr($orderBy); ?>"
                                           data-order-dir="<?php echo esc_attr($orderDir); ?>">
                                        <thead>
                                        <tr>
                                            <th class="is-clickable is-orderable" data-column="title" role="button">
                                                <?php echo esc_html($i18n['LB_PROJECT']); ?>
                                                <span class="pull-right o-order-direction">
                                                  <i class="fa fa-sort"></i>
                                                </span>
                                            </th>
                                            <th class="is-clickable is-orderable" data-column="startDate" role="button">
                                                <?php echo esc_html__("Start Date", 'upstream'); ?>
                                                <span class="pull-right o-order-direction">
                                                  <i class="fa fa-sort"></i>
                                                </span>
                                            </th>
                                            <th class="is-clickable is-orderable" data-column="endDate" role="button">
                                                <?php echo esc_html__("End Date", 'upstream'); ?>
                                                <span class="pull-right o-order-direction">
                                                  <i class="fa fa-sort"></i>
                                                </span>
                                            </th>
                                            <?php if ($areClientsEnabled): ?>
                                                <th class="is-clickable is-orderable" data-column="client"
                                                    role="button">
                                                    <?php echo esc_html($i18n['LB_CLIENT']); ?>
                                                    <span class="pull-right o-order-direction">
                                                  <i class="fa fa-sort"></i>
                                                </span>
                                                </th>
                                                <th data-column="client-users">
                                                    <?php printf(__('%s Users', 'upstream'), $i18n['LB_CLIENT']); ?>
                                                </th>
                                            <?php endif; ?>
                                            <th data-column="members">
                                                <?php printf(__('%s Members', 'upstream'), $i18n['LB_PROJECT']); ?>
                                            </th>
                                            <th class="is-clickable is-orderable" data-column="progress" role="button">
                                                <?php echo esc_html($i18n['LB_PROGRESS']); ?>
                                                <span class="pull-right o-order-direction">
                                              <i class="fa fa-sort"></i>
                                            </span>
                                            </th>
                                            <th class="is-clickable is-orderable" data-column="status" role="button">
                                                <?php echo esc_html($i18n['LB_STATUS']); ?>
                                                <span class="pull-right o-order-direction">
                                          <i class="fa fa-sort"></i>
                                        </span>
                                            </th>
                                            <th style="max-width: 250px;" data-column="categories">
                                                <?php echo esc_html($i18n['LB_CATEGORIES']); ?>
                                            </th>

                                            <?php do_action('upstream:project.columns.header', $tableSettings,
                                                $columnsSchema); ?>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $isProjectIndexOdd = true;
                                        foreach ($projectsList as $projectIndex => $project): ?>
                                            <?php
                                            $project = apply_filters('upstream_frontend_project_data', $project,
                                                $project->id);
                                            ?>
                                            <tr class="t-row-<?php echo $isProjectIndexOdd ? 'odd' : 'even'; ?>"
                                                data-id="<?php echo $project->id; ?>">

                                                <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, null, 0, 'title', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>
                                                    <td data-column="title"
                                                        data-value="<?php echo esc_attr($project->title); ?>">
                                                        <?php do_action('upstream:frontend.project.details.before_title',
                                                            $project); ?>
                                                        <a href="<?php echo esc_url($project->permalink); ?>">
                                                            <?php echo esc_html($project->title); ?>
                                                        </a>
                                                    </td>
                                                <?php else: ?>
                                                    <td data-column="title"
                                                        data-value="">
                                                        <span class="label up-o-label"
                                                              style="background-color:#666;color:#fff">(hidden)</span>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, null, 0, 'start', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>
                                                    <td data-column="startDate"
                                                        data-value="<?php echo esc_attr($project->startDateTimestamp); ?>">
                                                        <?php echo esc_html($project->startDate); ?>
                                                    </td>
                                                <?php else: ?>
                                                    <td data-column="startDate"
                                                        data-value="">
                                                        <span class="label up-o-label"
                                                              style="background-color:#666;color:#fff">(hidden)</span>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, null, 0, 'end', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>
                                                    <td data-column="endDate"
                                                        data-value="<?php echo esc_attr($project->endDateTimestamp); ?>">
                                                        <?php echo esc_html($project->endDate); ?>
                                                    </td>
                                                <?php else: ?>
                                                    <td data-column="endDate"
                                                        data-value="">
                                                        <span class="label up-o-label"
                                                              style="background-color:#666;color:#fff">(hidden)</span>
                                                    </td>
                                                <?php endif; ?>


                                                <?php if ($areClientsEnabled): ?>

                                                    <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, null, 0, 'client', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>
                                                        <td data-column="client"
                                                            data-value="<?php echo $project->clientName !== null ? esc_attr($project->clientName) : '__none__'; ?>">
                                                            <?php if ($project->clientName !== null): ?>
                                                                <?php echo esc_html($project->clientName); ?>
                                                            <?php else: ?>
                                                                <i class="s-text-color-gray"><?php echo esc_html($i18n['LB_NONE']); ?></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php else: ?>
                                                        <td data-column="client"
                                                            data-value="">
                                                        <span class="label up-o-label"
                                                              style="background-color:#666;color:#fff">(hidden)</span>
                                                        </td>
                                                    <?php endif; ?>

                                                    <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, null, 0, 'client_users', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>
                                                        <td data-column="client-users">
                                                            <?php upstream_output_client_users($project->id); ?>
                                                        </td>
                                                    <?php else: ?>
                                                        <td data-column="client-users"
                                                            data-value="">
                                                        <span class="label up-o-label"
                                                              style="background-color:#666;color:#fff">(hidden)</span>
                                                        </td>
                                                    <?php endif; ?>

                                                <?php endif; ?>

                                                <td data-column="members">
                                                    <?php upstream_output_project_members($project->id); ?>
                                                </td>

                                                <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, null, 0, 'progress', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>
                                                    <td data-column="progress"
                                                        data-value="<?php echo esc_attr($project->progress); ?>">
                                                        <div class="progress" style="margin-bottom: 0; height: 10px;">
                                                            <div
                                                                    class="progress-bar<?php echo $project->progress >= 100 ? ' progress-bar-success' : ""; ?>"
                                                                    role="progressbar"
                                                                    aria-valuenow="<?php echo esc_attr($project->progress); ?>"
                                                                    aria-valuemin="0" aria-valuemax="100"
                                                                    style="width: <?php echo $project->progress; ?>%;">
                                                            <span class="sr-only"><?php echo esc_html(sprintf(
                                                                    $i18n['LB_COMPLETE'],
                                                                    $project->progress . '%'
                                                                )); ?></span>
                                                            </div>
                                                        </div>
                                                        <small><?php echo esc_html(sprintf(
                                                                $i18n['LB_COMPLETE'],
                                                                $project->progress . '%'
                                                            )); ?></small>
                                                    </td>
                                                <?php else: ?>
                                                    <td data-column="progress"
                                                        data-value="">
                                                        <span class="label up-o-label"
                                                              style="background-color:#666;color:#fff">(hidden)</span>
                                                    </td>
                                                <?php endif; ?>



                                                <?php
                                                if ($project->status !== null && is_array($project->status)) {
                                                    $status = $project->status;
                                                } else {
                                                    $status = [
                                                        'id' => '',
                                                        'name' => '',
                                                        'color' => '#aaa',
                                                        'order' => '0',
                                                    ];
                                                }

                                                $statusOrder = array_search($status['id'], $projectOrder);
                                                ?>

                                                <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, null, 0, 'status', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>

                                                    <td data-column="status"
                                                        data-value="<?php echo !empty($status['id']) ? esc_attr($status['id']) : '__none__'; ?>"
                                                        data-order="<?php echo $statusOrder > 0 ? $statusOrder : '0'; ?>">
                                                        <?php if ($project->status !== null || empty($status['id']) || empty($status['name'])): ?>
                                                            <span class="label up-o-label"
                                                                  style="background-color: <?php echo esc_attr($status['color']); ?>;"><?php echo !empty($status['name']) ? esc_html($status['name']) : esc_html($i18n['LB_NONE']); ?></span>
                                                        <?php else: ?>
                                                            <i class="s-text-color-gray"><?php echo esc_html($i18n['LB_NONE']); ?></i>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php else: ?>
                                                    <td data-column="status"
                                                        data-value="">
                                                        <span class="label up-o-label"
                                                              style="background-color:#666;color:#fff">(hidden)</span>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, null, 0, 'categories', UPSTREAM_PERMISSIONS_ACTION_VIEW)): ?>
                                                    <td data-column="categories"
                                                        data-value="<?php echo count($project->categories) ? esc_attr(implode(
                                                            ',',
                                                            array_keys((array)$project->categories)
                                                        )) : '__none__'; ?>">
                                                        <?php if (count($project->categories) > 0): ?>
                                                            <?php echo esc_attr(implode(
                                                                ', ',
                                                                array_values((array)$project->categories)
                                                            )); ?>
                                                        <?php else: ?>
                                                            <i class="s-text-color-gray"><?php echo esc_html($i18n['LB_NONE']); ?></i>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php else: ?>
                                                    <td data-column="categories"
                                                        data-value="">
                                                        <span class="label up-o-label"
                                                              style="background-color:#666;color:#fff">(hidden)</span>
                                                    </td>
                                                <?php endif; ?>

                                                <?php do_action('upstream:project.columns.data', $tableSettings,
                                                    $columnsSchema, $project->id, $project); ?>
                                            </tr>

                                            <?php if ( ! empty($hiddenColumnsSchema)): ?>
                                                <tr data-parent="<?php echo $project->id; ?>" aria-expanded="false"
                                                    style="display: none;">
                                                    <td>
                                                        <div>
                                                            <?php foreach ($hiddenColumnsSchema as $columnName => $column):
                                                                $columnValue = isset($project->{$columnName}) ? $project->{$columnName} : null;
                                                                if (is_null($columnValue)) {
                                                                    continue;
                                                                }

                                                                if (is_array($columnValue) && isset($columnValue['value'])) {
                                                                    $columnValue = $columnValue['value'];
                                                                }
                                                                ?>
                                                                <div class="form-group"
                                                                     data-column="<?php echo esc_attr($columnName); ?>">
                                                                    <label><?php echo isset($column['label']) ? esc_html($column['label']) : ''; ?></label>
                                                                    <?php UpStream\Frontend\renderTableColumnValue(
                                                                        $columnName,
                                                                        $columnValue,
                                                                        $column,
                                                                        (array)$project,
                                                                        'project',
                                                                        $project->id
                                                                    ); ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif;

                                            $isProjectIndexOdd = ! $isProjectIndexOdd;
                                        endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <span class="p_count">
                                    Showing <?php print $display_start+1; ?> to <?php print min($display_start+$total_per_page, $totalProjects) ?> of <?php print $totalProjects ?>
                                </span>
                            <?php if ($totalProjects > $total_per_page) { ?>
                            <span class="pagination">
                                <?php if ($page > 1) { ?>  <a href="<?php print esc_url(add_query_arg('page', $page-1, $project_page_url)); ?>">&lt; Previous</a> <?php } ?>

                                    <select name="" onchange="if (this.value) window.location='<?php print esc_url(add_query_arg('page', '__PAGE_N_', $project_page_url)); ?>'.replace('__PAGE_N_',this.value);">
                                        <option>(Jump to page...)</option>
                                        <?php for ($j = 0; $j < ceil($totalProjects/$total_per_page); $j++): ?>
                                        <option value="<?php print $j+1 ?>"><?php print $j+1; ?></option>
                                        <?php endfor; ?>
                                    </select>

                                <?php if ($display_start + $total_per_page < $totalProjects) { ?> <a href="<?php print esc_url(add_query_arg('page', $page+1, $project_page_url)); ?>">Next &gt;</a> <?php } ?>
                            </span>

                            <?php } ?>
                            <?php else: ?>
                                <p><?php _e(
                                        "It seems that you're not participating in any project right now.",
                                        'upstream'
                                    ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php do_action('upstream:frontend.renderAfterProjectsList'); ?>
    </div>

<?php
do_action('upstream_after_project_list_content');

if (!apply_filters('upstream_theme_override_footer', false)) {
    upstream_get_template_part('global/footer.php');
}