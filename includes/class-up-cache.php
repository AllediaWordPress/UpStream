<?php
// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class Upstream_Cache
{
    /**
     * Instance of the Pimple container
     */
    protected static $instance;

    protected $cache = [];

    public function set($key, $value)
    {
        $this->cache[$key] = $value;
    }

    public function get($key)
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        return null;
    }

    public function reset()
    {
        $this->cache = [];
    }

    public static function get_instance()
    {
        if (empty(static::$instance)) {
            $instance = new self;
            static::$instance = $instance;
        }

        return static::$instance;
    }
}