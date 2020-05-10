<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

$type = $optionInfo['type'];

$mm = \UpStream_Model_Manager::get_instance();
$projects = $mm->findAccessibleProjects();

$fields = [];
$typeInfo = "";

switch ($type) {
    case 'project':
        $typeInfo = upstream_project_label_plural();
        $fields = UpStream_Model_Project::fields();
        break;
    case 'milestone':
        $typeInfo = upstream_milestone_label_plural();
        $fields = UpStream_Model_Milestone::fields();
        break;
    case 'task':
        $typeInfo = upstream_task_label_plural();
        $fields = UpStream_Model_Task::fields();
        break;
    case 'bug':
        $typeInfo = upstream_bug_label_plural();
        $fields = UpStream_Model_Bug::fields();
        break;
    case 'file':
        $typeInfo = upstream_file_label_plural();
        $fields = UpStream_Model_File::fields();
        break;
}

?>
<div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel" data-section="report-parameters-<?php echo $sectionId; ?>">
        <div class="x_title">
            <h2>
                <?php echo esc_html($typeInfo); ?>
            </h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">

            <div class="row">
                <p class="title">
                    <?php echo esc_html($typeInfo); ?>
                </p>

                <select class="form-control" multiple name="<?php echo $sectionId ?>">
                    <?php foreach ($projects as $project): ?>
                        <?php if ($type == 'project'): ?>
                            <option value="<?php echo $project->id ?>"><?php esc_html_e($project->title); ?></option>
                        <?php else: ?>
                            <option disabled><?php esc_html_e($project->title); ?></option>
                        <?php endif; ?>

                        <?php
                        if ($type == 'milestone' || $type == 'bug' || $type == 'file' || $type == 'task') {

                            $children = [];
                            if ($type == 'bug') $children = &$project->bugs();
                            elseif ($type == 'file') $children = &$project->files();
                            elseif ($type == 'task') $children = &$project->tasks();
                            elseif ($type == 'milestone') $children = $project->findMilestones();

                            foreach ($children as $child) {
                                ?>
                                <option value="<?php echo $child->id ?>">&emsp;<?php esc_html_e($child->title); ?></option>
                                <?php
                            }

                        }
                        ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php include('search-fields.php'); ?>
            <?php include('display-fields.php'); ?>


        </div>
    </div>
</div>
