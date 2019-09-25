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

        return false;
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

function upstream_cache_get_metadata()
{
    $str = "upstream_cache_get_metadata";
    $args = func_get_args();

    for ($i = 0; $i < func_num_args(); $i++) {
        $str .= $args[$i];
    }

    $cache = Upstream_Cache::get_instance();
    $res = $cache->get($str);

    if ($res !== false) {
        return $res;
    }

    $cache->set($str, $res);

    return call_user_func_array('get_metadata', $args);
}

function upstream_cache_get_post_meta()
{
    $str = "upstream_cache_get_post_meta";
    $args = func_get_args();

    for ($i = 0; $i < func_num_args(); $i++) {
        $str .= $args[$i];
    }

    $cache = Upstream_Cache::get_instance();
    $res = $cache->get($str);

    if ($res !== false) {
        return $res;
    }

    $cache->set($str, $res);

    return call_user_func_array('get_post_meta', $args);
}