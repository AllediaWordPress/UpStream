<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * UpStream Autoloader.
 *
 * @class       UpStream_Autoloader
 * @version     0.0.1
 * @package     UpStream/Classes
 * @category    Class
 * @author      UpStream
 */
class UpStream_Autoloader
{

    /**
     * Path to the includes directory.
     *
     * @var string
     */
    private $include_path = '';

    /**
     * The Constructor.
     */
    public function __construct()
    {
        if (function_exists("__autoload")) {
            spl_autoload_register("__autoload");
        }

        spl_autoload_register([$this, 'autoload']);

        $this->include_path = untrailingslashit(plugin_dir_path(UPSTREAM_PLUGIN_FILE)) . '/includes/';
    }

    /**
     * Auto-load classes on demand to reduce memory consumption.
     *
     * @param string $class
     */
    public function autoload($class)
    {
        $class = strtolower($class);
        $file  = $this->get_file_name_from_class($class);
        $path  = '';

        if (strpos($class, 'upstream_options') === 0) {
            $path = $this->include_path . 'admin/options/';
        } elseif (strpos($class, 'upstream_metaboxes') === 0) {
            $path = $this->include_path . 'admin/metaboxes/';
        } elseif (strpos($class, 'upstream_model') === 0) {
            $path = $this->include_path . 'model/';
            $file = $class . ".php";
        }

        if (empty($path) || ( ! $this->load_file($path . $file) && strpos($class, 'upstream_') === 0)) {
            $this->load_file($this->include_path . $file);
        }
    }

    /**
     * Take a class name and turn it into a file name.
     *
     * @param  string $class
     *
     * @return string
     */
    private function get_file_name_from_class($class)
    {
        $class = str_replace('upstream', 'up', $class);

        return 'class-' . str_replace('_', '-', $class) . '.php';
    }

    /**
     * Include a class file.
     *
     * @param  string $path
     *
     * @return bool successful or not
     */
    private function load_file($path)
    {
        if ($path && is_readable($path)) {
            include_once($path);

            return true;
        }

        return false;
    }
}

new UpStream_Autoloader();
UpStream_Model_Manager::get_instance();
Upstream_Cache::get_instance();