<?php
if ( ! defined('ABSPATH')) {
    exit;
}
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
                    var mpiechart_options = {title:'mpc',
                        pieHole:0.4,
                        width:'33%',
                        is3D: true,
                        height:300};
                    var mpiechart = new google.visualization.PieChart(document.getElementById('mpiechart_div'));
                    mpiechart.draw(data, mpiechart_options);

                    var tpiechart_options = {title:'tpc',
                        pieHole:0.4,
                        width:'33%',
                        is3D: true,
                        height:300};
                    var tpiechart = new google.visualization.PieChart(document.getElementById('tpiechart_div'));
                    tpiechart.draw(data, tpiechart_options);

                    var bpiechart_options = {title:'vp',
                        pieHole:0.4,
                        width:'33%',
                        is3D: true,
                        height:300};
                    var bpiechart = new google.visualization.PieChart(document.getElementById('bpiechart_div'));
                    bpiechart.draw(data, bpiechart_options);

                }
            </script>

            <div id="mpiechart_div" style="width:33%;position: relative;display: inline-block"></div>
            <div id="tpiechart_div" style="width:33%;position: relative;display: inline-block"></div>
            <div id="bpiechart_div" style="width:33%;position: relative;display: inline-block"></div>
        </div>
    </div>
</div>
