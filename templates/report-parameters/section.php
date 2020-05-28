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
        $typeInfo = upstream_project_label();
        $fields = UpStream_Model_Project::fields();
        break;
    case 'milestone':
        $typeInfo = upstream_milestone_label();
        $fields = UpStream_Model_Milestone::fields();
        break;
    case 'task':
        $typeInfo = upstream_task_label();
        $fields = UpStream_Model_Task::fields();
        break;
    case 'bug':
        $typeInfo = upstream_bug_label();
        $fields = UpStream_Model_Bug::fields();
        break;
    case 'file':
        $typeInfo = upstream_file_label();
        $fields = UpStream_Model_File::fields();
        break;
}

?>
<div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel" data-section="report-parameters-<?php echo $sectionId; ?>">
        <div class="x_title">
            <h2>
                <?php echo esc_html($typeInfo . __(' Filters')); ?>
            </h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">

            <div class="row">

                <div class="col-lg-12 col-xs-12">
                    <div class="form-group">
                        <label><?php esc_html_e('Name') ?></label>
                        <select class="form-control" multiple name="upstream_report__<?php echo $sectionId ?>_id[]">
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
                                        if (upstream_override_access_object(true, $type, $child->id, UPSTREAM_ITEM_TYPE_PROJECT, $project->id, UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                                            ?>
                                            <option value="<?php echo $child->id ?>">
                                                &emsp;<?php esc_html_e($child->title); ?></option>
                                            <?php
                                        }
                                    }

                                }
                                ?>
                            <?php endforeach; ?>
                        </select>
                        <a onclick="jQuery('[name=\'upstream_report__<?php print $sectionId; ?>_id[]\'] option').prop('selected', true)">Select all</a> | <a onclick="jQuery('[name=\'upstream_report__<?php print $sectionId; ?>_id[]\'] option').prop('selected', false)">Select none</a>
                    </div>

                    <?php include('search-fields.php'); ?>
                </div>
            </div>
        </div>
    </div>
</div>