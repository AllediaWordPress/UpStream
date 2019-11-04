<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}
?>

<?php if (upstreamAreProjectCommentsEnabled() && ! upstream_are_comments_disabled()):
    $pluginOptions = get_option('upstream_general');
    $collapseBox = isset($pluginOptions['collapse_project_discussion']) && (bool)$pluginOptions['collapse_project_discussion'] === true;

    $collapseBoxState = \UpStream\Frontend\getSectionCollapseState('discussion');

    $projectId = upstream_post_id();

    if ( ! is_null($collapseBoxState)) {
        $collapseBox = $collapseBoxState === 'closed';
    }
    ?>

    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="x_panel" data-section="discussion">
            <div class="x_title" id="discussion">
                <h2>
                    <i class="fa fa-bars sortable_handler"></i>
                    <i class="fa fa-comments"></i> <?php echo esc_html(upstream_discussion_label()); ?>
                </h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li>
                        <a class="collapse-link">
                            <i class="fa fa-chevron-<?php echo $collapseBox ? 'down' : 'up'; ?>"></i>
                        </a>
                    </li>
                    <?php do_action('upstream_project_discussion_top_right', $projectId); ?>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" style="display: <?php echo $collapseBox ? 'none' : 'block'; ?>;">
      <?php upstreamRenderCommentsBox(); ?>
    </div>
        </div>
    </div>
<?php endif; ?>
