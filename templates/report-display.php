<?php
/**
 * The Template for displaying a report parameters
 *
 * This template can be overridden by copying it to yourtheme/upstream/report-parameters.php.
 *
 *
 */

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once dirname(__FILE__) . '/../includes/admin/metaboxes/metabox-functions.php';

// Some hosts disable this function, so let's make sure it is enabled before call it.
if (function_exists('set_time_limit')) {
    set_time_limit(120);
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


upstream_get_template_part('global/header.php');
upstream_get_template_part('global/sidebar.php');
upstream_get_template_part('global/top-nav.php');


$report = UpStream_Report_Generator::getReport($_GET['report']);
if (!$report) {
    return;
}
?>

<script type="text/javascript">

jQuery(document).ready(function ($) {
    // Load the Visualization API and the piechart package.
    google.charts.load('current', {'packages': [ 'gantt']});

    // Set a callback to run when the Google Visualization API is loaded.
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {

        data = <?php echo json_encode(UpStream_Report_Generator::getReportFieldsFromPost(false)); ?>;
        data['report'] = '<?php echo $report->id; ?>';
        data['action'] = 'upstream_report_data';
        data['nonce'] = upstream.security;

        var jsonData = $.ajax({
            url: upstream.ajaxurl,
            type: 'post',
            dataType: "json",
            async: false,
            data: data
        }).responseText;

        // Create our data table out of JSON data loaded from server.
        var data = new google.visualization.DataTable(jsonData);
        var jo = JSON.parse(jsonData);
        var options = [];

        if ('options' in jo) {
            options = jo['options'];
        }

        options['width'] = '100%';
        options['height'] = 500;

        // Instantiate and draw our chart, passing in some options.
        var chart = new google.visualization.Gantt(document.getElementById('table_div'));
        chart.draw(data, options);
    }
});
</script>

<div class="right_col" role="main">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel" data-section="report-parameters-<?php echo $sectionId; ?>">
            <div class="x_title">
                <h2>
                    <?php esc_html_e($report->title); ?>
                </h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <div id="table_div"></div>
            </div>
        </div>
    </div>
</div>
<?php

include_once 'global/footer.php';
?>
