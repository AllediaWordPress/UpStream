<?php
if (!defined('ABSPATH')) exit;

$blogInfoUrl = get_bloginfo('url');

$projectsListURL = get_post_type_archive_link('project');

$labelProjectPlural = upstream_project_label_plural();
$labelTaskPlural = upstream_task_label_plural();
$labelBugPlural = upstream_bug_label_plural();

$areMilestonesDisabledAtAll = upstream_disable_milestones();
$areMilestonesDisabledForThisProject = upstream_are_milestones_disabled();
$areTasksDisabledAtAll = upstream_disable_tasks();
$areTasksDisabledForThisProject = upstream_are_tasks_disabled();
$areBugsDisabledAtAll = upstream_disable_bugs();
$areBugsDisabledForThisProject = upstream_are_bugs_disabled();
$areFilesDisabledForThisProject = upstream_are_files_disabled();
?>

<?php do_action('upstream_before_sidebar'); ?>

<div class="col-md-3 left_col">
    <div class="left_col scroll-view">
        <div class="navbar nav_title">
            <a href="<?php echo $blogInfoUrl; ?>" class="site_title">
                <span><?php echo get_bloginfo('name'); ?></span>
            </a>
        </div>

        <div class="clearfix"></div>

        <!-- menu profile quick info -->
        <div class="profile">
            <div class="profile_pic">
                <img src="<?php echo upstream_current_user('avatar'); ?>" alt="" class="img-circle profile_img">
            </div>
            <div class="profile_info">
                <h2><?php echo esc_html(upstream_current_user('fname')); ?></h2>
                <p><?php echo esc_html(upstream_current_user('role')); ?></p>
            </div>
        </div>
        <!-- /menu profile quick info -->

        <br />

        <!-- sidebar menu -->
        <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
            <div class="menu_section">
                <h3>&nbsp;</h3>
                <ul class="nav side-menu">
                    <li>
                        <a>
                            <i class="fa fa-home"></i> <?php echo $labelProjectPlural; ?>
                            <span class="fa fa-chevron-down"></span>
                        </a>
                        <ul class="nav child_menu">
                            <li>
                                <a href="<?php echo $projectsListURL; ?>">
                                    <?php printf(__('All %s', 'upstream'), $labelProjectPlural); ?>
                                </a>
                            </li>
                            <?php if (upstream_current_user('projects')): ?>
                                <?php foreach (upstream_current_user('projects') as $key => $project_id): ?>
                                <li>
                                    <a href="<?php echo get_permalink($project_id); ?>">
                                        <?php echo get_the_title($project_id); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </li>
                </ul>
            </div>

            <?php if (is_single() && get_post_type() === 'project'): ?>
                <div class="menu_section">
                    <h3><?php echo get_the_title(get_the_ID()); ?></h3>
                    <ul class="nav side-menu">
                        <?php do_action('upstream_sidebar_before_single_menu'); ?>

                        <?php if (!$areMilestonesDisabledForThisProject && !$areMilestonesDisabledAtAll): ?>
                        <li>
                            <a href="#milestones">
                                <i class="fa fa-flag"></i> <?php echo upstream_milestone_label_plural(); ?>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (!$areTasksDisabledForThisProject && !$areTasksDisabledAtAll): ?>
                        <li>
                            <a href="#tasks">
                                <i class="fa fa-wrench"></i> <?php echo $labelTaskPlural; ?>
                                <?php do_action( 'upstream_sidebar_after_tasks_menu' ); ?>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (!$areBugsDisabledAtAll && !$areBugsDisabledForThisProject): ?>
                        <li>
                            <a href="#bugs">
                                <i class="fa fa-bug"></i> <?php echo $labelBugPlural; ?>
                                <?php do_action( 'upstream_sidebar_after_bugs_menu' ); ?>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (!$areFilesDisabledForThisProject && !upstream_disable_files()): ?>
                        <li>
                            <a href="#files">
                                <i class="fa fa-file"></i> <?php echo upstream_file_label_plural(); ?>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php do_action( 'upstream_sidebar_after_single_menu' );  ?>

                        <?php if ((!$areTasksDisabledForThisProject && !$areTasksDisabledAtAll) || (!$areBugsDisabledAtAll && !$areBugsDisabledForThisProject)): ?>
                        <li>
                            <hr style="border-top-color: rgba(0, 0, 0, 0.2);" />
                        </li>
                        <?php endif; ?>

                        <?php if (!$areTasksDisabledForThisProject && !$areTasksDisabledAtAll): ?>
                        <li>
                            <a href="#my-tasks">
                                <i class="fa fa-wrench"></i> My <?php echo $labelTaskPlural; ?>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (!$areBugsDisabledAtAll && !$areBugsDisabledForThisProject): ?>
                        <li>
                            <a href="#my-bugs">
                                <i class="fa fa-bug"></i> My <?php echo $labelBugPlural; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <!-- /sidebar menu -->

        <!-- /menu footer buttons -->
        <div class="sidebar-footer hidden-small">
            <a href="<?php echo $projectsListURL; ?>" data-toggle="tooltip" data-placement="top" title="<?php printf(__('My %s', 'upstream'), $labelProjectPlural); ?>">
                <i class="fa fa-home"></i>
            </a>
            <a href="<?php echo esc_url( upstream_admin_support() ); ?>" data-toggle="tooltip" data-placement="top" title="<?php _e('Support Link', 'upstream'); ?>">
                <i class="fa fa-envelope-o"></i>
            </a>
            <a href="<?php echo esc_url(upstream_logout_url()); ?>" data-toggle="tooltip" data-placement="top" title="<?php _e('Log Out', 'upstream'); ?>">
                <i class="fa fa-sign-out"></i>
            </a>
        </div>
        <!-- /menu footer buttons -->
    </div>
</div>

<?php do_action( 'upstream_after_sidebar' ); ?>
