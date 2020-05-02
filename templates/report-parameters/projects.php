<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

$mm = \UpStream_Model_Manager::get_instance();
$projects = $mm->findAccessibleProjects();


?>
<div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel" data-section="report-parameters-project">
        <div class="x_title">
            <h2>
                <?php echo esc_html(upstream_project_label_plural()); ?>
            </h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">

            <div class="row">
                <p class="title"><?php echo esc_html(upstream_project_label_plural()); ?></p>

                <select class="form-control" multiple name="p1">
                    <?php foreach ($projects as $project): ?>
                        <option><?php esc_html_e($project->title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php upstream_get_template_part('report-parameters/search-fields.php'); ?>
            <?php upstream_get_template_part('report-parameters/display-fields.php'); ?>


        </div>
    </div>
</div>
