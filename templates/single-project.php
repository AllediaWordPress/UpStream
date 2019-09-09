<?php
/**
 * The Template for displaying a single project
 *
 * This template can be overridden by copying it to yourtheme/upstream/single-project.php.
 *
 *
 */

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// redirect to projects if no permissions for this project
if ( ! upstream_user_can_access_project(get_current_user_id(), upstream_post_id())) {
    wp_redirect(get_post_type_archive_link('project'));
    exit;
}

// Some hosts disable this function, so let's make sure it is enabled before call it.
if (function_exists('set_time_limit')) {
    set_time_limit(120);
}

add_action('init', function() { if(!session_id()) {session_start();}  }, 9);

$currentUser = (object)upstream_user_data();

$projectsList = [];
if (isset($currentUser->projects)) {
    if (is_array($currentUser->projects) && count($currentUser->projects) > 0) {
        foreach ($currentUser->projects as $project_id => $project) {
            $data = (object)[
                'id'          => $project_id,
                'author'      => (int)$project->post_author,
                'created_at'  => (string)$project->post_date_gmt,
                'modified_at' => (string)$project->post_modified_gmt,
                'title'       => $project->post_title,
                'slug'        => $project->post_name,
                'status'      => $project->post_status,
                'permalink'   => get_permalink($project_id),
            ];

            $projectsList[$project_id] = $data;
        }

        unset($project, $project_id);
    }

    unset($currentUser->projects);
}

$projectsListCount = count($projectsList);

upstream_get_template_part('global/header.php');
upstream_get_template_part('global/sidebar.php');
upstream_get_template_part('global/top-nav.php');

/*
 * upstream_single_project_before hook.
 */
do_action('upstream_single_project_before');

$user = upstream_user_data();

$options                = (array)get_option('upstream_general');
$displayOverviewSection = ! isset($options['disable_project_overview']) || (bool)$options['disable_project_overview'] === false;
$displayDetailsSection  = ! isset($options['disable_project_details']) || (bool)$options['disable_project_details'] === false;

/**
 * @param bool $displayOverviewSection
 *
 * @return bool
 */
$displayOverviewSection = apply_filters('upstream_display_overview_section', $displayOverviewSection);

/**
 * @param bool $displayDetailsSection
 *
 * @return bool
 */
$displayDetailsSection = apply_filters('upstream_display_details_section', $displayDetailsSection);

unset($options);

/*
 * Sections
 */
$sections = [
    'details',
    'milestones',
    'tasks',
    'bugs',
    'files',
    'discussion',
];
$sections = apply_filters('upstream_panel_sections', $sections);

// Apply the order to the panels.
$sectionsOrder = (array)\UpStream\Frontend\getPanelOrder();
$sections      = array_merge($sectionsOrder, $sections);
// Remove duplicates.
$sections = array_unique($sections);

while (have_posts()) : the_post(); ?>

    <!-- page content -->
    <div class="right_col" role="main">
        <div class="alerts">
            <?php do_action('upstream_frontend_projects_messages'); ?>
            <?php do_action('upstream_single_project_before_overview'); ?>
    </div>

        <div id="project-dashboard" class="sortable">
            <?php foreach ($sections as $section) : ?>
                <?php switch ($section) :
                    case 'details':
                        ?>
                        <div class="row" id="project-section-details">
                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-5">
                                <h3 style="display: inline-block;"><?php echo get_the_title(get_the_ID()); ?></h3>
                                <?php $status = upstream_project_status_color($id); ?>
                                <?php if ( ! empty($status['status'])): ?>
                                    <span class="label up-o-label"
                                          style="background-color: <?php echo esc_attr($status['color']); ?>"><?php echo $status['status']; ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($displayOverviewSection): ?>
                                <?php include 'single-project/overview.php'; ?>
                            <?php endif; ?>

                            <?php if ($displayDetailsSection): ?>
                                <?php do_action('upstream_single_project_before_details'); ?>
                                <?php upstream_get_template_part('single-project/details.php'); ?>
                            <?php endif; ?>
                        </div>
                        <?php
                        break;

                    case 'milestones':
                        if ( ! upstream_are_milestones_disabled() && ! upstream_disable_milestones()): ?>
                            <div class="row" id="project-section-milestones">
                            <?php do_action('upstream_single_project_before_milestones'); ?>

                            <?php upstream_get_template_part('single-project/milestones.php'); ?>

                            <?php do_action('upstream_single_project_after_milestones'); ?>
                        </div>
                        <?php endif;
                        break;

                    case 'tasks':
                        if ( ! upstream_are_tasks_disabled() && ! upstream_disable_tasks()): ?>
                            <div class="row" id="project-section-tasks">
                            <?php do_action('upstream_single_project_before_tasks'); ?>

                            <?php upstream_get_template_part('single-project/tasks.php'); ?>

                            <?php do_action('upstream_single_project_after_tasks'); ?>
                        </div>
                        <?php endif;
                        break;

                    case 'bugs':
                        if ( ! upstream_disable_bugs() && ! upstream_are_bugs_disabled()): ?>
                            <div class="row" id="project-section-bugs">
                            <?php do_action('upstream_single_project_before_bugs'); ?>

                            <?php upstream_get_template_part('single-project/bugs.php'); ?>

                            <?php do_action('upstream_single_project_after_bugs'); ?>
                        </div>
                        <?php endif;
                        break;

                    case 'files':
                        if ( ! upstream_are_files_disabled() && ! upstream_disable_files()): ?>
                            <div class="row" id="project-section-files">
                            <?php do_action('upstream_single_project_before_files'); ?>

                            <?php upstream_get_template_part('single-project/files.php'); ?>

                            <?php do_action('upstream_single_project_after_files'); ?>
                        </div>
                        <?php endif;
                        break;

                    case 'discussion':
                        if (upstreamAreProjectCommentsEnabled()): ?>
                            <div class="row" id="project-section-discussion">
                            <?php do_action('upstream_single_project_before_discussion'); ?>

                            <?php upstream_get_template_part('single-project/discussion.php'); ?>

                            <?php do_action('upstream_single_project_after_discussion'); ?>
                        </div>
                        <?php endif;
                        break;

                    default:
                        do_action('upstream_single_project_section_' . $section, upstream_post_id());

                        break;

                endswitch; ?>
            <?php endforeach; ?>


        </div>
    </div>
    <input type="hidden" id="project_id" value="<?php echo upstream_post_id(); ?>">
<?php endwhile;
/**
 * upstream_after_project_content hook.
 *
 */
do_action('upstream_after_project_content');

include_once 'global/footer.php';
?>
