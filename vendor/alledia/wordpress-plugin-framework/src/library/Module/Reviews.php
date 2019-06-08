<?php

namespace Allex\Module;

use Allex\Container;

class Reviews extends Abstract_Module
{
    const REPEAT_INTERVAL = '+30 days';

    /**
     * @var mixed
     */
    protected $twig;

    /**
     * @var string
     */
    protected $plugin_name;

    /**
     * @var string
     */
    protected $option_name_status;

    /**
     * @var string
     */
    protected $option_name_timestamp;

    /**
     * @var string
     */
    protected $notice_text;

    /**
     * @var string
     */
    protected $review_link;

    /**
     * Reviews constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->twig                  = $this->container['twig'];
        $this->plugin_name           = $this->container['PLUGIN_NAME'];
        $this->option_name_status    = $this->plugin_name . '_review_status';
        $this->option_name_timestamp = $this->plugin_name . '_review_timestamp';
    }

    /**
     * $params = [
     *     'redirect_url' => '',
     *     'review_link'  => '',
     *     'notice_text'  => '',
     *  ];
     *
     * @param array $params
     */
    public function init($params)
    {
        $this->notice_text = esc_html($params['notice_text']);
        $this->review_link = $params['review_link'];

        $this->handle_request_actions($params['redirect_url']);

        if ($this->should_display_notice()) {
            $this->add_action_admin_notice();
            $this->reset_options();
        }
    }

    /**
     * Handles actions triggered by the links in the notification.
     *
     * @param string $redirect_url
     */
    protected function handle_request_actions($redirect_url)
    {
        // We only check GET requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        // Check if the request is related to the current plugin.
        if ( ! isset($_GET['review_plugin'])
             || $_GET['review_plugin'] != $this->plugin_name) {
            return;
        }

        // Check if the URL is related to any action
        $valid_actions = ['no', 'done'];
        if ( ! isset($_GET['review_action'])
             || ! in_array($_GET['review_action'], $valid_actions)) {
            return;
        }

        $this->store_state_status($_GET['review_action']);

        // Store the date for asking for the review again later.
        if ($_GET['review_action'] === 'no') {
            $this->store_state_timestamp(date('Y-m-d'));
        }

        $this->redirect($redirect_url);

        exit;
    }

    /**
     * @param string $state
     */
    protected function store_state_status($status)
    {
        update_option($this->option_name_status, $status, true);
    }

    /**
     * @param string $timestamp
     */
    protected function store_state_timestamp($timestamp)
    {
        update_option($this->option_name_timestamp, $timestamp, true);
    }

    /**
     * @param $url
     */
    protected function redirect($url)
    {
        wp_redirect(admin_url($url));
    }

    /**
     * @return bool
     */
    protected function should_display_notice()
    {

        if ( ! is_admin()) {
            return false;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return false;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return false;
        }

        // Check if the user answered "done".
        $status = $this->get_state_status();

        if ($status === 'done') {
            return false;
        }

        // Check when the user answered no. We will ask again after a few days, if he still uses the plugin.
        if ($status === 'no') {
            $date  = $this->get_state_timestamp(date('Y-m-d'));
            $date  = strtotime($date . ' ' . self::REPEAT_INTERVAL);
            $today = strtotime(date('Y-m-d'));

            return $date <= $today;
        }

        return true;
    }

    /**
     * @param string $default
     *
     * @return mixed|void
     */
    protected function get_state_status($default = '')
    {
        return get_option($this->option_name_status, $default);
    }

    /**
     * @param string $default
     *
     * @return mixed|void
     */
    protected function get_state_timestamp($default = '')
    {
        return get_option($this->option_name_timestamp, $default);
    }

    /**
     *
     */
    protected function add_action_admin_notice()
    {
        add_action('admin_notices', [$this, 'render_notice']);
    }

    /**
     * Reset the options.
     */
    protected function reset_options()
    {
        delete_option($this->option_name_status);
        delete_option($this->option_name_timestamp);
    }

    /**
     *
     * @param string $message
     *
     * @return mixed
     */
    public function render_notice()
    {
        $context = [
            'message' => esc_html($this->notice_text),
            'links'   => [
                'yes'  => $this->review_link,
                'done' => admin_url("?review_plugin={$this->plugin_name}&review_action=done"),
                'no'   => admin_url("?review_plugin={$this->plugin_name}&review_action=no"),
            ],
            'labels'  => [
                'ok'   => __('Ok, you deserve it', 'allex'),
                'done' => __('I already did', 'allex'),
                'no'   => __('No, not good enough for now', 'allex'),
            ],
        ];

        echo $this->twig->render('notice_five_star_review.twig', $context);
    }
}
