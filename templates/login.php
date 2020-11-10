<?php
if ( ! defined('ABSPATH')) {
    exit;
}

if (is_archive() && (empty($_SESSION) || empty($_SESSION['upstream']) || empty($_SESSION['upstream']['user_id'])) && ! is_user_logged_in()) {
    wp_redirect(wp_login_url(get_post_type_archive_link('project')));
    exit;
}

$headerText = upstream_login_heading();

$pluginOptions = get_option('upstream_general');

$shouldDisplayClientLogo = isset($pluginOptions['login_client_logo']) ? (bool)$pluginOptions['login_client_logo'] : false;
if ($shouldDisplayClientLogo) {
    $clientLogoURL = upstream_client_logo();
}

$shouldDisplayProjectName = isset($pluginOptions['login_project_name']) ? $pluginOptions['login_project_name'] : false;
if ($shouldDisplayProjectName) {
    $headerText .= ! empty($headerText) ? '<br />' . '<small>' . get_the_title() . '</small>' : get_the_title();
}

$login = new UpStream_Login();

$userEmail = ! empty($_POST) && isset($_POST['user_email']) ? $_POST['user_email'] : "";
?>

<?php upstream_get_template_part('global/header.php'); ?>

<div class="col-xs-12 col-sm-4 col-sm-offset-4 text-center">
    <?php if ($shouldDisplayClientLogo && ! empty($clientLogoURL)): ?>
        <img src="<?php echo $clientLogoURL; ?>"/>
    <?php endif; ?>

    <div class="account-wall">
        <?php if ( ! empty($headerText)): ?>
            <header>
                <h3 class="text-center"><?php echo $headerText; ?></h3>
            </header>
        <?php endif; ?>

        <?php do_action('upstream_login_before_form'); ?>

        <form class="loginform" action="" method="POST">
            <input type="text" class="form-control" placeholder="<?php _e('Your Email', 'upstream'); ?>"
                   name="user_email" required
                   value="<?php echo esc_attr($userEmail); ?>" <?php echo empty($userEmail) ? 'autofocus' : ''; ?> />
            <input type="password" class="form-control" placeholder="<?php _e('Password', 'upstream'); ?>"
                   name="user_password" required <?php echo ! empty($userEmail) ? 'autofocus' : ''; ?> />

            <input type="hidden" name="upstream_login_nonce"
                   value="<?php echo wp_create_nonce('upstream-login-nonce'); ?>"/>

            <input type="submit" class="btn btn-lg btn-primary btn-block" value="<?php _e('Sign In', 'upstream'); ?>"
                   name="login"/>
        </form>

        <?php do_action('upstream_login_after_form'); ?>

        <div class="text-center">
            <?php echo upstream_login_text(); ?>
        </div>
    </div>

    <?php if ($login->hasFeedbackMessage()): ?>
        <div class="alert alert-danger">
            <?php echo $login->getFeedbackMessage(); ?>
        </div>
    <?php endif; ?>
</div>
