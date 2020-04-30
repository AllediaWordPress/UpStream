<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

$mm = \UpStream_Model_Manager::get_instance();
$projects = $mm->findAccessibleProjects();

$categories = get_terms([ 'taxonomy' => 'project_category' ]);

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
                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 text-left">
                    <p class="title"><?php echo esc_html(upstream_project_label_plural()); ?></p>

                    <select class="form-control" multiple name="p1">
                        <?php foreach ($projects as $project): ?>
                            <option><?php esc_html_e($project->title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 text-left">
                    <p class="title"><?php echo esc_html(upstream_project_label_plural()); ?></p>

                    <select class="form-control" multiple name="p2">
                        <?php foreach ($categories as $category): ?>
                            <option><?php esc_html_e($category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php upstream_get_template_part('report-parameters/fields.php'); ?>


        </div>
    </div>
</div>
