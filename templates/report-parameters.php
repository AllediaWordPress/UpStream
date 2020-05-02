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

    <div class="right_col" role="main">

        <form action="<?php $_SERVER['REQUEST_URI']; ?>" method="post">

            <?php if (is_array($report->getFieldOption('projects'))): ?>
            <div id="report-parameters-project">
                <?php upstream_get_template_part('report-parameters/projects.php'); ?>
            </div>
            <?php endif; ?>

           <input type="submit" name="submit" value="Submit">
        </form>
    </div>
<?php

include_once 'global/footer.php';
?>
