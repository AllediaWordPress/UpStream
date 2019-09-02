<?php
/**
 * UpStream Admin
 *
 * @class    UpStream_Admin
 * @author   UpStream
 * @category Admin
 * @package  UpStream/Admin
 * @version  1.0.0
 */

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * UpStream_Admin class.
 */
class UpStream_Admin
{

    /**
     * @var \Allex\Core
     */
    protected $framework;

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'includes']);
        add_action('init', [$this, 'init'], 13);
        add_filter('admin_body_class', [$this, 'admin_body_class']);
        add_filter('ajax_query_attachments_args', [$this, 'filter_user_attachments'], 10, 1);
        add_action('admin_menu', [$this, 'limitUpStreamUserAccess']);

        add_action('show_user_profile', [$this, 'renderAdditionalUserFields'], 10, 1);
        add_action('edit_user_profile', [$this, 'renderAdditionalUserFields'], 10, 1);
        add_action('personal_options_update', [$this, 'saveAdditionalUserFields'], 10, 1);
        add_action('edit_user_profile_update', [$this, 'saveAdditionalUserFields'], 10, 1);

        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            add_filter('comment_status_links', [$this, 'commentStatusLinks'], 10, 1);
            add_action('pre_get_comments', [$this, 'preGetComments'], 10, 1);
        }

        add_action(
            'wp_ajax_upstream:project.get_all_items_comments',
            ['UpStream_Metaboxes_Projects', 'fetchAllItemsComments']
        );

        add_action('cmb2_render_up_timestamp', [$this, 'renderCmb2TimestampField'], 10, 5);
        add_action('cmb2_sanitize_up_timestamp', [$this, 'sanitizeCmb2TimestampField'], 10, 5);

        add_action('cmb2_render_up_button', [$this, 'renderCmb2ButtonField'], 10, 5);
        add_action('cmb2_sanitize_up_button', [$this, 'sanitizeCmb2ButtonField'], 10, 5);

        add_action('cmb2_render_up_buttonsgroup', [$this, 'renderCmb2ButtonsGroupField'], 10, 5);
        add_action('cmb2_sanitize_up_buttonsgroup', [$this, 'sanitizeCmb2ButtonsGroupField'], 10, 5);

        add_filter('cmb2_override_option_get_upstream_general', [$this, 'filter_override_option_get_upstream_general'],
            10, 3);

        $this->framework = UpStream::instance()->get_container()['framework'];

        add_action('wp_ajax_upstream.milestone-edit.editmenuorder', [$this, 'editMenuOrder']);

        add_action('wp_ajax_upstream.task-edit.gettaskpercent', [$this, 'getTaskPercent']);
        add_action('wp_ajax_upstream.task-edit.gettaskstatus', [$this, 'getTaskStatus']);
    }

    /**
     * @since   1.24.5
     * @static
     */
    public function editMenuOrder()
    {
        //update_metadata('post', $_REQUEST['post_id'], 'upst_order', $_REQUEST['item_val']);
        $cur_post = array('ID'=>$_REQUEST['post_id'], 'menu_order'=>$_REQUEST['item_val']);
        wp_update_post( $cur_post );

        return 'success';
    }

    /**
     * @since   1.24.5
     * @static
     */
    public function getTaskPercent()
    {
        $task_id = $_REQUEST['task_id'];
        $cur_per = $_REQUEST['cur_per'];

        $option   = get_option('upstream_tasks');
        $statuses = isset($option['statuses']) ? $option['statuses'] : '';
        if ($statuses) {
            foreach ($statuses as $status) {
                if ($status['id'] == $task_id) {
                    if ($status['percent'] > $cur_per) {
                        echo $status['percent']; 
                        exit;
                    } else {
                        echo $cur_per;
                        exit;
                    }
                }
            }
        }
        return 0;
    }

    /**
     * @since   1.24.5
     * @static
     */
    public function getTaskStatus()
    {
        $task_percent = $_REQUEST['task_percent'];

        $option   = get_option('upstream_tasks');
        $statuses = isset($option['statuses']) ? $option['statuses'] : '';
        $sortArr = array();
        $selStatus = '';
        if ($statuses) {
            foreach ($statuses as $status) {
                $sortArr[$status['id']] = $status['percent'];
                if ($task_percent == '100' && $status['percent'] == '100') {
                    echo $status['id'];
                    exit;
                }
            }
        }
        asort($sortArr);
        if ($sortArr) {
            foreach ($sortArr as $id => $percent) {
                if ($percent > $task_percent) {
                    echo $selStatus;
                    exit;
                }
                $selStatus = $id;
            }
        }
        return 0;
    }

    /**
     * Filter comments for Comments.php page.
     *
     * @param array $query Query args array.
     *
     * @since   1.13.0
     * @static
     *
     */
    public static function preGetComments($query)
    {
        if ( ! isUserEitherManagerOrAdmin()) {
            $user = wp_get_current_user();

            if (in_array('upstream_user', $user->roles) || in_array('upstream_client_user', $user->roles)) {
                // Limit comments visibility to projects user is participating in.
                $allowedProjects               = upstream_get_users_projects($user);
                $query->query_vars['post__in'] = array_keys($allowedProjects);

                $userCanModerateComments = user_can($user, 'moderate_comments');
                $userCanDeleteComments   = user_can($user, 'delete_project_discussion');

                $query->query_vars['status'] = ['approve'];

                if ($userCanModerateComments) {
                    $query->query_vars['status'][] = 'hold';
                } elseif (empty($allowedProjects)) {
                    $query->query_vars['post__in'] = -1;
                }
            } else {
                // Hide Projects comments from other user types.
                $projects = get_posts([
                    'post_type'      => 'project',
                    'post_status'    => 'any',
                    'posts_per_page' => -1,
                ]);

                $ids = [];
                foreach ($projects as $project) {
                    $ids[] = $project->ID;
                }

                $query->query_vars['post__not_in'] = $ids;
            }
        }
    }

    /**
     * Set up WP-Table filters links.
     *
     * @param array $links Associative array of table filters.
     *
     * @return  array   $links
     * @since   1.13.0
     * @static
     *
     */
    public static function commentStatusLinks($links)
    {
        if ( ! isUserEitherManagerOrAdmin()) {
            $user = wp_get_current_user();

            if (in_array('upstream_user', $user->roles) || in_array('upstream_client_user', $user->roles)) {
                $userCanModerateComments = user_can($user, 'moderate_comments');
                $userCanDeleteComments   = user_can($user, 'delete_project_discussion');

                if ( ! $userCanModerateComments) {
                    unset($links['moderated'], $links['approved'], $links['spam']);

                    if ( ! $userCanDeleteComments) {
                        unset($links['trash']);
                    }
                }

                $projects = upstream_get_users_projects($user);

                $commentsQueryArgs = [
                    'post_type' => "project",
                    'post__in'  => array_keys($projects),
                    'count'     => true,
                ];

                $commentsCount      = new stdClass();
                $commentsCount->all = get_comments($commentsQueryArgs);

                $links['all'] = preg_replace(
                    '/<span class="all-count">\d+<\/span>/',
                    '<span class="all-count">' . $commentsCount->all . '</span>',
                    $links['all']
                );

                if (isset($links['moderated'])) {
                    $commentsCount->approved = get_comments(array_merge(
                        $commentsQueryArgs,
                        ['status' => "approve"]
                    ));

                    $links['approved'] = preg_replace(
                        '/<span class="approved-count">\d+<\/span>/',
                        '<span class="approved-count">' . $commentsCount->approved . '</span>',
                        $links['approved']
                    );

                    $commentsCount->pending = get_comments(array_merge($commentsQueryArgs, ['status' => "hold"]));

                    $links['moderated'] = preg_replace(
                        '/<span class="pending-count">\d+<\/span>/',
                        '<span class="pending-count">' . $commentsCount->pending . '</span>',
                        $links['moderated']
                    );
                }

                if (isset($links['trash'])) {
                    $commentsCount->trash = get_comments(array_merge($commentsQueryArgs, ['status' => "trash"]));

                    $links['trash'] = preg_replace(
                        '/<span class="trash-count">\d+<\/span>/',
                        '<span class="trash-count">' . $commentsCount->trash . '</span>',
                        $links['trash']
                    );
                }
            } else {
                $projects = get_posts([
                    'post_type'      => "project",
                    'posts_per_page' => -1,
                ]);

                $projectsIds = [];
                foreach ($projects as $project) {
                    $projectsIds[] = $project->ID;
                }

                $commentsQueryArgs = [
                    'post__not_in' => $projectsIds,
                    'count'        => true,
                ];

                if (isset($links['all'])) {
                    $count        = get_comments($commentsQueryArgs);
                    $links['all'] = preg_replace(
                        '/<span class="all-count">\d+<\/span>/',
                        '<span class="all-count">' . $count . '</span>',
                        $links['all']
                    );
                }

                if (isset($links['moderated'])) {
                    $count              = get_comments(array_merge($commentsQueryArgs, ['status' => "hold"]));
                    $links['moderated'] = preg_replace(
                        '/<span class="pending-count">\d+<\/span>/',
                        '<span class="pending-count">' . $count . '</span>',
                        $links['moderated']
                    );
                }

                if (isset($links['approved'])) {
                    $count             = get_comments(array_merge($commentsQueryArgs, ['status' => "approve"]));
                    $links['approved'] = preg_replace(
                        '/<span class="approved-count">\d+<\/span>/',
                        '<span class="approved-count">' . $count . '</span>',
                        $links['approved']
                    );
                }

                if (isset($links['spam'])) {
                    $count         = get_comments(array_merge($commentsQueryArgs, ['status' => "spam"]));
                    $links['spam'] = preg_replace(
                        '/<span class="spam-count">\d+<\/span>/',
                        '<span class="spam-count">' . $count . '</span>',
                        $links['spam']
                    );
                }

                if (isset($links['trash'])) {
                    $count          = get_comments(array_merge($commentsQueryArgs, ['status' => "trash"]));
                    $links['trash'] = preg_replace(
                        '/<span class="trash-count">\d+<\/span>/',
                        '<span class="trash-count">' . $count . '</span>',
                        $links['trash']
                    );
                }
            }
        }

        return $links;
    }

    /**
     * Render a button
     *
     * @param \CMB2_Field $field      The current CMB2_Field object.
     * @param string      $value      The field value passed through the escaping filter.
     * @param mixed       $objectId   The object id.
     * @param string      $objectType The type of object being handled.
     * @param \CMB2_Types $fieldType  Instance of the correspondent CMB2_Types object.
     *
     * @since   1.15.1
     * @static
     *
     */
    public static function renderCmb2ButtonsGroupField($field, $value, $objectId, $objectType, $fieldType)
    {
        $count = (int)$field->args['count'];

        $html      = '';
        $selectors = [];

        for ($i = 0; $i < $count; $i++) {
            $id          = $field->args['id'] . '_' . $i;
            $selectors[] = '#' . $id;

            $html .= sprintf('<button class="%s" id="%s" data-nonce="%s" data-slug="%s">%s</button>',
                isset($field->args['class']) ? $field->args['class'] : 'button-secondary',
                $id,
                $field->args['nonce'],
                $field->args['slugs'][$i],
                $field->args['labels'][$i]);

        }

        $selector = implode(', ', $selectors);

        $html .= '<script>';
        $html .= 'jQuery("' . $selector . '").on("click", function(event){event.preventDefault(); ' . $field->args['onclick'] . '});';
        $html .= '</script>';

        $html .= isset($field->args['desc']) ? '<p class="cmb2-metabox-description">' . $field->args['desc'] . '</p>' : '';

        echo $html;
    }

    /**
     * Ensure 'up_button' fills in missing button on newer CMB2.
     *
     * @param null            $overrideValue Sanitization override value to return.
     * @param mixed           $value         The actual field value.
     * @param mixed           $objectId      The object id.
     * @param string          $objectType    The type of object being handled.
     * @param \CMB2_Sanitizer $sanitizer     Sanitizer's instance.
     *
     * @return  mixed
     * @since   1.15.1
     * @static
     *
     */
    public static function sanitizeCmb2ButtonsGroupField($overrideValue, $value, $objectId, $fieldArgs, $sanitizer)
    {
    }

    /**
     * Render a button
     *
     * @param \CMB2_Field $field      The current CMB2_Field object.
     * @param string      $value      The field value passed through the escaping filter.
     * @param mixed       $objectId   The object id.
     * @param string      $objectType The type of object being handled.
     * @param \CMB2_Types $fieldType  Instance of the correspondent CMB2_Types object.
     *
     * @since   1.15.1
     * @static
     *
     */
    public static function renderCmb2ButtonField($field, $value, $objectId, $objectType, $fieldType)
    {

        $html = sprintf('<button class="%s" id="%s" data-nonce="%s">%s</button>',
            isset($field->args['class']) ? $field->args['class'] : 'button-secondary',
            $field->args['id'],
            $field->args['nonce'],
            $field->args['label']);

        $html .= isset($field->args['desc']) ? '<p class="cmb2-metabox-description">' . $field->args['desc'] . '</p>' : '';

        $selector = '#' . $field->_id();

        $html .= '<script>';
        $html .= 'jQuery("' . $selector . '").on("click", function(event){event.preventDefault(); ' . $field->args['onclick'] . '});';
        $html .= '</script>';

        echo $html;
    }

    /**
     * Ensure 'up_button' fills in missing button on newer CMB2.
     *
     * @param null            $overrideValue Sanitization override value to return.
     * @param mixed           $value         The actual field value.
     * @param mixed           $objectId      The object id.
     * @param string          $objectType    The type of object being handled.
     * @param \CMB2_Sanitizer $sanitizer     Sanitizer's instance.
     *
     * @return  mixed
     * @since   1.15.1
     * @static
     *
     */
    public static function sanitizeCmb2ButtonField($overrideValue, $value, $objectId, $fieldArgs, $sanitizer)
    {
    }

    /**
     * Render a modified 'text_date_timestamp' that will always use
     * its date's time being as 12:00:00 AM.
     *
     * @param \CMB2_Field $field      The current CMB2_Field object.
     * @param string      $value      The field value passed through the escaping filter.
     * @param mixed       $objectId   The object id.
     * @param string      $objectType The type of object being handled.
     * @param \CMB2_Types $fieldType  Instance of the correspondent CMB2_Types object.
     *
     * @since   1.15.1
     * @static
     *
     */
    public static function renderCmb2TimestampField($field, $value, $objectId, $objectType, $fieldType)
    {
        echo $fieldType->text_date_timestamp();
    }

        /**
     * Ensure 'up_timestamp' fields date's time are set to 12:00:00 AM before it is stored AS GMT/UTC.
     *
     * @param null            $overrideValue Sanitization override value to return.
     * @param mixed           $value         The actual field value.
     * @param mixed           $objectId      The object id.
     * @param string          $objectType    The type of object being handled.
     * @param \CMB2_Sanitizer $sanitizer     Sanitizer's instance.
     *
     * @return  mixed
     * @since   1.15.1
     * @static
     *
     */
    public static function sanitizeCmb2TimestampField($overrideValue, $value, $objectId, $fieldArgs, $sanitizer)
    {
        $value = trim((string)$value);

        if (strlen($value) > 0) {
            try {
                $date = DateTime::createFromFormat($fieldArgs['date_format'], $value);

                if ($date !== false) {
                    $date->setTime(0, 0, 0);
                    $value         = (string)$date->format('U');
                    $overrideValue = $value;
                } else {
                    $value = '';
                }
            } catch (\Exception $e) {
                $value = '';
            }
        }

        return $value;
    }

    public static function escapeCmb2TimestampField($value, $args, $field)
    {
        $value = (int)$value;
        if ($value > 0) {
            $date = new \DateTime('now');
            $date->setTimestamp($value);

            $value = $date->format($args['date_format']);
        }

        return $value;
    }

    public static function saveAdditionalUserFields($user_id)
    {
        if ( ! current_user_can('edit_user', $user_id)
             || ! isset($_POST['upstream'])
        ) {
            return false;
        }

        $data = &$_POST['upstream'];

        if (isset($data['comment_replies_notification'])) {
            $receiveNotifications = $data['comment_replies_notification'] !== 'no';

            update_user_meta($user_id, 'upstream_comment_replies_notification', $receiveNotifications ? 'yes' : 'no');

            unset($receiveNotifications);
        }
    }

    public static function renderAdditionalUserFields($user)
    {
        $receiveNotifications = userCanReceiveCommentRepliesNotification($user->ID); ?>
        <h2><?php _e('UpStream', 'upstream'); ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="upstream_comment_reply_notifications"><?php _e(
                            'Comment reply notifications',
                            'upstream'
                        ); ?></label>
                </th>
                <td>
                    <div>
                        <label>
                            <?php _e('Yes'); ?>
                            <input type="radio" name="upstream[comment_replies_notification]"
                                   value="1"<?php echo $receiveNotifications ? ' checked' : ''; ?>>
                        </label>
                        <label>
                            <?php _e('No'); ?>
                            <input type="radio" name="upstream[comment_replies_notification]"
                                   value="no"<?php echo $receiveNotifications ? '' : ' checked'; ?>>
                        </label>
                    </div>
                    <p class="description"><?php printf(
                            __('Whether to be notified when someone reply to your comments within %s.', 'upstream'),
                            upstream_project_label_plural(true)
                        ); ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Create id for newly added project/bugs/tasks statuses.
     * This method is called right before field data is saved to db.
     *
     * @param array       $value Array of the new data set.
     * @param array       $args  Field arguments.
     * @param \CMB2_Field $field The field object.
     *
     * @return  array           $value
     * @since   1.17.0
     * @static
     *
     */
    public static function onBeforeSave($value, $args, $field)
    {
        if (is_array($value)) {
            $value = self::createMissingIdsInSet($value);
        }

        return $value;
    }

    /**
     * Create missing id in a rowset.
     *
     * @param array $rowset Data array;
     *
     * @return  array
     * @since   1.17.0
     * @static
     *
     */
    public static function createMissingIdsInSet($rowset)
    {
        if ( ! is_array($rowset)) {
            return false;
        }

        if (count($rowset) > 0) {
            $indexesMissingId = [];
            $idsMap           = [];

            foreach ($rowset as $rowIndex => $row) {
                if ( ! isset($row['id'])
                     || empty($row['id'])
                ) {
                    $indexesMissingId[] = $rowIndex;
                } else {
                    $idsMap[$row['id']] = $rowIndex;
                }
            }

            if (count($indexesMissingId) > 0) {
                $newIdsLength    = 5;
                $newIdsCharsPool = 'abcdefghijklmnopqrstuvwxyz0123456789';

                foreach ($indexesMissingId as $rowIndex) {
                    do {
                        $id = upstreamGenerateRandomString($newIdsLength, $newIdsCharsPool);
                    } while (isset($idsMap[$id]));

                    $rowset[$rowIndex]['id'] = $id;
                    $idsMap[$id]             = $rowIndex;
                }
            }
        }

        return $rowset;
    }

    /**
     * Init the dependencies.
     */
    public function init()
    {
        do_action('alex_enable_module_upgrade', 'https://upstreamplugin.com/pricing/');
    }

    public function limitUpStreamUserAccess()
    {
        if (empty($_GET) || ! is_admin()) {
            return;
        }

        $user               = wp_get_current_user();
        $userIsUpStreamUser = count(array_intersect(
                (array)$user->roles,
                ['administrator', 'upstream_manager']
            )) === 0;

        if ($userIsUpStreamUser) {
            global $pagenow;

            $shouldRedirect = false;

            $postType          = isset($_GET['post_type']) ? $_GET['post_type'] : '';
            $isPostTypeProject = $postType === 'project';

            if ($pagenow === 'edit-tags.php') {
                if (isset($_GET['taxonomy'])
                    && $_GET['taxonomy'] === 'project_category'
                    && $isPostTypeProject
                ) {
                    $shouldRedirect = true;
                }
            } elseif ($pagenow === 'post.php'
                      && $isPostTypeProject
            ) {
                $projectMembersList = (array)get_post_meta((int)$_GET['post'], '_upstream_project_members', true);
                // Since he's not and Administrator nor an UpStream Manager, we need to check if he's participating in the project.
                if ( ! in_array($user->ID, $projectMembersList)) {
                    $shouldRedirect = true;
                }
            } elseif ($pagenow === 'post-new.php'
                      && $isPostTypeProject
            ) {
                $shouldRedirect = true;
            } elseif ($pagenow === 'edit.php'
                      && $postType === 'client'
            ) {
                $shouldRedirect = true;
            }

            if ($shouldRedirect) {
                // Redirect the user to the projects list page.
                $pagenow = 'edit.php';
                wp_redirect(admin_url('/edit.php?post_type=project'));
                exit;
            }
        }
    }

    /**
     * Include any classes we need within admin.
     */
    public function includes()
    {

        // option pages
        include_once('class-up-admin-options.php');
        include_once('options/option-functions.php');

        // metaboxes
        include_once('class-up-admin-metaboxes.php');
        include_once('metaboxes/metabox-functions.php');

        include_once('up-enqueues.php');
        include_once('class-up-admin-projects-menu.php');
        include_once('class-up-admin-project-columns.php');
        include_once('class-up-admin-client-columns.php');
        include_once('class-up-admin-pointers.php');
    }

    /**
     * Adds one or more classes to the body tag in the dashboard.
     *
     * @param String $classes Current body classes.
     *
     * @return String          Altered body classes.
     */
    public function admin_body_class($classes)
    {
        $screen = get_current_screen();

        if (in_array($screen->id, [
            'client',
            'edit-client',
            'project',
            'edit-project',
            'edit-project_category',
            'project_page_tasks',
            'project_page_bugs',
            'toplevel_page_upstream_general',
            'upstream_page_upstream_bugs',
            'upstream_page_upstream_tasks',
            'upstream_page_upstream_milestones',
            'upstream_page_upstream_clients',
            'upstream_page_upstream_projects',
        ])) {
            return "$classes upstream";
        }
    }

    /**
     * Only show current users media items
     *
     */
    public function filter_user_attachments($query = [])
    {
        $user  = wp_get_current_user();
        $roles = upstream_media_unrestricted_roles();

        // Get the user's role
        $match = array_intersect($user->roles, $roles);

        // If the user's has a role selected as unrestricted, we do not filter the attachments.
        if ( ! empty($match)) {
            return $query;
        }

        // The user should only see its own attachments.
        if (is_object($user) && isset($user->ID) && ! empty($user->ID)) {
            $query['author'] = $user->ID;
        }

        return $query;
    }

    /**
     * Override the media_comment_images option based on the current capabilities.
     *
     * @param string $test
     * @param        $default
     * @param        $instance
     *
     * @return array
     */
    public function filter_override_option_get_upstream_general($test, $default, $instance)
    {

        // Identify roles that has the upstream_comment_images capability.
        $roles         = array_keys(get_editable_roles());
        $capable_roles = [];
        foreach ($roles as $role_id) {
            $role = get_role($role_id);
            if ($role->has_cap('upstream_comment_images')) {
                $capable_roles[] = $role_id;
            }
        }

        $options = get_option('upstream_general');

        $options['media_comment_images'] = $capable_roles;


        return $options;
    }
}

return new UpStream_Admin();
