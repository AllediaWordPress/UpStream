<?php

namespace Allex;

use Allex\Module\Addons;
use Allex\Module\Assets;
use Allex\Module\Reviews;
use Allex\Module\Upgrade;

class Core
{

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Textdomain
     */
    protected $textdomain;

    /**
     * @var Reviews
     */
    protected $module_reviews;

    /**
     * @var Assets
     */
    protected $module_assets;

    /**
     * @var Addons
     */
    protected $module_addons;

    /**
     * @var Upgrade
     */
    protected $module_upgrade;

    /**
     * Core constructor.
     *
     * @param string $plugin_base_name
     * @param string $edd_api_url
     * @param string $plugin_author
     * @param string $updates_doc_url
     * @param string Deprecated $subscription_ad_url
     */
    public function __construct(
        $plugin_base_name,
        $edd_api_url,
        $plugin_author,
        $updates_doc_url = '',
        $subscription_ad_url = ''
    ) {
        $this->init_container($plugin_base_name, $edd_api_url, $plugin_author, $updates_doc_url);

        $this->textdomain     = $this->get_service('textdomain');
        $this->module_reviews = $this->get_service('module_reviews');
        $this->module_assets  = $this->get_service('module_assets');
        $this->module_addons  = $this->get_service('module_addons');
        $this->module_upgrade = $this->get_service('module_upgrade');
    }

    /**
     * @param string $plugin_base_name
     * @param string $edd_api_url
     * @param string $plugin_author
     * @param string $updates_doc_url
     */
    protected function init_container(
        $plugin_base_name,
        $edd_api_url,
        $plugin_author,
        $updates_doc_url = ''
    ) {
        $this->container = new Container([
            'PLUGIN_BASENAME' => $plugin_base_name,
            'EDD_API_URL'     => $edd_api_url,
            'UPDATES_DOC_URL' => $updates_doc_url,
            'PLUGIN_AUTHOR'   => $plugin_author,
        ]);

        return $this->container;
    }

    /**
     * @param $service_name
     *
     * @return mixed
     * @throws \Exception
     */
    public function get_service($service_name)
    {
        $container = $this->get_container();

        if ( ! isset($container[$service_name])) {
            throw new \Exception('Service ' . $service_name . ' is undefined.');
        }

        return $container[$service_name];
    }

    /**
     * @return mixed
     */
    public function get_container()
    {
        return $this->container;
    }

    /**
     * Initialize the framework.
     */
    public function init()
    {
        $this->init_textdomain();
        $this->init_assets();
        $this->add_hooks();

        do_action('allex_loaded');
    }

    /**
     * Load the text domain.
     */
    protected function init_textdomain()
    {
        $this->textdomain->load();
    }

    /**
     * Enqueue assets.
     */
    protected function init_assets()
    {
        $this->module_assets->init();
    }

    /**
     * Initialize the hooks.
     */
    protected function add_hooks()
    {
        // Reviews
        add_action('allex_enable_module_reviews', [$this->module_reviews, 'init']);
        // Add-ons
        add_action('allex_enable_module_addons', [$this->module_addons, 'init']);
        // Upgrade
        add_action('allex_enable_module_upgrade', [$this->module_upgrade, 'init']);
    }
}
