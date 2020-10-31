<?php
if ( ! defined('ABSPATH')) {
    exit;
}

global $upstream_allcounts;


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

                google.charts.load('current', {'packages':['corechart']});
                google.charts.setOnLoadCallback(drawChart);

                function drawChart() {
                    //mchart start
                    var data_mchart = new google.visualization.DataTable();
                    data_mchart.addColumn('string', 'Topping');
                    data_mchart.addColumn('number', 'Slices');
                    data_mchart.addColumn({type: 'string', role: 'style'});
                    var milestone_open = parseInt("<?php echo $upstream_allcounts['milestonesCounts']['open']; ?>");
                    var milestone_mine = parseInt("<?php echo $upstream_allcounts['milestonesCounts']['mine']; ?>");
                    var milestone_overdue = parseInt("<?php echo $upstream_allcounts['milestonesCounts']['overdue']; ?>");
                    var milestone_finished = parseInt("<?php echo $upstream_allcounts['milestonesCounts']['finished']; ?>");
                    var milestone_total = parseInt("<?php echo $upstream_allcounts['milestonesCounts']['total']; ?>");
                    data_mchart.addRows([
                        ['Open', milestone_open, 'color: yellow'],
                        ['Mine', milestone_mine, 'color: yellow'],
                        ['Overdue', milestone_overdue, 'color: yellow'],
                        ['Finished', milestone_finished, 'color: yellow'],
                        ['Total', milestone_total, 'color: yellow']
                    ]);
                    var mchart_options = {title:'mpc',
                        legend:'none',
                        width:'33%',
                        is3D: true,
                        height:300};
                    var mchart = new google.visualization.BarChart(document.getElementById('mchart_div'));
                    mchart.draw(data_mchart, mchart_options);
                    //mchart end
                    //tchart start
                    var data_tchart = new google.visualization.DataTable();
                    data_tchart.addColumn('string', 'Topping');
                    data_tchart.addColumn('number', 'Slices');
                    data_tchart.addColumn({type: 'string', role: 'style'});
                    var task_open = parseInt("<?php echo $upstream_allcounts['tasksCounts']['open']; ?>");
                    var task_mine = parseInt("<?php echo $upstream_allcounts['tasksCounts']['mine']; ?>");
                    var task_overdue = parseInt("<?php echo $upstream_allcounts['tasksCounts']['overdue']; ?>");
                    var task_finished = parseInt("<?php echo $upstream_allcounts['tasksCounts']['finished']; ?>");
                    var task_total = parseInt("<?php echo $upstream_allcounts['tasksCounts']['total']; ?>");
                    data_tchart.addRows([
                        ['Open', task_open, 'color: red'],
                        ['Mine', task_mine, 'color: orange'],
                        ['Overdue', task_overdue, 'color: yellow'],
                        ['Finished', task_finished, 'color: green'],
                        ['Total', task_total, 'color: blue']
                    ]);
                    var tchart_options = {title:'tpc',
                        legend:'none',
                        width:'33%',
                        is3D: true,
                        height:300};
                    var tchart = new google.visualization.BarChart(document.getElementById('tchart_div'));
                    tchart.draw(data_tchart, tchart_options);
                    //tchart end
                    //bchart start
                    var data_bchart = new google.visualization.DataTable();
                    data_bchart.addColumn('string', 'Topping');
                    data_bchart.addColumn('number', 'Slices');
                    data_bchart.addColumn({type: 'string', role: 'style'});
                    var bug_open = parseInt("<?php echo $upstream_allcounts['bugsCounts']['open']; ?>");
                    var bug_mine = parseInt("<?php echo $upstream_allcounts['bugsCounts']['mine']; ?>");
                    var bug_overdue = parseInt("<?php echo $upstream_allcounts['bugsCounts']['overdue']; ?>");
                    var bug_finished = parseInt("<?php echo $upstream_allcounts['bugsCounts']['finished']; ?>");
                    var bug_total = parseInt("<?php echo $upstream_allcounts['bugsCounts']['total']; ?>");
                    data_bchart.addRows([
                        ['Open', bug_open, 'color: green'],
                        ['Mine', bug_mine, 'color: green'],
                        ['Overdue', bug_overdue, 'color: green'],
                        ['Finished', bug_finished, 'color: green'],
                        ['Total', bug_total, 'color: green']
                    ]);
                    var bchart_options = {title:'vp',
                        legend:'none',
                        width:'100%',
                        is3D: true,
                        height:300};
                    var bchart = new google.visualization.BarChart(document.getElementById('bchart_div'));
                    bchart.draw(data_bchart, bchart_options);
                    //bchart end
                }
            </script>

            <div id="mchart_div" style="width:33%;position: relative;display: inline-block"></div>
            <div id="tchart_div" style="width:33%;position: relative;display: inline-block"></div>
            <div id="bchart_div" style="width:33%;position: relative;display: inline-block"></div>

        </div>
    </div>
</div>
