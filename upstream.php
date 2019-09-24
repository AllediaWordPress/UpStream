<?php
/**
 * Plugin Name: UpStream
 * Description: A WordPress Project Management plugin by UpStream.
 * Author: UpStream
 * Author URI: https://upstreamplugin.com
 * Version: 1.28.1
 * Text Domain: upstream
 * Domain Path: /languages
 */

use UpStream\Comments;

// Exit if accessed directly.
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! class_exists('UpStream')) :

    /**
     * Main UpStream Class.
     *
     * @since 1.0.0
     */
    final class UpStream
    {
        /**
         * @var UpStream The one true UpStream
         * @since 1.0.0
         */
        protected static $_instance = null;

        /**
         * @var Twig_Environment
         */
        protected $twig;

        /**
         * @var Container
         */
        protected $container;

        /**
         * Main UpStream Instance.
         */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        /**
         * Throw error on object clone.
         *
         * The whole idea of the singleton design pattern is that there is a single
         * object therefore, we don't want the object to be cloned.
         *
         * @since   1.0.0
         */
        public function __clone()
        {
            _doing_it_wrong(__FUNCTION__, 'You\'re not supposed to clone this class.', UPSTREAM_VERSION);
        }

        /**
         * Disable unserializing of the class.
         *
         * @since   1.0.0
         */
        public function __wakeup()
        {
            _doing_it_wrong(__FUNCTION__, 'You\'re not supposed to unserialize this class.', UPSTREAM_VERSION);
        }

        /**
         * Prevent the class instance being serialized.
         *
         * @since   1.10.2
         */
        public function __sleep()
        {
            _doing_it_wrong(__FUNCTION__, 'You\'re not supposed to serialize this class.', UPSTREAM_VERSION);
        }

        public function __construct()
        {
            $this->define_constants();
            $this->includes();

            // Start the pimple container.
            $this->container = Container::get_instance();

            // Initialize the Allex Framework.
            $this->init_framework();

            if (UpStream_Debug::is_enabled()) {
                UpStream_Debug::init();
            }

            $this->init_hooks();
            $this->init_twig();

            do_action('upstream_loaded');
        }

        /**
         * Hook into actions and filters.
         *
         * @since  1.0.0
         */
        private function init_hooks()
        {
            add_action('init', [$this, 'init']);
            add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
            add_filter('plugin_action_links_upstream/upstream.php', [$this, 'handleActionLinks']);
            add_filter('http_request_host_is_external', ['UpStream', 'allowExternalUpdateHost'], 10, 3);
            add_filter('quicktags_settings', 'upstream_tinymce_quicktags_settings');
            add_filter('tiny_mce_before_init', 'upstream_tinymce_before_init_setup_toolbar');
            add_filter('tiny_mce_before_init', 'upstream_tinymce_before_init');
            add_filter('teeny_mce_before_init', 'upstream_tinymce_before_init_setup_toolbar');
            add_filter('comments_clauses', [$this, 'filterCommentsOnDashboard'], 10, 2);
            add_filter('views_dashboard', ['UpStream_Admin', 'commentStatusLinks'], 10, 1);
            add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);

            if (is_admin()) {
                add_action('admin_init', [$this->container['reviews'], 'init']);
            }

            // Render additional update info if needed.
            global $pagenow;
            if ($pagenow === "plugins.php") {
                add_action(
                    'in_plugin_update_message-' . UPSTREAM_PLUGIN_BASENAME,
                    [$this, 'renderAdditionalUpdateInfo'],
                    20,
                    2
                );
            }
        }

        /**
         * Initialize the Alledia Framework.
         */
        private function init_framework()
        {
            $this->container['framework']->init();
        }

        /**
         * Initialize the twig environment.
         */
        private function init_twig()
        {
            $loader          = new Twig_Loader_Filesystem(__DIR__ . '/twig');
            $twigEnvironment = new Twig_Environment($loader);


            $doActionFunc = new Twig_SimpleFunction('doAction', function ($action, $context) {
                $args = func_get_args();

                call_user_func_array('do_action', $args);
                //                do_action($action, $context);
            });
            $twigEnvironment->addFunction($doActionFunc);

            $wpEditorFunc = new Twig_SimpleFunction('wpEditor', function ($content, $editorId, $settings = []) {
                wp_editor($content, $editorId, $settings);
            });
            $twigEnvironment->addFunction($wpEditorFunc);

            $this->twig = $twigEnvironment;
        }

        /**
         * @param       $twig_file
         * @param array $context
         *
         * @return string
         * @throws Twig_Error_Runtime
         * @throws Twig_Error_Syntax
         *
         * @throws Twig_Error_Loader
         * @deprecated
         */
        public function twig_render($twig_file, $context = [])
        {
            return $this->twigRender($twig_file, $context);
        }

        /**
         * @param       $twigFile
         * @param array $context
         *
         * @return string
         * @throws Twig_Error_Loader
         * @throws Twig_Error_Runtime
         * @throws Twig_Error_Syntax
         */
        public function twigRender($twigFile, $context = [])
        {
            return $this->twig->render($twigFile, $context);
        }

        /**
         * Prevent a Client User from accessing any page other than the profile.
         *
         * @since   1.11.0
         *
         * @global  $pagenow
         */
        public function limitClientUsersAdminAccess()
        {
            global $pagenow;

            $profilePage = 'profile.php';
            if ($pagenow !== $profilePage && $pagenow !== "edit.php" && ! wp_doing_ajax()) {
                wp_redirect(admin_url($profilePage));
                exit;
            }
        }

        /**
         * Make sure Client Users can only see the Profile menu item.
         *
         * @since   1.11.0
         *
         * @global  $menu
         */
        public function limitClientUsersMenu()
        {
            global $menu;

            foreach ($menu as $menuIndex => $menuData) {
                $menuFile = isset($menuData[2]) ? $menuData[2] : null;
                if ($menuFile !== null) {
                    if ($menuFile === 'profile.php' || $menuFile === 'edit.php?post_type=project') {
                        continue;
                    }

                    remove_menu_page($menuFile);
                }
            }
        }

        /**
         * Hide some toolbar items from Client Users.
         *
         * @param \WP_Admin_Bar $wp_admin_bar
         *
         * @since   1.11.0
         *
         */
        public function limitClientUsersToolbarItems($wp_admin_bar)
        {
            $user      = wp_get_current_user();
            $userRoles = (array)$user->roles;

            if (count(array_intersect(
                    $userRoles,
                    ['administrator', 'upstream_manager']
                )) === 0 && in_array(
                    'upstream_client_user',
                    $userRoles
                )) {
                $menuItems = ['about', 'comments', 'new-content'];

                if ( ! is_admin()) {
                    $menuItems = array_merge($menuItems, ['dashboard', 'edit']);
                }

                foreach ($menuItems as $menuItem) {
                    $wp_admin_bar->remove_menu($menuItem);
                }
            }
        }

        /**
         * @return Container
         */
        public function get_container()
        {
            return $this->container;
        }

        /**
         * Define Constants.
         *
         * @since  1.0.0
         */
        private function define_constants()
        {
            $upload_dir = wp_upload_dir();
            $this->define('UPSTREAM_PLUGIN_FILE', __FILE__);
            $this->define('UPSTREAM_PLUGIN_DIR', plugin_dir_path(__FILE__));
            $this->define('UPSTREAM_PLUGIN_URL', plugin_dir_url(__FILE__));
            $this->define('UPSTREAM_PLUGIN_BASENAME', plugin_basename(__FILE__));
            $this->define('UPSTREAM_PLUGIN_RELATIVE_PATH', 'upstream');

            include_once __DIR__ . '/includes.php';
        }

        /**
         * Define constant if not already set.
         *
         * @param string      $name
         * @param string|bool $value
         *
         * @since  1.0.0
         *
         */
        private function define($name, $value)
        {
            if ( ! defined($name)) {
                define($name, $value);
            }
        }

        /**
         * What type of request is this?
         * string $type frontend or admin.
         *
         * @return bool
         * @since  1.0.0
         */
        private function is_request($type)
        {
            switch ($type) {
                case 'admin':
                    return is_admin();
                case 'frontend':
                    return ( ! is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
            }
        }

        /**
         * Include required core files used in admin and on the frontend.
         *
         * @since  1.0.0
         */
        public function includes()
        {
            // When composer is used in a global scope the folder won't exist here. So we need to check before load it.
            if (file_exists(__DIR__ . '/vendor/autoload.php')) {
                require_once __DIR__ . '/vendor/autoload.php';
            }

            include_once __DIR__ . '/includes/class-up-exception.php';
            include_once __DIR__ . '/includes/trait-up-singleton.php';
            include_once __DIR__ . '/includes/trait-up-post-metadata.php';
            include_once __DIR__ . '/includes/abs-class-up-struct.php';
            include_once __DIR__ . '/includes/class-up-debug.php';
            include_once __DIR__ . '/includes/class-up-container.php';
            include_once __DIR__ . '/includes/up-install.php';
            include_once __DIR__ . '/includes/class-up-autoloader.php';
            include_once __DIR__ . '/includes/class-up-roles.php';
            include_once __DIR__ . '/includes/class-up-counts.php';
            include_once __DIR__ . '/includes/class-up-counter.php';
            include_once __DIR__ . '/includes/class-up-project-activity.php';
            include_once __DIR__ . '/includes/up-permalinks.php';
            include_once __DIR__ . '/includes/up-general-functions.php';
            include_once __DIR__ . '/includes/up-post-types.php';
            include_once __DIR__ . '/includes/up-labels.php';
            include_once __DIR__ . '/includes/class-up-milestones.php';
            include_once __DIR__ . '/includes/class-up-milestone.php';
            include_once __DIR__ . '/includes/class-up-factory.php';
            include_once __DIR__ . '/includes/up-install.php';

            if ($this->is_request('admin')) {
                global $pagenow;

                $isMultisite = (bool)is_multisite();
                $loadCmb2    = false;

                if ($isMultisite) {
                    $currentPage = isset($_SERVER['PHP_SELF']) ? preg_replace(
                        '/^\/wp-admin\//i',
                        '',
                        $_SERVER['PHP_SELF']
                    ) : '';
                } else {
                    $currentPage = (string)$pagenow;
                }

                if (in_array($currentPage, ['post.php', 'post-new.php'])) {
                    $postType = isset($_REQUEST['post_type']) ? $_REQUEST['post_type'] : null;
                    if (empty($postType)) {
                        $projectId = isset($_REQUEST['post']) ? (int)$_REQUEST['post'] : 0;
                        $postType  = get_post_type($projectId);
                    }

                    if ( ! empty($postType)) {
                        $postTypesUsingCmb2 = apply_filters(
                            'upstream:post_types_using_cmb2',
                            ['project', 'client']
                        );
                        $loadCmb2           = in_array($postType, $postTypesUsingCmb2);
                    }
                } elseif ($currentPage === 'admin.php'
                          && isset($_REQUEST['page'])
                          && preg_match('/^upstream_/i', $_REQUEST['page'])
                ) {
                    $loadCmb2 = true;
                }

                if ($loadCmb2) {
                    include_once __DIR__ . '/includes/libraries/cmb2/init.php';
                    include_once __DIR__ . '/includes/libraries/cmb2-grid/Cmb2GridPlugin.php';
                }

                include_once __DIR__ . '/includes/admin/class-up-admin.php';
                include_once __DIR__ . '/includes/admin/class-up-admin-tasks-page.php';
                include_once __DIR__ . '/includes/admin/class-up-admin-bugs-page.php';
                include_once __DIR__ . '/includes/admin/class-up-admin-reviews.php';
            }

            if ($this->is_request('frontend')) {
                include_once __DIR__ . '/includes/frontend/class-up-template-loader.php';
                include_once __DIR__ . '/includes/frontend/class-up-login.php';
                include_once __DIR__ . '/includes/frontend/class-up-style-output.php';
                include_once __DIR__ . '/includes/frontend/up-enqueues.php';
                include_once __DIR__ . '/includes/frontend/up-template-functions.php';
                include_once __DIR__ . '/includes/frontend/up-table-functions.php';
                include_once __DIR__ . '/includes/frontend/class-up-view.php';
                include_once __DIR__ . '/includes/frontend/class-ajax.php';
            }

            include_once __DIR__ . '/includes/up-project-functions.php';
            include_once __DIR__ . '/includes/up-client-functions.php';
            include_once __DIR__ . '/includes/up-permissions-functions.php';
            include_once __DIR__ . '/includes/up-comments-migration.php';
            include_once __DIR__ . '/includes/class-up-comments.php';
            include_once __DIR__ . '/includes/class-up-comment.php';
        }

        /**
         * Init UpStream when WordPress Initialises.
         */
        public function init()
        {
            // Load the classes
            UpStream\Milestones::instantiate();

            // Before init action.
            do_action('before_upstream_init');
            // Set up localisation.

            // Load class instances.
            $this->project          = new UpStream_Project();
            $this->project_activity = new UpStream_Project_Activity();

            // If PHP < 5.5, loads a library intended to provide forward compatibility with the password_* functions that ship with PHP 5.5.
            if (version_compare(PHP_VERSION, '5.5', '<')) {
                require_once UPSTREAM_PLUGIN_DIR . 'includes/libraries/password_compat-1.0.4/lib/password.php';
            }

            // Executes the Legacy Client Users Migration script if needed.
            \UpStream\Migrations\Comments::run();

            $user      = wp_get_current_user();
            $userRoles = (array)$user->roles;
            if (count(array_intersect(
                    $userRoles,
                    ['administrator', 'upstream_manager']
                )) === 0 && in_array(
                    'upstream_client_user',
                    $userRoles
                )) {
                add_filter('admin_init', [$this, 'limitClientUsersAdminAccess']);
                add_filter('admin_head', [$this, 'limitClientUsersMenu']);
                add_action('admin_bar_menu', [$this, 'limitClientUsersToolbarItems'], 999);
            }

            // Starting from v1.12.5 UpStream Users role won't have 'edit_others_projects' capability by default.
            $editOtherProjectsPermissionWereRemoved = (bool)get_option('upstream:role_upstream_users:drop_edit_others_projects');
            if ( ! $editOtherProjectsPermissionWereRemoved) {
                $role = get_role('upstream_user');
                $role->remove_cap('edit_others_projects');
                unset($role);

                update_option('upstream:role_upstream_users:drop_edit_others_projects', 1);
            }

            UpStream_Options_Projects::createProjectsStatusesIds();
            UpStream_Options_Tasks::createTasksStatusesIds();
            UpStream_Options_Bugs::createBugsStatusesIds();

            Comments::instantiate();

            if ($this->is_request('frontend')) {
                UpStream_Ajax::instantiate();
            }

            // Init action.
            do_action('upstream_init');
        }

        /**
         * Load Localisation files.
         */
        public function load_plugin_textdomain()
        {
            load_plugin_textdomain('upstream', false, UPSTREAM_PLUGIN_RELATIVE_PATH . '/languages/');
        }


        /**
         * Show row meta on the plugin screen.
         *
         * @param mixed $links Plugin Row Meta
         * @param mixed $file  Plugin Base file
         *
         * @return  array
         */
        public function plugin_row_meta($links, $file)
        {
            if ($file == UPSTREAM_PLUGIN_BASENAME) {
                $row_meta = [
                    'docs'        => '<a href="' . esc_url('http://upstreamplugin.com/documentation') . '" title="' . esc_attr(__(
                            'View Documentation',
                            'upstream'
                        )) . '">' . __('Docs', 'upstream') . '</a>',
                    'quick-start' => '<a href="' . esc_url('http://upstreamplugin.com/quick-start-guide') . '" title="' . esc_attr(__(
                            'View Quick Start Guide',
                            'upstream'
                        )) . '">' . __('Quick Start Guide', 'upstream') . '</a>',
                ];

                return array_merge($links, $row_meta);
            }

            return (array)$links;
        }

        /**
         * Callback called to setup the links to display on the plugins page, besides active/deactivate links.
         *
         * @param array $links The list of links to be displayed.
         *
         * @return  array
         * @since   1.11.1
         * @static
         *
         */
        public static function handleActionLinks($links)
        {
            $links['settings'] = sprintf(
                '<a href="%s" title="%2$s" aria-label="%2$s">%3$s</a>',
                admin_url('admin.php?page=upstream_general'),
                __('Open Settings Page', 'upstream'),
                __('Settings', 'upstream')
            );

            return $links;
        }

        /**
         * Ensures the plugins update API's host is whitelisted to WordPress external requests.
         *
         * @param boolean $isAllowed
         * @param string  $host
         * @param string  $url
         *
         * @return  boolean
         * @since   1.11.1
         * @static




         *
         */
        public static function allowExternalUpdateHost($isAllowed, $host, $url)
        {
            if ($host === 'upstreamplugin.com') {
                return true;
            }

            return $isAllowed;
        }

        /**
         * Render additional update info if needed.
         *
         * @param array  $pluginData Plugin metadata.
         * @param object $response   Metadata about the available plugin update.
         *
         * @since   1.12.5
         * @static
         *
         * @see     https://developer.wordpress.org/reference/hooks/in_plugin_update_message-file
         *
         */
        public static function renderAdditionalUpdateInfo($pluginData, $response)
        {
            $updateNoticeTitleHtml = sprintf(
                '<strong style="font-size: 1.25em; display: block; margin-top: 10px;">%s</strong>',
                __('Update notice:', 'upstream')
            );

            if (version_compare(UPSTREAM_VERSION, "1.12.5", "<")) {
                printf(
                    $updateNoticeTitleHtml .
                    _x(
                        'Starting from <strong>%s</strong> <code>%s</code> capability was removed from <code>%s</code> users role.',
                        '1st %s: plugin version, 2nd %s: capability name, 3rd: UpStream User role',
                        'upstream'
                    ),
                    'v1.12.5',
                    'edit_others_projects',
                    __('UpStream User', 'upstream')
                );
            }
        }

        /**
         * Make sure Recent Comments section on admin Dashboard display only comments
         * current user is allowed to see from projects he's allowed to access.
         *
         * @param array              $queryArgs Query clauses.
         * @param WP_Comment_Query   $query     Current query instance.
         *
         * @return  array   $queryArgs
         * @global                   $pagenow   , $wpdb
         *
         * @since   1.13.0
         * @static
         *
         */
        public static function filterCommentsOnDashboard($queryArgs, $query)
        {
            global $pagenow;

            if (is_admin()
                && $pagenow === "index.php"
                && ! isUserEitherManagerOrAdmin()
            ) {
                global $wpdb;

                $queryArgs['join'] = 'LEFT JOIN ' . $wpdb->prefix . 'posts AS post ON post.ID = ' . $wpdb->prefix . 'comments.comment_post_ID';

                $user = wp_get_current_user();
                if (in_array('upstream_user', $user->roles) || in_array('upstream_client_user', $user->roles)) {
                    $projects = (array)upstream_get_users_projects($user);
                    if (count($projects) === 0) {
                        $queryArgs['where'] = "(post.ID = -1)";
                    } else {
                        $queryArgs['where'] = "(post.post_type = 'project' AND post.ID IN (" . implode(
                                ', ',
                                array_keys($projects)
                            ) . "))";

                        $userCanModerateComments = user_can($user, 'moderate_comments');
                        if ( ! $userCanModerateComments) {
                            $queryArgs['where'] .= " AND ( comment_approved = '1' )";
                        } else {
                            $queryArgs['where'] .= " AND ( comment_approved = '1' OR comment_approved = '0' )";
                        }
                    }
                } else {
                    $queryArgs['where'] .= " AND (post.post_type != 'project')";
                }
            }

            return $queryArgs;
        }
    }
endif;


/**
 * Main instance of UpStream.
 *
 * Returns the main instance of UpStream to prevent the need to use globals.
 *
 * @return UpStream
 * @since  1.0.0
 */
function UpStream()
{
    return UpStream::instance();
}

UpStream();

do_action('upstream_run');
