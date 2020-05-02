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


UpStream_Report_Generator::executeReport($report);

include_once 'global/footer.php';
?>
