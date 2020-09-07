<?php
/**
 * The Template for displaying a report parameters
 *
 * This template can be overridden by copying it to yourtheme/upstream/report-parameters.php.
 *
 *
 */

if ( ! defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/../includes/admin/metaboxes/metabox-functions.php';

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


if (!apply_filters('upstream_theme_override_header', false)) {
    upstream_get_template_part('global/header.php');
}

if (!apply_filters('upstream_theme_override_sidebar', false)) {
    upstream_get_template_part('global/sidebar.php');
}

if (!apply_filters('upstream_theme_override_topnav', false)) {
    upstream_get_template_part('global/top-nav.php');
}

$report = UpStream_Report_Generator::get_instance()->getReport($_GET['report']);
if (!$report) {
    return;
}

$display_fields = [];

?>

    <div class="right_col" role="main">

        <form action="<?php $_SERVER['REQUEST_URI']; ?>" method="post">

            <?php foreach ($report->getAllFieldOptions() as $sectionId => $optionInfo): ?>
                <div id="report-parameters-<?php echo $optionInfo['type'] ?>>">
                    <?php include('report-parameters/section.php'); ?>
                </div>
            <?php endforeach; ?>

            <?php include('report-parameters/display-fields.php'); ?>

           <input type="submit" name="submit" value="Submit">
        </form>
    </div>

<?php


if (!apply_filters('upstream_theme_override_footer', false)) {
    upstream_get_template_part('global/footer.php');
}
?>
