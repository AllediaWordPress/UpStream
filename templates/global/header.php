<?php
if ( ! defined('ABSPATH')) {
    exit;
}



$options = (array)get_option('upstream_general');
$enable  = ! empty($options['compatibility_mode']) && ! empty($options['compatibility_mode'][0]) ? (string)$options['compatibility_mode'][0] === '1' : false;

if ($enable) {
    add_action('wp_enqueue_scripts', 'upstream_perform_aggressive_dequeue', 99999999);
}

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title><?php wp_title('|', true, 'right') . bloginfo('name'); ?></title>

    <link rel="profile" href="http://gmpg.org/xfn/11">

    <?php wp_head(); ?>
    <?php do_action('upstream_head'); ?>
</head>
<body <?php body_class(['nav-md', 'upstream-front-end']); ?>">
<div class="container body">
    <div class="main_container">
    <?php do_action('upstream_frontend_header_before_content'); ?>
