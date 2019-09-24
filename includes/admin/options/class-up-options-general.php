<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! class_exists('UpStream_Options_General')) :

    /**
     * CMB2 Theme Options
     *
     * @version 0.1.0
     */
    class UpStream_Options_General
    {

        /**
         * Array of metaboxes/fields
         *
         * @var array
         */
        public $id = 'upstream_general';

        /**
         * Page title
         *
         * @var string
         */
        protected $title = '';

        /**
         * Menu Title
         *
         * @var string
         */
        protected $menu_title = '';

        /**
         * Menu Title
         *
         * @var string
         */
        protected $description = '';

        /**
         * Holds an instance of the object
         *
         * @var Myprefix_Admin
         **/
        public static $instance = null;

        /**
         * Constructor
         *
         * @since 0.1.0
         */
        public function __construct()
        {
            // Set our title
            $this->title       = __('General', 'upstream');
            $this->menu_title  = $this->title;
            $this->description = '';

            add_action('wp_ajax_upstream_admin_reset_capabilities', [$this, 'reset_capabilities']);
            add_action('wp_ajax_upstream_admin_refresh_projects_meta', [$this, 'refresh_projects_meta']);
            add_action('wp_ajax_upstream_admin_cleanup_update_cache', [$this, 'cleanup_update_cache']);
            add_action('wp_ajax_upstream_admin_migrate_milestones_get_projects',
                [$this, 'migrate_milestones_get_projects']);
            add_action('wp_ajax_upstream_admin_migrate_milestones_for_project',
                [$this, 'migrate_milestones_for_project']);
        }

        /**
         * Returns the running object
         *
         * @return Myprefix_Admin
         **/
        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Get a list of user roles.
         *
         * @return array
         */
        protected function get_roles()
        {
            $list  = [];
            $roles = get_editable_roles();

            foreach ($roles as $role => $data) {
                $list[$role] = $data['name'];
            }

            return $list;
        }


        /**
         * Add the options metabox to the array of metaboxes
         *
         * @since  0.1.0
         */
        public function options()
        {
            $project_url = '<a target="_blank" href="' . home_url('projects') . '">' . home_url('projects') . '</a>';

            $roles = $this->get_roles();

            $options = apply_filters(
                $this->id . '_option_fields',
                [
                    'id'         => $this->id, // upstream_tasks
                    'title'      => $this->title,
                    'menu_title' => $this->menu_title,
                    'desc'       => $this->description,
                    'show_on'    => ['key' => 'options-page', 'value' => [$this->id],],
                    'show_names' => true,
                    'fields'     => [

                        /**
                         * General
                         */
                        [
                            'name' => __('General', 'upstream'),
                            'id'   => 'general_title',
                            'type' => 'title',
                        ],
                        [
                            'name'    => __('Filter Closed Items', 'upstream'),
                            'id'      => 'filter_closed_items',
                            'type'    => 'radio_inline',
                            'default' => '0',
                            'desc'    => __(
                                'Choose whether Projects, Tasks and Bugs will only display items that have “open” statuses. Items with “closed” statuses will still be loaded on the page, but users will have to use filters to view them. This option only applies if “Archive Closed Items” is set to “No”',
                                'upstream'
                            ),
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Archive Closed Items', 'upstream'),
                            'id'      => 'archive_closed_items',
                            'type'    => 'radio_inline',
                            'default' => '1',
                            'desc'    => __(
                                'Using the Archive feature means that Closed items are not loaded on the frontend. This can speed up your site if you have projects with many items. Do not use the Archive feature if you want users to find Closed items.',
                                'upstream'
                            ),
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Show Users\' Names', 'upstream'),
                            'id'      => 'show_users_name',
                            'type'    => 'radio_inline',
                            'default' => '0',
                            'desc'    => __(
                                'Show names on Project list (Front page)',
                                'upstream'
                            ),
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Project Users Roles', 'upstream'),
                            'id'      => 'project_user_roles',
                            'desc'    => __(
                                'Select the user roles that should be used to filter the list of users on projects.',
                                'upstream'
                            ),
                            'type'    => 'multicheck',
                            'default' => ['administrator', 'upstream_manager', 'upstream_user'],
                            'options' => $roles,
                        ],

                        /**
                         * Labels
                         */
                        [
                            'name'       => __('Labels', 'upstream'),
                            'id'         => 'labels_title',
                            'type'       => 'title',
                            'desc'       => __(
                                'Here you can change the labels of various items. You could change Client to Customer or Bugs to Issues for example.<br>These labels will change on the frontend as well as in the admin area.',
                                'upstream'
                            ),
                            'before_row' => '<hr>',
                        ],
                        [
                            'name' => __('Project Label', 'upstream'),
                            'id'   => 'project',
                            'type' => 'labels',
                        ],
                        [
                            'name' => __('Client Label', 'upstream'),
                            'id'   => 'client',
                            'type' => 'labels',
                        ],
                        [
                            'name' => __('Milestone Label', 'upstream'),
                            'id'   => 'milestone',
                            'type' => 'labels',
                        ],
                        [
                            'name' => __('Milestone Categories Label', 'upstream'),
                            'id'   => 'milestone_categories',
                            'type' => 'labels',
                        ],
                        [
                            'name' => __('Task Label', 'upstream'),
                            'id'   => 'task',
                            'type' => 'labels',
                        ],
                        [
                            'name' => __('Bug Label', 'upstream'),
                            'id'   => 'bug',
                            'type' => 'labels',
                        ],
                        [
                            'name' => __('File Label', 'upstream'),
                            'id'   => 'file',
                            'type' => 'labels',
                        ],
                        [
                            'name' => __('Discussion Label', 'upstream'),
                            'id'   => 'discussion',
                            'type' => 'labels',
                        ],

                        /**
                         * Client
                         */
                        [
                            'name'       => sprintf(__('%s Area', 'upstream'), upstream_client_label()),
                            'id'         => 'client_area_title',
                            'type'       => 'title',
                            'desc'       => sprintf(
                                __(
                                    'Various options for the %1s login page and the frontend view. <br>%2s can view their projects by visiting %3s (URL is available after adding a %s).',
                                    'upstream'
                                ),
                                upstream_client_label(),
                                upstream_client_label_plural(),
                                $project_url,
                                upstream_project_label()
                            ),
                            'before_row' => '<hr>',
                        ],
                        [
                            'name' => __('Login Page Heading', 'upstream'),
                            'id'   => 'login_heading',
                            'type' => 'text',
                            'desc' => __('The heading used on the client login page.', 'upstream'),
                        ],
                        [
                            'name' => __('Login Page Text', 'upstream'),
                            'id'   => 'login_text',
                            'type' => 'textarea_small',
                            'desc' => __('Text or instructions that can be added below the login form.', 'upstream'),

                        ],
                        [
                            'name'    => __('Login Page Client Logo', 'upstream'),
                            'id'      => 'login_client_logo',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether Client\'s Logo should be displayed on login page if available.',
                                'upstream'
                            ),
                            'default' => '1',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Login Page Project Name', 'upstream'),
                            'id'      => 'login_project_name',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether Project\'s name should be displayed on login page.',
                                'upstream'
                            ),
                            'default' => '1',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name' => __('Admin Email', 'upstream'),
                            'id'   => 'admin_email',
                            'type' => 'text',
                            'desc' => __('The email address that clients can use to contact you.', 'upstream'),
                        ],
                        [
                            'name'    => __('Admin Support Link Label', 'upstream'),
                            'id'      => 'admin_support_label',
                            'type'    => 'text',
                            'desc'    => __('Label that describes the Admin Support Link.', 'upstream'),
                            'default' => __('Contact Admin', 'upstream'),
                        ],
                        [
                            'name'    => __('Admin Support Link', 'upstream'),
                            'id'      => 'admin_support_link',
                            'type'    => 'text',
                            'desc'    => __(
                                'Link to contact form or knowledge base to help clients obtain support.',
                                'upstream'
                            ),
                            'default' => 'mailto:' . upstream_admin_email(),
                        ],
                        /**
                         * MEDIA
                         */
                        [
                            'name'       => __('Media', 'upstream'),
                            'id'         => 'media_filter',
                            'type'       => 'title',
                            'desc'       => __('Options to configure the list of media attachments.', 'upstream'),
                            'before_row' => '<hr>',
                        ],
                        [
                            'name'    => __('Who can see all the media?', 'upstream'),
                            'id'      => 'media_unrestricted_roles',
                            'desc'    => __(
                                'For security, UpStream users can normally only access their own media uploads. Select the roles who can see all the entire media library.',
                                'upstream'
                            ),
                            'type'    => 'multicheck',
                            'default' => ['administrator'],
                            'options' => $roles,

                        ],
                        [
                            'name'    => __('Who can post images in comments?', 'upstream'),
                            'id'      => 'media_comment_images',
                            'desc'    => __(
                                'By default, not all WordPress users can upload images. Select the roles who can add images to UpStream comments.',
                                'upstream'
                            ),
                            'type'    => 'multicheck',
                            'default' => array_keys($roles),
                            'options' => $roles,

                        ],
                        /**
                         * Collapse Sections
                         */
                        [
                            'name'       => __('Collapse Sections', 'upstream'),
                            'id'         => 'frontend_collapse_sections',
                            'type'       => 'title',
                            'desc'       => __(
                                'Options to collapse different sections on the client area on frontend.',
                                'upstream'
                            ),
                            'before_row' => '<hr>',
                        ],
                        [
                            'name'    => __('Collapse Project Details box', 'upstream'),
                            'id'      => 'collapse_project_details',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether to collapse the Project Details box automatically when a user opens a project page.',
                                'upstream'
                            ),
                            'default' => '0',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Collapse Project Milestones box', 'upstream'),
                            'id'      => 'collapse_project_milestones',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether to collapse the Milestones box automatically when a user opens a project page.',
                                'upstream'
                            ),
                            'default' => '0',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Collapse Project Tasks box', 'upstream'),
                            'id'      => 'collapse_project_tasks',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether to collapse the Tasks box automatically when a user opens a project page.',
                                'upstream'
                            ),
                            'default' => '0',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Collapse Project Bugs box', 'upstream'),
                            'id'      => 'collapse_project_bugs',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether to collapse the Bugs box automatically when a user opens a project page.',
                                'upstream'
                            ),
                            'default' => '0',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Collapse Project Files box', 'upstream'),
                            'id'      => 'collapse_project_files',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether to collapse the Files box automatically when a user opens a project page.',
                                'upstream'
                            ),
                            'default' => '0',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Collapse Project Discussion box', 'upstream'),
                            'id'      => 'collapse_project_discussion',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether to collapse the Discussion box automatically when a user opens a project page.',
                                'upstream'
                            ),
                            'default' => '0',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],

                        /**
                         * Toggle Features
                         */
                        [
                            'name'       => __('Toggle Features', 'upstream'),
                            'id'         => 'toggle_features',
                            'type'       => 'title',
                            'desc'       => __('Options to toggle different sections and features.', 'upstream'),
                            'before_row' => '<hr>',
                        ],
                        [
                            'name'    => __('Disable Clients and Client Users', 'upstream'),
                            'id'      => 'disable_clients',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether if Clients and Client Users can be added and used on Projects.',
                                'upstream'
                            ),
                            'default' => '0',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Select all client\'s users by default', 'upstream'),
                            'id'      => 'pre_select_users',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether if all client\'s users should be checked by default after change or select the client.',
                                'upstream'
                            ),
                            'default' => '0',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Disable Projects Categorization', 'upstream'),
                            'id'      => 'disable_categories',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether Projects can be sorted into categories by managers and users.',
                                'upstream'
                            ),
                            'default' => '0',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Project Progress Icons', 'upstream'),
                            'id'      => 'disable_project_overview',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether to display the Project Progress Icons section on frontend.',
                                'upstream'
                            ),
                            'default' => '0',
                            'options' => [
                                1 => __('Do not show', 'upstream'),
                                0 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Disable Project Details', 'upstream'),
                            'id'      => 'disable_project_details',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Choose whether to display the Project Details section on frontend.',
                                'upstream'
                            ),
                            'default' => '0',
                            'options' => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                        ],
                        [
                            'name'              => __('Disable Bugs', 'upstream'),
                            'id'                => 'disable_bugs',
                            'type'              => 'multicheck',
                            'desc'              => __(
                                'Ticking this box will disable the Bugs section on both the frontend and the admin area.',
                                'upstream'
                            ),
                            'default'           => '',
                            'options'           => [
                                'yes' => __('Disable the Bugs section?', 'upstream'),
                            ],
                            'select_all_button' => false,
                        ],
                        [
                            'name'              => __('Disable Tasks', 'upstream'),
                            'id'                => 'disable_tasks',
                            'type'              => 'multicheck',
                            'desc'              => __(
                                'Ticking this box will disable the Tasks section on both the frontend and the admin area.',
                                'upstream'
                            ),
                            'default'           => '',
                            'options'           => [
                                'yes' => __('Disable the Tasks section?', 'upstream'),
                            ],
                            'select_all_button' => false,
                        ],
                        [
                            'name'              => __('Disable Milestones', 'upstream'),
                            'id'                => 'disable_milestones',
                            'type'              => 'multicheck',
                            'desc'              => __(
                                'Ticking this box will disable the Milestones section on both the frontend and the admin area.',
                                'upstream'
                            ),
                            'default'           => '',
                            'options'           => [
                                'yes' => __('Disable the Milestones section?', 'upstream'),
                            ],
                            'select_all_button' => false,
                        ],
                        [
                            'name'              => __('Disable Milestone Categories', 'upstream'),
                            'id'                => 'disable_milestone_categories',
                            'type'              => 'radio_inline',
                            'desc'              => __(
                                'Ticking this box will disable the Milestone Categories section on both the frontend and the admin area.',
                                'upstream'
                            ),
                            'default'           => '1',
                            'options'           => [
                                0 => __('No', 'upstream'),
                                1 => __('Yes', 'upstream'),
                            ],
                            'select_all_button' => false,
                        ],
                        [
                            'name'              => __('Disable Files', 'upstream'),
                            'id'                => 'disable_files',
                            'type'              => 'multicheck',
                            'desc'              => __(
                                'Ticking this box will disable the Files section on both the frontend and the admin area.',
                                'upstream'
                            ),
                            'default'           => '',
                            'options'           => [
                                'yes' => __('Disable the Files section?', 'upstream'),
                            ],
                            'select_all_button' => false,
                        ],
                        [
                            'name'    => __('Disable Discussion on Projects', 'upstream'),
                            'id'      => 'disable_project_comments',
                            'type'    => 'radio_inline',
                            'desc'    => __(
                                'Either allow comments on projects on both the frontend and the admin area or hide the section.',
                                'upstream'
                            ),
                            'default' => '1',
                            'options' => [
                                '1' => __('Allow comments on projects', 'upstream'),
                                '0' => __('Disable section', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Disable Discussion on Milestones', 'upstream'),
                            'id'      => 'disable_comments_on_milestones',
                            'type'    => 'radio_inline',
                            'desc'    => sprintf(
                                __('Either allow comments on %s or hide the section.', 'upstream'),
                                __('Milestones', 'upstream')
                            ),
                            'default' => '1',
                            'options' => [
                                '1' => __('Allow comments on Milestones', 'upstream'),
                                '0' => __('Disable section', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Disable Discussion on Tasks', 'upstream'),
                            'id'      => 'disable_comments_on_tasks',
                            'type'    => 'radio_inline',
                            'desc'    => sprintf(
                                __('Either allow comments on %s or hide the section.', 'upstream'),
                                __('Tasks', 'upstream')
                            ),
                            'default' => '1',
                            'options' => [
                                '1' => __('Allow comments on Tasks', 'upstream'),
                                '0' => __('Disable section', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Disable Discussion on Bugs', 'upstream'),
                            'id'      => 'disable_comments_on_bugs',
                            'type'    => 'radio_inline',
                            'desc'    => sprintf(
                                __('Either allow comments on %s or hide the section.', 'upstream'),
                                __('Bugs', 'upstream')
                            ),
                            'default' => '1',
                            'options' => [
                                '1' => __('Allow comments on Bugs', 'upstream'),
                                '0' => __('Disable section', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Disable Discussion on Files', 'upstream'),
                            'id'      => 'disable_comments_on_files',
                            'type'    => 'radio_inline',
                            'desc'    => sprintf(
                                __('Either allow comments on %s or hide the section.', 'upstream'),
                                __('Files', 'upstream')
                            ),
                            'default' => '1',
                            'options' => [
                                '1' => __('Allow comments on Files', 'upstream'),
                                '0' => __('Disable section', 'upstream'),
                            ],
                        ],
                        [
                            'name'    => __('Show all projects in the frontend sidebar', 'upstream'),
                            'id'      => 'show_all_projects_sidebar',
                            'type'    => 'radio_inline',
                            'desc'    => __('If enabled, all projects will be displayed in the sidebar on frontend.',
                                'upstream'),
                            'default' => '0',
                            'options' => [
                                '0' => __('Show only the current project', 'upstream'),
                                '1' => __('Show all projects', 'upstream'),
                            ],
                        ],
                        [
                            'name'       => __('Send Notifications for New Comments', 'upstream'),
                            'id'         => 'send_notifications_for_new_comments',
                            'type'       => 'radio_inline',
                            'options'    => [
                                '1' => __('Enabled'),
                                '0' => __('Disabled'),
                            ],
                            'default'    => '1',
                            'desc'       => __('Check this to send a notification to the owner and creator of a milestone, task, or bug when someone comments on it.'),
                        ],

                        /**
                         * Maintenance
                         */
                        [
                            'name'       => __('Maintenance', 'upstream'),
                            'id'         => 'maintenance_title',
                            'type'       => 'title',
                            'before_row' => '<hr>',
                            'desc'       => __('General options for maintenance only. Be careful enabling any of these options.',
                                'upstream'),
                        ],
                        [
                            'name'    => __('Add default UpStream capabilities', 'upstream'),
                            'id'      => 'add_default_capabilities',
                            'type'    => 'up_buttonsgroup',
                            'count'   => 4,
                            'labels'  => [
                                __('Administrator', 'upstream'),
                                __('UpStream Manager', 'upstream'),
                                __('UpStream User', 'upstream'),
                                __('UpStream Client User', 'upstream'),
                            ],
                            'slugs'   => [
                                'administrator',
                                'upstream_manager',
                                'upstream_user',
                                'upstream_client_user',
                            ],
                            'desc'    => __(
                                'Clicking this button will reset all the capabilities to the default set for the following user roles: administrator, upstream_manager, upstream_user and upstream_client_user. This can\'t be undone.',
                                'upstream'
                            ),
                            'onclick' => 'upstream_reset_capabilities(event);',
                            'nonce'   => wp_create_nonce('upstream_reset_capabilities'),
                        ],
                        [
                            'name'    => __('Update Project Data', 'upstream'),
                            'id'      => 'refresh_projects_meta',
                            'type'    => 'up_button',
                            'label'   => __('Update', 'upstream'),
                            'desc'    => __(
                                'Clicking this button will recalculate the data for all the projects, including: project members, milestones\' tasks statuses, created time, project author. This can\'t be undone and can take some time if you have many projects.',
                                'upstream'
                            ),
                            'onclick' => 'upstream_refresh_projects_meta(event);',
                            'nonce'   => wp_create_nonce('upstream_refresh_projects_meta'),
                        ],
                        [
                            'name'    => __('Migrate Legacy Milestones', 'upstream'),
                            'id'      => 'migrate_milestones',
                            'type'    => 'up_button',
                            'label'   => __('Start migration', 'upstream'),
                            'desc'    => __(
                                'Clicking this button will force to migrate again all the legacy milestones (project meta data) to the new post type. Only do this if you had any issue with the migrated data after updating to the version 1.24.0. This can\'t be undone and can take some time if you have many projects.',
                                'upstream'
                            ),
                            'onclick' => 'upstream_migrate_milestones(event);',
                            'nonce'   => wp_create_nonce('upstream_migrate_milestones'),
                        ],
                        [
                            'name'    => __('Cleanup Plugin\'s Update Cache', 'upstream'),
                            'id'      => 'cleanup_update_cache',
                            'type'    => 'up_button',
                            'label'   => __('Cleanup', 'upstream'),
                            'desc'    => __(
                                'If you’re having problems seeing UpStream extension updates, click this button and you see any new plugin releases.',
                                'upstream'
                            ),
                            'onclick' => 'upstream_cleanup_update_cache(event);',
                            'nonce'   => wp_create_nonce('upstream_cleanup_update_cache'),
                        ],
                        [
                            'name'              => __('Debug', 'upstream'),
                            'id'                => 'debug',
                            'type'              => 'multicheck',
                            'desc'              => __(
                                'Ticking this box will enable special debug code and a new menu to inspect the debug information.',
                                'upstream'
                            ),
                            'default'           => '',
                            'options'           => [
                                '1' => __('Enabled', 'upstream'),
                            ],
                            'select_all_button' => false,
                        ],
                        [
                            'name'              => __('Remove Data', 'upstream'),
                            'id'                => 'remove_data',
                            'type'              => 'multicheck',
                            'desc'              => __(
                                'Ticking this box will delete all UpStream data when plugin is uninstalled.',
                                'upstream'
                            ),
                            'default'           => '',
                            'options'           => [
                                'yes' => __('Remove all data on uninstall?', 'upstream'),
                            ],
                            'select_all_button' => false,
                        ],

                    ],
                ]
            );

            return $options;
        }

        public function reset_capabilities()
        {
            $return = '';
            $abort  = false;

            if ( ! isset($_POST['nonce'])) {
                $return = 'error';
                $abort  = true;
            }

            if ( ! wp_verify_nonce($_POST['nonce'], 'upstream_reset_capabilities')) {
                $return = 'error';
                $abort  = true;
            }

            $validRoles = [
                'administrator',
                'upstream_manager',
                'upstream_user',
                'upstream_client_user',
            ];

            if ( ! isset($_POST['role']) || ! in_array($_POST['role'], $validRoles)) {
                $return = 'error';
                $abort  = true;
            }

            if ( ! $abort) {
                // Reset capabilities
                $roles = new UpStream_Roles();
                $roles->add_default_caps($_POST['role']);

                $return = 'success';
            }

            echo wp_json_encode($return);
            exit();
        }

        public function refresh_projects_meta()
        {
            $return = '';
            $abort  = false;

            if ( ! isset($_POST['nonce'])) {
                $return = 'error';
                $abort  = true;
            }

            if ( ! wp_verify_nonce($_POST['nonce'], 'upstream_refresh_projects_meta')) {
                $return = 'error';
                $abort  = true;
            }

            if ( ! $abort) {
                if ( ! class_exists('Upstream_Counts')) {
                    include_once UPSTREAM_PLUGIN_DIR . '/includes/class-up-counts.php';
                }

                $counts   = new Upstream_Counts(0);
                $projects = $counts->projects;

                if ( ! empty($projects)) {
                    foreach ($projects as $project) {
                        $projectObject = new UpStream_Project($project->ID);
                        $projectObject->update_project_meta();
                    }
                }

                $return = 'success';
            }

            echo wp_json_encode($return);
            exit();
        }

        public function cleanup_update_cache()
        {
            $return = '';
            $abort  = false;

            if ( ! isset($_POST['nonce'])) {
                $return = 'error';
                $abort  = true;
            }

            if ( ! wp_verify_nonce($_POST['nonce'], 'upstream_cleanup_update_cache')) {
                $return = 'error';
                $abort  = true;
            }

            if ( ! $abort) {
                $addons = apply_filters('allex_addons', [], 'upstream');

                foreach ($addons as $extension) {
                    $extension = str_replace('upstream-', '', $extension['slug']);
                    delete_transient('upstream.' . $extension . ':plugin_latest_version');
                }
                delete_site_transient('update_plugins');

                $return = 'success';
            }

            echo wp_json_encode($return);
            exit();
        }

        /**
         * Migrate the project milestones
         */
        public function migrate_milestones_get_projects()
        {
            if ( ! isset($_GET['nonce'])) {
                wp_die(__('Invalid Nonce'), 'Forbidden', ['response' => 403]);
            }

            if ( ! wp_verify_nonce($_GET['nonce'], 'upstream_migrate_milestones')) {
                wp_die(__('Invalid Nonce'), 'Forbidden', ['response' => 403]);
            }

            $return = [];

            $projects = get_posts([
                'post_type'      => 'project',
                'post_status'    => 'any',
                'meta_key'       => '_upstream_project_milestones',
                'posts_per_page' => -1,
            ]);

            if ( ! empty($projects)) {
                foreach ($projects as $project) {
                    $milestones = get_post_meta($project->ID, '_upstream_project_milestones', true);

                    $return[] = [
                        'id'    => $project->ID,
                        'title' => $project->post_title,
                        'count' => count($milestones),
                    ];
                }
            }

            echo wp_json_encode($return);
            exit();
        }

        public function migrate_milestones_for_project()
        {
            if ( ! isset($_POST['nonce'])) {
                wp_die(__('Invalid Nonce'), 'Forbidden', ['response' => 403]);
            }

            if ( ! wp_verify_nonce($_POST['nonce'], 'upstream_migrate_milestones')) {
                wp_die(__('Invalid Nonce'), 'Forbidden', ['response' => 403]);
            }

            $return = [];

            if ( ! isset($_POST['projectId']) || empty((int)($_POST['projectId']))) {
                wp_die(__('Invalid project id'), 'Project not found', ['response' => 400]);
            }

            $projectId = (int)$_POST['projectId'];

            $return['success'] = \UpStream\Milestones::migrateLegacyMilestonesForProject($projectId);

            echo wp_json_encode($return);
            exit();
        }
    }
endif;
