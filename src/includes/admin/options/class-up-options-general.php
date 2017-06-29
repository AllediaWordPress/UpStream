<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'UpStream_Options_General' ) ) :

/**
 * CMB2 Theme Options
 * @version 0.1.0
 */
class UpStream_Options_General {

    /**
     * Array of metaboxes/fields
     * @var array
     */
    public $id = 'upstream_general';

    /**
     * Page title
     * @var string
     */
    protected $title = '';

    /**
     * Menu Title
     * @var string
     */
    protected $menu_title = '';

    /**
     * Menu Title
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
     * @since 0.1.0
     */
    public function __construct() {
        // Set our title
        $this->title = __( 'General Settings', 'upstream' );
        $this->menu_title = __( 'General Settings', 'upstream' );
        $this->description = '';
    }
    /**
     * Returns the running object
     *
     * @return Myprefix_Admin
     **/
    public static function get_instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }



    /**
     * Add the options metabox to the array of metaboxes
     * @since  0.1.0
     */
    public function options() {

        $project_url = '<a target="_blank" href="' . home_url( 'projects' ) . '">' . home_url( 'projects' ) . '</a>';

        $options = apply_filters( $this->id . '_option_fields', array(
            'id'         => $this->id, // upstream_tasks
            'title'      => $this->title,
            'menu_title' => $this->menu_title,
            'desc'       => $this->description,
            'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->id ), ),
            'show_names' => true,
            'fields'     => array(

                array(
                    'name' => __( 'Labels', 'upstream' ),
                    'id'   => 'labels_title',
                    'type' => 'title',
                    'desc' => __( 'Here you can change the labels of various items. You could change Client to Customer or Bugs to Issues for example.<br>These labels will change on the frontend as well as in the admin area.', 'upstream' ),
                ),
                array(
                    'name' => __( 'Project Label', 'upstream' ),
                    'id'   => 'project',
                    'type' => 'labels',
                ),
                array(
                    'name' => __( 'Client Label', 'upstream' ),
                    'id'   => 'client',
                    'type' => 'labels',
                ),
                array(
                    'name' => __( 'Milestone Label', 'upstream' ),
                    'id'   => 'milestone',
                    'type' => 'labels',
                ),
                array(
                    'name' => __( 'Task Label', 'upstream' ),
                    'id'   => 'task',
                    'type' => 'labels',
                ),
                array(
                    'name' => __( 'Bug Label', 'upstream' ),
                    'id'   => 'bug',
                    'type' => 'labels',
                ),
                array(
                    'name' => __( 'File Label', 'upstream' ),
                    'id'   => 'file',
                    'type' => 'labels',
                ),

                array(
                    'name' => sprintf( __( '%s Area', 'upstream' ), upstream_client_label() ),
                    'id'   => 'client_area_title',
                    'type' => 'title',
                    'desc' => sprintf( __( 'Various options for the %1s login page and the frontend view. <br>%2s can view their projects by visiting %3s (URL is available after adding a %s).', 'upstream' ), upstream_client_label(), upstream_client_label_plural(), $project_url, upstream_project_label() ),
                ),
                array(
                    'name' => __( 'Login Page Heading', 'upstream' ),
                    'id'   => 'login_heading',
                    'type' => 'text',
                    'desc' => __( 'The heading used on the client login page.', 'upstream' ),
                ),
                array(
                    'name' => __( 'Login Page Text', 'upstream' ),
                    'id'   => 'login_text',
                    'type' => 'textarea_small',
                    'desc' => __( 'Text or instructions that can be added below the login form.', 'upstream' ),

                ),
                array(
                    'name' => __( 'Admin Email', 'upstream' ),
                    'id'   => 'admin_email',
                    'type' => 'text',
                    'desc' => __( 'The email address that clients can use to contact you.', 'upstream' ),
                ),
				array(
					'name' => __( 'Admin Support Link', 'upstream' ),
                    'id'   => 'admin_support_link',
                    'type' => 'text',
                    'desc' => __( 'Link to contact form or knowledgebase to help clients obtain support.', 'upstream' ),'name' => __( 'Admin Support Link', 'upstream' ),
				),
                array(
                    'name' => __( 'Disable Bugs', 'upstream' ),
                    'id'   => 'disable_bugs',
                    'type' => 'multicheck',
                    'desc' => __( 'Ticking this box will disable the Bugs section on both the frontend and the admin area.', 'upstream' ),
                    'default' => '',
                    'options' => array(
                        'yes' => 'Disable the Bugs section?'
                    ),
                    'select_all_button' => false
                ),
                array(
                    'name' => __( 'Disable Tasks', 'upstream' ),
                    'id'   => 'disable_tasks',
                    'type' => 'multicheck',
                    'desc' => __( 'Ticking this box will disable the Tasks section on both the frontend and the admin area.', 'upstream' ),
                    'default' => '',
                    'options' => array(
                        'yes' => 'Disable the Tasks section?'
                    ),
                    'select_all_button' => false
                ),
                array(
                    'name' => __( 'Disable Milestones', 'upstream' ),
                    'id'   => 'disable_milestones',
                    'type' => 'multicheck',
                    'desc' => __( 'Ticking this box will disable the Milestones section on both the frontend and the admin area.', 'upstream' ),
                    'default' => '',
                    'options' => array(
                        'yes' => 'Disable the Milestones section?'
                    ),
                    'select_all_button' => false
                ),
                array(
                    'name' => __( 'Disable Files', 'upstream' ),
                    'id'   => 'disable_files',
                    'type' => 'multicheck',
                    'desc' => __( 'Ticking this box will disable the Files section on both the frontend and the admin area.', 'upstream' ),
                    'default' => '',
                    'options' => array(
                        'yes' => 'Disable the Files section?'
                    ),
                    'select_all_button' => false
                ),
                array(
                    'name' => __( 'Disable Discussions', 'upstream' ),
                    'id'   => 'disable_discussion',
                    'type' => 'multicheck',
                    'desc' => __( 'Ticking this box will disable the Discussion section on both the frontend and the admin area.', 'upstream' ),
                    'default' => '',
                    'options' => array(
                        'yes' => 'Disable the Discussion section?'
                    ),
                    'select_all_button' => false
                ),
                array(
                    'name' => __( 'Remove Data', 'upstream' ),
                    'id'   => 'remove_data',
                    'type' => 'multicheck',
                    'desc' => __( 'Ticking this box will delete all UpStream data when plugin is uninstalled.', 'upstream' ),
                    'default' => '',
                    'options' => array(
                        'yes' => 'Remove all data on uninstall?'
                    ),
                    'select_all_button' => false
                ),

            ) )
        );

        return $options;

    }

}


endif;
