<?php
/**
 * The following snippets uses `PLUGIN` to prefix
 * the constants and class names. You should replace
 * it with something that matches your plugin name.
 */
// define test environment
define( 'PLUGIN_PHPUNIT', true );

// define fake ABSPATH
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', sys_get_temp_dir() );
}
// define fake PLUGIN_ABSPATH
if ( ! defined( 'PLUGIN_ABSPATH' ) ) {
    define( 'PLUGIN_ABSPATH', sys_get_temp_dir() . '/wp-content/plugins/upstream/' );
}

require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../includes/abs-class-up-struct.php';
require_once __DIR__ . '/../../includes/class-up-exception.php';
require_once __DIR__ . '/../../includes/class-up-milestone.php';

require_once __DIR__ . '/../_support/UnitCest.php';
