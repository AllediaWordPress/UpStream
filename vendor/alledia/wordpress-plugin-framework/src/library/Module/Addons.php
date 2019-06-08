<?php

namespace Allex\Module;

use Allex\Container;

class Addons extends Abstract_Module
{
    /**
     * Constant for valid status
     */
    const LICENSE_STATUS_VALID = 'valid';

    /**
     * Constant for invalid status
     */
    const LICENSE_STATUS_INVALID = 'invalid';

    /**
     * @var string
     */
    protected $plugin_basename;

    /**
     * @var string
     */
    protected $plugin_name;

    /**
     * @var string
     */
    protected $plugin_dir_path;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var array
     */
    protected $addons;

    /**
     * @var string
     */
    protected $edd_api_url;

    /**
     * @var string
     */
    protected $plugin_author;

    /**
     * @var string
     */
    protected $setLicenseKeyLink;

    /**
     * @var string
     */
    protected $updates_doc_url;

    /**
     * Upgrade constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->plugin_basename = $this->container['PLUGIN_BASENAME'];
        $this->plugin_name     = $this->container['PLUGIN_NAME'];
        $this->plugin_dir_path = $this->get_plugins_dir_path();
        $this->twig            = $this->container['twig'];
        $this->edd_api_url     = $this->container['EDD_API_URL'];
        $this->updates_doc_url = $this->container['UPDATES_DOC_URL'];
        $this->plugin_author   = $this->container['PLUGIN_NAME'];
    }

    /**
     * @return bool|string
     */
    protected function get_plugins_dir_path()
    {
        return dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))));
    }

    /**
     * Add an Upgrade link to the action links in the plugin list.
     */
    public function init()
    {
        $this->add_hooks();
    }

    /**
     * Add the hooks.
     */
    protected function add_hooks()
    {
        add_action('allex_echo_addons_page', [$this, 'echo_addons_page'], 10, 2);
        add_action('wp_ajax_allex_addon_license_validate', [$this, 'ajax_validate_license_key']);
        add_action('allex_set_license_key_links', [$this, 'setLicenseKeyLinks'], 10, 2);

        add_filter('allex_addons', [$this, 'filter_allex_addons_append_data'], 999998, 2);
        add_filter('allex_installed_addons', [$this, 'filter_allex_installed_addons'], 999999, 2);
        add_filter('plugin_action_links_' . $this->plugin_name, [$this, 'add_action_links']);
    }

    /**
     * @param string $addons_page_url
     * @param string $plugin_name
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function echo_addons_page($addons_page_url, $plugin_name = null)
    {

        /*
         * ---------------------------------------------------------------------
         * Backward compatibility for users using PublishPress and UpStream with
         * an older version of this library where the $plugin_name param is not
         * sent. We force its value, so the add-ons page keeps working until
         * they update the plugin.
         *
         * @todo: Remove this after a few releases
         *
         * @since 1.16.3
         */
        if (is_null($plugin_name)) {
            $plugin_name = $this->plugin_name;
        }
        /*
         * ---------------------------------------------------------------------
         */

        // If not related to this plugin, we skip it.
        if ($plugin_name !== $this->plugin_name) {
            return;
        }

        /**
         *     $addons = [
         *          [
         *              'slug'        => '',
         *              'title'       => '',
         *              'description' => '',
         *              'icon_class'  => '',
         *          ],
         *     ],
         */
        $addons = apply_filters('allex_addons', [], $this->plugin_name, false);

        // Cache the add-ons list.
        $this->addons = $addons;

        // Count the installed add-ons.
        $count = 0;
        foreach ($addons as $addon) {
            if ($addon['is_installed']) {
                $count++;
            }
        }

        /**
         * Filter to return a boolean value to say if it should display or not a sidebar.
         *
         * @var bool
         */
        $show_sidebar = apply_filters('allex_upgrade_show_sidebar_ad', false, $this->plugin_name, 'addons');

        $sidebar_output = '';
        if ($show_sidebar) {
            ob_start();
            do_action('allex_upgrade_sidebar_ad', $this->plugin_name);
            $sidebar_output = ob_get_clean();
        }

        $context = [
            'browse_more_url'    => $addons_page_url,
            'addons'             => $addons,
            'count_addons'       => $count,
            'count_addons_total' => count($addons),
            'plugin_name'        => $this->plugin_name,
            'show_sidebar'       => $show_sidebar,
            'sidebar_output'     => $sidebar_output,
            'nonce'              => wp_create_nonce('allex_activate_license'),
            'labels'             => [
                'installed'         => __('Installed Extensions', 'allex'),
                'browse_more'       => __('Browse More Extensions', 'allex'),
                'enter_license_key' => __('Enter your license key', 'allex'),
                'activate'          => __('Activate', 'allex'),
                'license_key'       => __('License Key', 'allex'),
                'change'            => __('Change', 'allex'),
                'get_plugins'       => __('Get Pro Add-ons!', 'allex'),
            ],
        ];

        echo $this->twig->render('addons_list.twig', $context);
    }

    /**
     * Append add-ons' state and license info to the add-ons array.
     *
     * @param $addons
     * @param $plugin_name
     *
     * @return mixed
     */
    public function filter_allex_addons_append_data($addons, $plugin_name)
    {

        // If not related to this plugin, we skip it.
        if ($plugin_name !== $this->plugin_name) {
            return $addons;
        }

        $this->set_addons_state($addons);
        $this->check_addons_license($addons);

        return $addons;
    }

    /**
     * Set the a property "is_installed" and "is_active" according to the current state of the add-on.
     *
     * @param array $addons
     */
    protected function set_addons_state(&$addons)
    {
        if ( ! empty($addons)) {
            foreach ($addons as &$addon) {
                $is_installed = $this->is_plugin_installed($addon['slug']);
                $is_active    = false;

                if ($is_installed) {
                    $is_active = $this->is_plugin_active($addon['slug']);
                }

                $addon['is_installed'] = $is_installed;
                $addon['is_active']    = $is_active;
            }
        }
    }

    /**
     * @param $plugin_name
     *
     * @return bool
     */
    public function is_plugin_installed($plugin_name)
    {
        return file_exists($this->plugin_dir_path . "/{$plugin_name}/{$plugin_name}.php");
    }

    /**
     * @param $plugin_name
     *
     * @return bool
     */
    public function is_plugin_active($plugin_name)
    {
        return is_plugin_active("{$plugin_name}/{$plugin_name}.php");
    }

    /**
     * @param array $addons
     */
    protected function check_addons_license(&$addons)
    {
        if (empty($addons)) {
            return;
        }

        foreach ($addons as &$addon) {
            $addon_name              = str_replace('-', '_', $addon['slug']);
            $addon['license_status'] = get_option("{$addon_name}_license_status", self::LICENSE_STATUS_INVALID);
            $addon['license_key']    = get_option("{$addon_name}_license_key");

            // Applies filters to the license key and status.
            $addon['license_status'] = apply_filters('allex_addons_get_license_status', $addon['license_status'],
                $addon['slug']);
            $addon['license_key']    = apply_filters('allex_addons_get_license_key', $addon['license_key'],
                $addon['slug']);
        }
    }

    /**
     * Filter the list of add-ons by state.
     *
     * @param $addons
     * @param $plugin_name
     *
     * @return mixed
     */
    public function filter_allex_installed_addons($addons, $plugin_name)
    {

        // If not related to this plugin, we skip it returning an empty array.
        if ($plugin_name !== $this->plugin_name) {
            return $addons;
        }

        $addons = apply_filters('allex_addons', [], $plugin_name);

        foreach ($addons as $addon_slug => $addon) {
            if ( ! $addon['is_installed']) {
                unset($addons[$addon_slug]);
            }
        }

        return $addons;
    }

    /**
     * AJAX endpoint that validates a given license key for an extension.
     * Echoes JSON data.
     */
    public function ajax_validate_license_key()
    {
        // If we are not in the valid plugin, we skip it. Maybe the method was called by another instance.
        if ( ! isset($_POST['plugin_name']) || $_POST['plugin_name'] !== $this->plugin_name) {
            return;
        }

        $response = [
            'success'        => false,
            'message'        => '',
            'license_status' => self::LICENSE_STATUS_INVALID,
            'license_key'    => '',
        ];

        try {
            if ( ! current_user_can('activate_plugins')) {
                throw new \Exception(__("You're not allowed to do this.", 'allex'));
            }

            if (empty($_POST) || ! isset($_POST['key']) || ! isset($_POST['plugin_name']) || ! isset($_POST['nonce'])) {
                throw new \Exception(__('Invalid request.', 'allex'));
            }

            if ( ! check_ajax_referer('allex_activate_license', 'nonce', false)) {
                throw new \Exception(__("You're not allowed to do this.", 'allex'));
            }

            $plugin_name = sanitize_text_field(trim($_POST['plugin_name']));
            if (empty($plugin_name)) {
                throw new \Exception(__('Invalid plugin name.', 'allex'));
            }

            $addon_slug = sanitize_text_field(trim($_POST['addon_name']));
            if (empty($addon_slug)) {
                throw new \Exception(__('Invalid addon name.', 'allex'));
            }

            $addon_edd_id = sanitize_text_field(trim($_POST['addon_edd_id']));
            if (empty($addon_edd_id)) {
                throw new \Exception(__('Invalid addon EDD ID.', 'allex'));
            }

            $license_key = sanitize_text_field(trim($_POST['key']));
            if (empty($license_key)) {
                throw new \Exception(__('Invalid license key.', 'allex'));
            }

            /**
             *     $addons = [
             *          [
             *              'slug'        => '',
             *              'title'       => '',
             *              'description' => '',
             *              'icon_class'  => '',
             *          ],
             *     ],
             */
            $addons = apply_filters('allex_addons', [], $this->plugin_name);

            // Check if it is a valid add-on.
            if (empty($addons)) {
                throw new \Exception(__("Invalid add-on.", 'allex'));
            }

            if ( ! array_key_exists($addon_slug, $addons)) {
                throw new \Exception(__('Invalid addon name.', 'allex'));
            }

            // The default status.
            $license_new_status = self::LICENSE_STATUS_INVALID;

            // Make the request.
            $edd_response = wp_remote_post(
                $this->edd_api_url,
                [
                    'timeout'   => 30,
                    'sslverify' => true,
                    'body'      => [
                        'edd_action' => "activate_license",
                        'license'    => $license_key,
                        'item_id'    => $addon_edd_id,
                        'url'        => site_url(),
                    ],
                ]
            );

            $response['license_key'] = $license_key;

            // Is the response an error?
            if (is_wp_error($edd_response) || 200 !== wp_remote_retrieve_response_code($edd_response)) {
                $message = $edd_response->get_error_message();

                if (empty($message)) {
                    throw new \Exception(__('An error occurred. Please, try again or contact the support team.',
                        'allex'));
                }

                throw new \Exception($message, 'allex');
            }

            // Convert data response to an object.
            $data = json_decode(wp_remote_retrieve_body($edd_response));

            // Do we have empty data? Throw an error.
            if (empty($data) || ! is_object($data)) {
                throw new \Exception(__('An error occurred. Please, try again or contact the support team.',
                    'allex'));
            }

            $response['success'] = true;

            $addon_name = str_replace('-', '_', $addon_slug);

            // Deal with invalid licenses.
            if ( ! $data->success && $data->license === self::LICENSE_STATUS_INVALID) {
                if (isset($data->error) && ! empty($data->error)) {
                    $response['license_status'] = $data->error;

                    update_option("{$addon_name}_license_key", $license_key, true);
                    update_option("{$addon_name}_license_status", $data->error, true);

                    do_action('allex_addon_update_license', $plugin_name, $addon_slug, $license_key, $data->error);
                }
            }

            // Deal with valid licenses.
            if ($data->success && $data->license === self::LICENSE_STATUS_VALID) {
                $response['license_status'] = static::LICENSE_STATUS_VALID;

                // Store the license key and status.
                update_option("{$addon_name}_license_key", $license_key, true);
                update_option("{$addon_name}_license_status", static::LICENSE_STATUS_VALID, true);

                do_action('allex_addon_update_license', $plugin_name, $addon_slug, $license_key,
                    static::LICENSE_STATUS_VALID);
            }
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
        }

        header('Content-Type: application/json');
        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * @param $plugin
     * @param $link
     *
     * @return array
     */
    public function setLicenseKeyLinks($plugin, $link)
    {
        $this->setLicenseKeyLink = $link;

        // Set license key link
        $pool = apply_filters('allex_addons', [], $plugin);

        foreach ($pool as $addon) {
            if (file_exists(WP_PLUGIN_DIR . '/' . $addon['slug'] . '/' . $addon['slug'] . '.php')) {
                add_action('plugin_action_links_' . $addon['slug'] . '/' . $addon['slug'] . '.php',
                    [$this, 'actionSetLicenseKeyLink'], 998, 2);

                add_action('in_plugin_update_message-' . $addon['slug'] . '/' . $addon['slug'] . '.php',
                    [$this, 'inPluginUpdateMessage'], 998, 2);
            }
        }
    }

    /**
     * @param $pluginData
     * @param $response
     */
    public function inPluginUpdateMessage($pluginData, $response)
    {
        // Get the plugin's add-ons
        $addons = apply_filters('allex_addons', [], $this->plugin_name);
        $addons = array_keys($addons);

        // If is not a plugin add-on, we stop.
        if ( ! in_array($pluginData['slug'], $addons)) {
            return;
        }

        $addon         = str_replace('-', '_', str_replace('.php', '', basename($pluginData['slug'])));
        $licenseKey    = get_option($addon . '_license_key');
        $licenseStatus = get_option($addon . '_license_status');

        if ((empty($licenseKey) || $licenseStatus !== 'valid') && empty($response->package)) {
            // Update available, but there is no package probably because there is no valid license key.
            $context = [
                'text' => [
                    'please_enter_license_key' => __('Please enter a valid license key to enable automatic updates.',
                        'allex'),
                    'enter_license_key'        => __('Enter License Key', 'allex'),
                ],
                'link' => $this->setLicenseKeyLink,
            ];

            echo $this->twig->render('after_plugin_row.twig', $context);
        } elseif (empty($response->package)) {
            // Update available, valid license key, but no package found.
            $context = [
                'link_docs' => $this->updates_doc_url,
                'text'      => [
                    'message'   => __('Sorry, we couldn’t access the files for this update.', 'allex'),
                    'link_docs' => __('Please click this link for more details.', 'allex'),
                ],
            ];

            echo $this->twig->render('after_plugin_row_error.twig', $context);
        }
    }

    /**
     * Show a link to set the license key if there is no license key or is invalid.
     *
     * @param $links
     * @param $pluginFile
     *
     * @return array
     */
    public function actionSetLicenseKeyLink($links, $pluginFile)
    {
        $addon         = str_replace('-', '_', str_replace('.php', '', basename($pluginFile)));
        $licenseKey    = get_option($addon . '_license_key');
        $licenseStatus = get_option($addon . '_license_status');

        if (empty($licenseKey) || $licenseStatus !== 'valid') {
            $context = [
                'url'   => $this->setLicenseKeyLink,
                'label' => __('Enter License Key', 'allex'),
            ];

            $link = $this->twig->render('action_link_set_key.twig', $context);

            // Avoid to add duplicated link, in case it already exist
            if (array_search($link, $links) === false) {
                $links[] = $link;
            }
        }

        return $links;
    }
}
