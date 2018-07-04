<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$isSingle = is_single();
if ( $isSingle ) {
    $areMilestonesDisabledAtAll          = upstream_disable_milestones();
    $areMilestonesDisabledForThisProject = upstream_are_milestones_disabled();
    $areTasksDisabledAtAll               = upstream_disable_tasks();
    $areTasksDisabledForThisProject      = upstream_are_tasks_disabled();
    $areBugsDisabledAtAll                = upstream_disable_bugs();
    $areBugsDisabledForThisProject       = upstream_are_bugs_disabled();
    $areFilesDisabledForThisProject      = upstream_are_files_disabled();
    $areCommentsDisabled                 = upstream_are_comments_disabled();
}
?>

<?php do_action( 'upstream_before_sidebar' ); ?>

<div class="col-md-3 left_col">
    <div class="left_col scroll-view">
        <div class="navbar nav_title">
            <a href="<?php echo esc_url( $siteUrl ); ?>" class="site_title">
                <span><?php echo esc_html( $pageTitle ); ?></span>
            </a>
        </div>
        <div class="clearfix"></div>
        <!-- menu profile quick info -->
        <div class="profile">
            <div class="profile_pic">
                <img src="<?php echo esc_url( $currentUser->avatar ); ?>" alt="" class="img-circle profile_img">
            </div>
            <div class="profile_info">
                <h2><?php echo esc_html( $currentUser->display_name ); ?></h2>
                <p><?php echo esc_html( $currentUser->role ); ?></p>
            </div>
        </div>

        <!-- /menu profile quick info -->
        <br/>
        <!-- sidebar menu -->
        <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
            <div class="menu_section">
                <h3>&nbsp;</h3>
                <ul class="nav side-menu">
                    <li>
                        <a href="<?php echo esc_attr( $projectsListUrl ); ?>">
                            <i class="fa fa-home"></i>
                            <?php echo esc_html( $i18n['LB_PROJECTS'] ); ?>
                        </a>
                        <ul class="nav child_menu">
                            <li>
                                <a href="<?php echo esc_attr( $projectsListUrl ); ?>">
                                    <i class="fa fa-columns"></i> <?php printf( __( 'All %s', 'upstream' ), $i18n['LB_PROJECTS'] ); ?>
                                </a>

                                <?php do_action( 'upstream_sidebar_after_all_projects_link' ); ?>
                            </li>

                            <?php do_action( 'upstream_sidebar_projects_submenu' ); ?>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php if ( $isSingle && get_post_type() === 'project' ): ?>
                <?php $project_id = get_the_ID(); ?>
                <div class="menu_section active">
                    <ul class="nav side-menu">
                        <li class="current-page active">
                            <a href="#">
                                <i class="fa fa-folder"></i>
                                <?php echo get_the_title( $project_id ); ?>
                            </a>

                            <ul class="nav child_menu" style="display: block;">
                                <?php do_action( 'upstream_sidebar_before_single_menu' ); ?>

                                <?php if ( ! $areMilestonesDisabledForThisProject && ! $areMilestonesDisabledAtAll ): ?>
                                    <li>
                                        <a href="#milestones">
                                            <i class="fa fa-flag"></i> <?php echo upstream_milestone_label_plural(); ?>
                                            <?php
                                            if ( function_exists( 'countItemsForUserOnProject' ) ) {
                                                $itemsCount = countItemsForUserOnProject( 'milestones', get_current_user_id(),
                                                    upstream_post_id() );
                                            } else {
                                                $itemsCount = (int) upstream_count_assigned_to( 'milestones' );
                                            }

                                            if ( $itemsCount > 0 ): ?>
                                                <span class="label label-info pull-right" data-toggle="tooltip"
                                                      title="<?php _e( 'Assigned to me', 'upstream' ); ?>"
                                                      style="margin-top: 3px;"><?php echo $itemsCount; ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ( ! $areTasksDisabledForThisProject && ! $areTasksDisabledAtAll ): ?>
                                    <li>
                                        <a href="#tasks">
                                            <i class="fa fa-wrench"></i> <?php echo $i18n['LB_TASKS']; ?>
                                            <?php
                                            if ( function_exists( 'countItemsForUserOnProject' ) ) {
                                                $itemsCount = countItemsForUserOnProject( 'tasks', get_current_user_id(),
                                                    upstream_post_id() );
                                            } else {
                                                $itemsCount = (int) upstream_count_assigned_to( 'tasks' );
                                            }

                                            if ( $itemsCount > 0 ): ?>
                                                <span class="label label-info pull-right" data-toggle="tooltip"
                                                      title="<?php _e( 'Assigned to me', 'upstream' ); ?>"
                                                      style="margin-top: 3px;"><?php echo $itemsCount; ?></span>
                                            <?php endif; ?>
                                            <?php do_action( 'upstream_sidebar_after_tasks_menu' ); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ( ! $areBugsDisabledAtAll && ! $areBugsDisabledForThisProject ): ?>
                                    <li>
                                        <a href="#bugs">
                                            <i class="fa fa-bug"></i> <?php echo $i18n['LB_BUGS']; ?>
                                            <?php
                                            if ( function_exists( 'countItemsForUserOnProject' ) ) {
                                                $itemsCount = countItemsForUserOnProject( 'bugs', get_current_user_id(),
                                                    upstream_post_id() );
                                            } else {
                                                $itemsCount = (int) upstream_count_assigned_to( 'bugs' );
                                            }

                                            if ( $itemsCount > 0 ): ?>
                                                <span class="label label-info pull-right" data-toggle="tooltip"
                                                      title="<?php _e( 'Assigned to me', 'upstream' ); ?>"
                                                      style="margin-top: 3px;"><?php echo $itemsCount; ?></span>
                                            <?php endif; ?>
                                            <?php do_action( 'upstream_sidebar_after_bugs_menu' ); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ( ! $areFilesDisabledForThisProject && ! upstream_disable_files() ): ?>
                                    <li>
                                        <a href="#files">
                                            <i class="fa fa-file"></i> <?php echo upstream_file_label_plural(); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ( ! $areCommentsDisabled ): ?>
                                    <li>
                                        <a href="#discussion">
                                            <i class="fa fa-comments"></i>
                                            <?php echo upstream_discussion_label(); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php do_action( 'upstream_sidebar_after_single_menu' ); ?>
                            </ul>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <!-- /sidebar menu -->
        <!-- /menu footer buttons -->
        <div class="sidebar-footer hidden-small">
            <a href="<?php echo esc_attr( $projectsListUrl ); ?>" data-toggle="tooltip" data-placement="top"
               title="<?php printf( __( 'My %s', 'upstream' ), $i18n['LB_PROJECTS'] ); ?>">
                <i class="fa fa-home"></i>
            </a>
            <a href="<?php echo esc_url( $supportUrl ); ?>" data-toggle="tooltip" data-placement="top"
               title="<?php echo esc_attr( $i18n['MSG_SUPPORT'] ); ?>" target="_blank" rel="noreferrer noopener">
                <i class="fa fa-question-circle"></i>
            </a>
            <a href="<?php echo esc_url( $logOutUrl ); ?>" data-toggle="tooltip" data-placement="top"
               title="<?php echo esc_attr( $i18n['LB_LOGOUT'] ); ?>">
                <i class="fa fa-sign-out"></i>
            </a>
        </div>
        <!-- /menu footer buttons -->
    </div>
</div>

<?php do_action( 'upstream_after_sidebar' ); ?>
