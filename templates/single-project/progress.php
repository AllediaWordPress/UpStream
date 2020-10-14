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

                    var data = new google.visualization.DataTable();
                    data.addColumn('string', 'Topping');
                    data.addColumn('number', 'Slices');
                    data.addRows([
                        ['Mushrooms', 3],
                        ['Onions', 1],
                        ['Olives', 1],
                        ['Zucchini', 1],
                        ['Pepperoni', 2]
                    ]);
/*
                    var barchart_options = {title:'Barchart: How Much Pizza I Ate Last Night',
                        width:'100%',
                        height:300,
                        legend: 'none'};
                    var barchart = new google.visualization.BarChart(document.getElementById('barchart_div'));
                    barchart.draw(data, barchart_options);
*/
                    var mchart_options = {title:'mpc',
                        legend:'none',
                        width:'33%',
                        is3D: true,
                        height:300};
                    var mchart = new google.visualization.BarChart(document.getElementById('mchart_div'));
                    mchart.draw(data, mchart_options);

                    var tchart_options = {title:'tpc',
                        legend:'none',
                        width:'33%',
                        is3D: true,
                        height:300};
                    var tchart = new google.visualization.BarChart(document.getElementById('tchart_div'));
                    tchart.draw(data, tchart_options);

                    var bchart_options = {title:'vp',
                        legend:'none',
                        width:'33%',
                        is3D: true,
                        height:300};
                    var bchart = new google.visualization.BarChart(document.getElementById('bchart_div'));
                    bchart.draw(data, bchart_options);

                }
            </script>

            <div id="mchart_div" style="width:33%;position: relative;display: inline-block"></div>
            <div id="tchart_div" style="width:33%;position: relative;display: inline-block"></div>
            <div id="bchart_div" style="width:33%;position: relative;display: inline-block"></div>
        </div>
    </div>
</div>
