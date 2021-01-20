<?php
if ( ! defined('ABSPATH')) {
    exit;
}
global $upstream_allcounts;

$pluginOptions = get_option('upstream_general');
$collapseBox = isset($pluginOptions['collapse_project_progress']) && (bool)$pluginOptions['collapse_project_progress'] === true;

$collapseBoxState = \UpStream\Frontend\getSectionCollapseState('progress');

$projectId = upstream_post_id();

if ($collapseBoxState !== false) {
    $collapseBox = $collapseBoxState === 'closed';
}

$count_enabled = 0;
if (!upstream_disable_tasks()) $count_enabled++;
if (!upstream_disable_bugs()) $count_enabled++;


$manager = \UpStream_Model_Manager::get_instance();
$project = $manager->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $projectId);

?>

<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="x_panel" data-section="progress">
        <div class="x_title" id="progress">
            <h2>
                <i class="fa fa-bars sortable_handler"></i>
                <i class="fa fa-comments"></i> <?php _e('Progress', 'upstream'); ?>
            </h2>
            <ul class="nav navbar-right panel_toolbox">
                <li>
                    <a class="collapse-link">
                        <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
                    </a>
                </li>
            </ul>
            <div class="clearfix"></div>
        </div>
        <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
            <script type="text/javascript">

                google.charts.load('current', {'packages':['bar', 'corechart']});
                google.charts.setOnLoadCallback(drawChart);

                function drawChart() {
                    var data = [ [
                        "",
                        <?php print json_encode(__('Open', 'upstream')); ?>,
                        <?php print json_encode(__('Assigned to me', 'upstream')); ?>,
                        <?php print json_encode(__('Overdue', 'upstream')); ?>,
                        <?php print json_encode(__('Completed', 'upstream')); ?>,
                        <?php print json_encode(__('Total', 'upstream')); ?> ]
                    ];

                    <?php if (!upstream_disable_milestones()): ?>
                    var milestone_open = parseInt("<?php echo $upstream_allcounts['milestonesCounts']['open']; ?>");
                    var milestone_mine = parseInt("<?php echo $upstream_allcounts['milestonesCounts']['mine']; ?>");
                    var milestone_overdue = parseInt("<?php echo $upstream_allcounts['milestonesCounts']['overdue']; ?>");
                    var milestone_finished = parseInt("<?php echo $upstream_allcounts['milestonesCounts']['finished']; ?>");
                    var milestone_total = parseInt("<?php echo $upstream_allcounts['milestonesCounts']['total']; ?>");

                    data.push([<?php print json_encode(upstream_milestone_label_plural()) ?>,
                        milestone_open,
                        milestone_mine,
                        milestone_overdue,
                        milestone_finished,
                        milestone_total]);
                    <?php endif; ?>

                    <?php if (!upstream_disable_tasks()): ?>
                    var task_open = parseInt("<?php echo $upstream_allcounts['tasksCounts']['open']; ?>");
                    var task_mine = parseInt("<?php echo $upstream_allcounts['tasksCounts']['mine']; ?>");
                    var task_overdue = parseInt("<?php echo $upstream_allcounts['tasksCounts']['overdue']; ?>");
                    var task_finished = parseInt("<?php echo $upstream_allcounts['tasksCounts']['closed']; ?>");
                    var task_total = parseInt("<?php echo $upstream_allcounts['tasksCounts']['total']; ?>");

                    data.push([<?php print json_encode(upstream_task_label_plural()) ?>,
                        task_open,
                        task_mine,
                        task_overdue,
                        task_finished,
                        task_total]);
                    <?php endif; ?>

                    <?php if (!upstream_disable_bugs()): ?>
                    var bug_open = parseInt("<?php echo $upstream_allcounts['bugsCounts']['open']; ?>");
                    var bug_mine = parseInt("<?php echo $upstream_allcounts['bugsCounts']['mine']; ?>");
                    var bug_overdue = parseInt("<?php echo $upstream_allcounts['bugsCounts']['overdue']; ?>");
                    var bug_finished = parseInt("<?php echo $upstream_allcounts['bugsCounts']['closed']; ?>");
                    var bug_total = parseInt("<?php echo $upstream_allcounts['bugsCounts']['total']; ?>");

                    data.push([<?php print json_encode(upstream_bug_label_plural()) ?>,
                        bug_open,
                        bug_mine,
                        bug_overdue,
                        bug_finished,
                        bug_total]);
                    <?php endif; ?>

                    var options = {
                        chart: {
                            width: '100%'
                        }
                    };

                    var chart = new google.charts.Bar(document.getElementById('progress_chart_div'));
                    chart.draw(google.visualization.arrayToDataTable(data), google.charts.Bar.convertOptions(options));


                    <?php if (!upstream_disable_tasks()):

                        $tasks = $project->tasks();
                        $statusCounts = [];
                        foreach ($tasks as $t) {
                            if (upstream_override_access_object(true, UPSTREAM_ITEM_TYPE_TASK, $t->id, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                                if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_TASK, $t->id, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, 'title', UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                                    upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_TASK, $t->id, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, 'status', UPSTREAM_PERMISSIONS_ACTION_VIEW)
                                ) {
                                    if (trim($t->status) == "") {
                                       $key = __('None', 'upstream');
                                    } else {
                                        $key = $t->status;
                                    }
                                    if (isset($statusCounts[$key])) {
                                        $statusCounts[$key]++;
                                    }
                                    else {
                                        $statusCounts[$key] = 1;
                                    }
                                }
                            }
                        }

                    ?>
                    var data = google.visualization.arrayToDataTable([
                        ['', ''],
                        <?php foreach($statusCounts as $key => $value): ?>
                        [<?php print json_encode($key) ?>, parseInt("<?php print $value ?>")],
                        <?php endforeach; ?>
                    ]);

                    var options = {
                        pieSliceText: 'label',
                        legend: 'none',
                        pieSliceTextStyle: { color: '#aaa' },
                        height: 300,
                        pieHole: .5
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('task_chart_div'));
                    chart.draw(data, options);
                    <?php endif; ?>

                    <?php if (!upstream_disable_bugs()):


                    $bugs = $project->bugs();
                    $statusCounts = [];
                    foreach ($bugs as $t) {
                        if (upstream_override_access_object(true, UPSTREAM_ITEM_TYPE_BUG, $t->id, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                            if (upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_BUG, $t->id, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, 'title', UPSTREAM_PERMISSIONS_ACTION_VIEW) &&
                                upstream_override_access_field(true, UPSTREAM_ITEM_TYPE_BUG, $t->id, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, 'status', UPSTREAM_PERMISSIONS_ACTION_VIEW)
                            ) {
                                if (trim($t->status) == "") {
                                    $key = __('None', 'upstream');
                                } else {
                                    $key = $t->status;
                                }
                                if (isset($statusCounts[$key])) {
                                    $statusCounts[$key]++;
                                }
                                else {
                                    $statusCounts[$key] = 1;
                                }
                            }
                        }
                    }

                    ?>


                    var data = google.visualization.arrayToDataTable([
                        ['', ''],
                        <?php foreach($statusCounts as $key => $value): ?>
                        [<?php print json_encode($key) ?>, parseInt("<?php print $value ?>")],
                        <?php endforeach; ?>
                    ]);

                    var options = {
                        pieHole: .5,
                        legend: 'none',
                        pieSliceText: 'label',
                        pieSliceTextStyle: { color: '#aaa' },
                        height: 300
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('bug_chart_div'));
                    chart.draw(data, options);
                    <?php endif; ?>
                }
            </script>

            <div style="width:100%;display:block">
                <?php if (!upstream_disable_tasks()): ?>
                    <div style="width:<?php print esc_attr(100/$count_enabled-1) ?>%;position: relative;display: inline-block;text-align: center"><?php print esc_html(upstream_task_label_plural()) ?></div>
                <?php endif; ?>
                <?php if (!upstream_disable_bugs()): ?>
                    <div style="width:<?php print esc_attr(100/$count_enabled-1) ?>%;position: relative;display: inline-block;text-align: center"><?php print esc_html(upstream_bug_label_plural()) ?></div>
                <?php endif; ?>
            </div>

            <?php if (!upstream_disable_tasks()): ?>
                <div id="task_chart_div" style="width:<?php print esc_attr(100/$count_enabled-1) ?>%;position: relative;display: inline-block"></div>
            <?php endif; ?>
            <?php if (!upstream_disable_bugs()): ?>
                <div id="bug_chart_div" style="width:<?php print esc_attr(100/$count_enabled-1) ?>%;position: relative;display: inline-block"></div>
            <?php endif; ?>

            <div id="progress_chart_div" style="width:100%;position: relative;display: block"></div>
        </div>
    </div>
</div>
