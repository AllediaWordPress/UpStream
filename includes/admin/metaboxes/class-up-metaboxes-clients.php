<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use Cmb2Grid\Grid\Cmb2Grid;
use UpStream\Traits\Singleton;

/**
 * Clients Metabox Class.
 *
 * @package     UpStream
 * @subpackage  Admin\Metaboxes
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.11.0
 * @final
 */
final class UpStream_Metaboxes_Clients
{
    use Singleton;

    /**
     * The post type where this metabox will be used.
     *
     * @since   1.11.0
     * @access  protected
     * @static
     *
     * @var     string
     */
    protected static $postType = 'client';

    /**
     * String that represents the singular form of the post type's name.
     *
     * @since   1.11.0
     * @access  protected
     * @static
     *
     * @var     string
     */
    protected static $postTypeLabelSingular = null;

    /**
     * String that represents the plural form of the post type's name.
     *
     * @since   1.11.0
     * @access  protected
     * @static
     *
     * @var     string
     */
    protected static $postTypeLabelPlural = null;

    /**
     * Prefix used on form fields.
     *
     * @since   1.11.0
     * @access  protected
     * @static
     *
     * @var     string
     */
    protected static $prefix = '_upstream_client_';

    /**
     * Class constructor.
     *
     * @since   1.11.0
     */
    public function __construct()
    {
        self::$postTypeLabelSingular = upstream_client_label();
        self::$postTypeLabelPlural   = upstream_client_label_plural();

        self::attachHooks();

        // Enqueues the default ThickBox assets.
        add_thickbox();

        // Render all inner metaboxes.
        self::renderMetaboxes();
    }

    /**
     * Attach all hooks.
     *
     * @since   1.13.6
     * @static
     */
    public static function attachHooks()
    {
        // Define all ajax endpoints.
        $ajaxEndpointsSchema = [
            'remove_user'             => 'removeUser',
            'fetch_unassigned_users'  => 'fetchUnassignedUsers',
            'add_existent_users'      => 'addExistentUsers',
            'migrate_legacy_user'     => 'migrateLegacyUser',
            'discard_legacy_user'     => 'discardLegacyUser',
            'fetch_user_permissions'  => 'fetchUserPermissions',
            'update_user_permissions' => 'updateUserPermissions',
        ];

        foreach ($ajaxEndpointsSchema as $endpoint => $callbackName) {
            add_action('wp_ajax_upstream:client.' . $endpoint, [__CLASS__, $callbackName]);
        }
    }

    /**
     * Render all inner-metaboxes.
     *
     * @since   1.11.0
     * @access  private
     * @static
     */
    private static function renderMetaboxes()
    {
        self::renderDetailsMetabox();
        self::renderLogoMetabox();

        $metaboxesCallbacksList = ['createUsersMetabox'];
        foreach ($metaboxesCallbacksList as $callbackName) {
            add_action('add_meta_boxes', [__CLASS__, $callbackName]);
        }
    }

    /**
     * Renders the Details metabox using CMB2.
     *
     * @since   1.11.0
     * @static
     */
    public static function renderDetailsMetabox()
    {
        $metabox = new_cmb2_box([
            'id'           => self::$prefix . 'details',
            'title'        => '<span class="dashicons dashicons-admin-generic"></span>' . __('Details', 'upstream'),
            'object_types' => [self::$postType],
            'context'      => 'side',
            'priority'     => 'high',
        ]);

        $phoneField = $metabox->add_field([
            'name' => __('Phone Number', 'upstream'),
            'id'   => self::$prefix . 'phone',
            'type' => 'text',
        ]);

        $websiteField = $metabox->add_field([
            'name' => __('Website', 'upstream'),
            'id'   => self::$prefix . 'website',
            'type' => 'text_url',
        ]);

        $addressField = $metabox->add_field([
            'name' => __('Address', 'upstream'),
            'id'   => self::$prefix . 'address',
            'type' => 'textarea_small',
        ]);

        $fields = [];

        $fields = apply_filters('upstream_client_metabox_fields', $fields);
        ksort($fields);

        // loop through ordered fields and add them to the group
        if ($fields) {
            foreach ($fields as $key => $value) {
                $fields[$key] = $metabox->add_field($value);
            }
        }

        $metaboxGrid    = new Cmb2Grid($metabox);
        $metaboxGridRow = $metaboxGrid->addRow([$phoneField, $websiteField, $addressField]);
    }

    /**
     * Renders Logo metabox using CMB2.
     *
     * @since   1.11.0
     * @static
     */
    public static function renderLogoMetabox()
    {
        $metabox = new_cmb2_box([
            'id'           => self::$prefix . 'client_logo',
            'title'        => '<span class="dashicons dashicons-format-image"></span>' . __("Logo", 'upstream'),
            'object_types' => [self::$postType],
            'context'      => 'side',
            'priority'     => 'core',
        ]);

        $metabox->add_field([
            'id'         => self::$prefix . 'logo',
            'type'       => 'file',
            'name'       => __('Image URL', 'upstream'),
            'query_args' => [
                'type' => 'image/*',
            ],
        ]);
    }

    /**
     * Renders the users metabox.
     * This is where all client users are listed.
     *
     * @since   1.11.0
     * @static
     */
    public static function renderUsersMetabox()
    {
        $client_id = get_the_id();
        $usersList = (array)self::getUsersFromClient($client_id); ?>

        <div class="upstream-row">
            <a
                    id="add-existent-user"
                    name="<?php echo __('Add Existing Users', 'upstream'); ?>"
                    href="#TB_inline?width=600&height=300&inlineId=modal-add-existent-user"
                    class="thickbox button"
            ><?php echo __('Add Existing Users', 'upstream'); ?></a>
        </div>
        <div class="upstream-row">
            <table id="table-users" class="wp-list-table widefat fixed striped posts upstream-table">
                <thead>
                <tr>
                    <th><?php echo __('Name', 'upstream'); ?></th>
                    <th><?php echo __('Email', 'upstream'); ?></th>
                    <th><?php echo __('Assigned by', 'upstream'); ?></th>
                    <th class="text-center"><?php echo __('Assigned at', 'upstream'); ?></th>
                    <th class="text-center"><?php echo __('Remove?', 'upstream'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($usersList) > 0):
                    $dateFormat = get_option('date_format') . ' ' . get_option('time_format');

                    foreach ($usersList as $user):
                        $assignedAt = new DateTime($user->assigned_at); ?>
                        <tr data-id="<?php echo $user->id; ?>">
                            <td>
                                <a title="<?php echo sprintf(__("Managing %s's Permissions"), $user->name); ?>"
                                   href="#TB_inline?width=600&height=425&inlineId=modal-user-permissions"
                                   class="thickbox"><?php echo $user->name; ?></a>
                            </td>
                            <td><?php echo $user->email; ?></td>
                            <td><?php echo $user->assigned_by; ?></td>
                            <td class="text-center"><?php echo $assignedAt->format($dateFormat); ?></td>
                            <td class="text-center">
                                <a href="#" onclick="javascript:void(0);" class="up-u-color-red" data-remove-user>
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr data-empty>
                        <td colspan="5"><?php echo __("There are no users assigned yet.", 'upstream'); ?></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>

            <p>
                <span
                        class="dashicons dashicons-info"></span> <?php echo __(
                    'Removing a user only means that they will no longer be associated with this client. Their WordPress account will not be deleted.',
                    'upstream'
                ); ?>
            </p>
        </div>

        <?php
        self::renderUserPermissionsModal();
        self::renderAddExistentUserModal();
    }

    /**
     * Retrieve all Client Users associated with a given client.
     *
     * @since   1.11.0
     * @access  private
     * @static
     *
     * @param   int $client_id The reference id.
     *
     * @return  array
     */
    private static function getUsersFromClient($client_id)
    {
        if ((int)$client_id <= 0) {
            return [];
        }

        // Let's cache all users basic info so we don't have to query each one of them later.
        $rowset = (array)get_users([
            'fields' => ['ID', 'display_name', 'user_login', 'user_email'],
        ]);

        // Create our users hash map.
        $users = [];
        foreach ($rowset as $row) {
            $users[(int)$row->ID] = [
                'id'    => (int)$row->ID,
                'name'  => $row->display_name,
                'email' => $row->user_email,
            ];
        }
        unset($rowset);

        $clientUsersList    = [];
        $clientUsersIdsList = [];

        // Retrieve all client users.
        $meta = (array)get_post_meta($client_id, '_upstream_new_client_users');
        if ( ! empty($meta)) {
            foreach ($meta[0] as $clientUser) {
                if ( ! empty($clientUser) && is_array($clientUser) && isset($users[$clientUser['user_id']]) && ! in_array(
                        $clientUser['user_id'],
                        $clientUsersIdsList
                    )) {
                    $user = $users[$clientUser['user_id']];

                    $user['assigned_at'] = $clientUser['assigned_at'];
                    $user['assigned_by'] = $users[$clientUser['assigned_by']]['name'];

                    $clientUsersList[] = (object)$user;
                    $clientUsersIdsList[] = $clientUser['user_id'];
                }
            }
        }

        return $clientUsersList;
    }

    /**
     * Renders the modal's html which is used to manage a given Client User's permissions.
     *
     * @since   1.11.0
     * @access  private
     * @static
     */
    private static function renderUserPermissionsModal()
    {
        ?>
        <div id="modal-user-permissions" style="display: none;">
            <div id="form-user-permissions">
                <div>
                    <h3><?php echo __("UpStream's Custom Permissions", 'upstream'); ?></h3>
                    <table class="wp-list-table widefat fixed striped posts upstream-table">
                        <thead>
                        <tr>
                            <th class="text-center" style="width: 20px;">
                                <input type="checkbox"/>
                            </th>
                            <th><?php echo __('Permission', 'upstream'); ?></th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div>
                    <div class="up-form-group">
                        <button
                                type="submit"
                                class="button button-primary"
                                data-label="<?php echo __('Update Permissions', 'upstream'); ?>"
                                data-loading-label="<?php echo __('Updating...', 'upstream'); ?>"
                        ><?php echo __('Update Permissions', 'upstream'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renders the modal's html which is used to associate existent client users with a client.
     *
     * @since   1.11.0
     * @access  private
     * @static
     */
    private static function renderAddExistentUserModal()
    {
        ?>
        <div id="modal-add-existent-user" style="display: none;">
            <div class="upstream-row">
                <p><?php echo sprintf(__(
                        'These are all the users assigned with the role <code>%s</code> and not related to this client yet.',
                        'upstream'
                    ), __('UpStream Client User', 'upstream')); ?></p>
            </div>
            <div class="upstream-row">
                <table id="table-add-existent-users" class="wp-list-table widefat fixed striped posts upstream-table">
                    <thead>
                    <tr>
                        <th class="text-center" style="width: 20px;">
                            <input type="checkbox"/>
                        </th>
                        <th><?php echo __('Name', 'upstream'); ?></th>
                        <th><?php echo __('Email', 'upstream'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td colspan="3"><?php echo __('No users found.', 'upstream'); ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="upstream-row submit"></div>
        </div>
        <?php
    }

    /**
     * It defines the Users metabox.
     *
     * @since   1.11.0
     * @static
     */
    public static function createUsersMetabox()
    {
        add_meta_box(
            self::$prefix . 'users',
            '<span class="dashicons dashicons-groups"></span>' . __("Users", 'upstream'),
            [__CLASS__, 'renderUsersMetabox'],
            self::$postType,
            'normal'
        );
    }

    /**
     * Renders the Legacy Users metabox.
     *
     * @since   1.11.0
     * @static
     */
    public static function renderLegacyUsersMetabox()
    {
        $client_id = upstream_post_id();

        $legacyUsersErrors = get_post_meta($client_id, '_upstream_client_legacy_users_errors')[0];

        $legacyUsersMeta = get_post_meta($client_id, '_upstream_client_users')[0];
        $legacyUsers     = [];
        foreach ($legacyUsersMeta as $a) {
            $legacyUsers[$a['id']] = $a;
        }
        unset($legacyUsersMeta); ?>
        <div class="upstream-row">
            <p><?php echo __(
                    'The users listed below are those old <code>UpStream Client Users</code> that could not be automatically converted/migrated to <code>WordPress Users</code> by UpStream for some reason. More details on the Disclaimer metabox.',
                    'upstream'
                ); ?></p>
        </div>
        <div class="upstream-row">
            <table id="table-legacy-users" class="wp-list-table widefat fixed striped posts upstream-table">
                <thead>
                <tr>
                    <th><?php echo __('First Name', 'upstream'); ?></th>
                    <th><?php echo __('Last Name', 'upstream'); ?></th>
                    <th><?php echo __('Email', 'upstream'); ?></th>
                    <th><?php echo __('Phone', 'upstream'); ?></th>
                    <th class="text-center"><?php echo __('Migrate?', 'upstream'); ?></th>
                    <th class="text-center"><?php echo __('Discard?', 'upstream'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($legacyUsersErrors as $legacyUserId => $legacyUserError):
                    $user = $legacyUsers[$legacyUserId];
                    $userFirstName = isset($user['fname']) ? trim($user['fname']) : '';
                    $userLastName = isset($user['lname']) ? trim($user['lname']) : '';
                    $userEmail = isset($user['email']) ? trim($user['email']) : '';
                    $userPhone = isset($user['phone']) ? trim($user['phone']) : '';

                    switch ($legacyUserError) {
                        case 'ERR_EMAIL_NOT_AVAILABLE':
                            $errorMessage = __(
                                "This email address is already being used by another user.",
                                'upstream'
                            );
                            break;
                        case 'ERR_EMPTY_EMAIL':
                            $errorMessage = __("Email addresses cannot be empty.", 'upstream');
                            break;
                        case 'ERR_INVALID_EMAIL':
                            $errorMessage = __("Invalid email address.", 'upstream');
                            break;
                        default:
                            $errorMessage = $legacyUserError;
                            break;
                    }

                    $emptyValueString = '<i>' . __('empty', 'upstream') . '</i>'; ?>
                    <tr data-id="<?php echo $legacyUserId; ?>">
                        <td data-column="fname"><?php echo ! empty($userFirstName) ? $userFirstName : $emptyValueString; ?></td>
                        <td data-column="lname"><?php echo ! empty($userLastName) ? $userLastName : $emptyValueString; ?></td>
                        <td data-column="email"><?php echo ! empty($userEmail) ? $userEmail : $emptyValueString; ?></td>
                        <td data-column="phone"><?php echo ! empty($userPhone) ? $userPhone : $emptyValueString; ?></td>
                        <td class="text-center">
                            <a name="<?php echo __('Migrating Client User', 'upstream'); ?>"
                               href="#TB_inline?width=350&height=400&inlineId=modal-migrate-user" class="thickbox"
                               data-modal-identifier="user-migration">
                                <span class="dashicons dashicons-plus-alt"></span>
                            </a>
                        </td>
                        <td class="text-center">
                            <a href="#" onclick="javascript:void(0);" class="up-u-color-red"
                               data-action="legacyUser:discard">
                                <span class="dashicons dashicons-trash"></span>
                            </a>
                        </td>
                    </tr>
                    <tr data-id="<?php echo $legacyUserId; ?>">
                        <td colspan="7">
                            <span class="dashicons dashicons-warning"></span>&nbsp;<?php echo $errorMessage; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Ajax endpoint responsible for removing Client Users from a given client.
     *
     * @since   1.11.0
     * @static
     */
    public static function removeUser()
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'err'     => null,
        ];

        try {
            if ( ! upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || ! isset($_POST['client'])) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            $clientId = (int)$_POST['client'];
            if ($clientId <= 0) {
                throw new \Exception(__('Invalid Client ID.', 'upstream'));
            }

            $userId = (int)@$_POST['user'];
            if ($userId <= 0) {
                throw new \Exception(__('Invalid User ID.', 'upstream'));
            }

            $clientUsersMetaKey = '_upstream_new_client_users';
            $meta               = (array)get_post_meta($clientId, $clientUsersMetaKey);
            if ( ! empty($meta)) {
                $newClientUsersList = [];
                foreach ($meta[0] as $clientUser) {
                    if ( ! empty($clientUser) && is_array($clientUser)) {
                        if ((int)$clientUser['user_id'] !== $userId) {
                            $newClientUsersList[] = $clientUser;
                        }
                    }
                }

                update_post_meta($clientId, $clientUsersMetaKey, $newClientUsersList);
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Ajax endpoint responsible for fetching all Client Users that are not related to
     * the given client.
     *
     * @since   1.11.0
     * @static
     */
    public static function fetchUnassignedUsers()
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'data'    => [],
            'err'     => null,
        ];

        try {
            if ( ! upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_GET) || ! isset($_GET['client'])) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            $clientId = (int)$_GET['client'];
            if ($clientId <= 0) {
                throw new \Exception(__('Invalid Client ID.', 'upstream'));
            }

            $clientUsers     = (array)self::getUsersFromClient($clientId);
            $excludeTheseIds = [get_current_user_id()];
            if (count($clientUsers) > 0) {
                foreach ($clientUsers as $clientUser) {
                    $excludeTheseIds[] = $clientUser->id;
                }
            }

            $rowset = (array)get_users([
                'exclude'  => $excludeTheseIds,
                'role__in' => ['upstream_client_user'],
                'orderby'  => 'ID',
            ]);

            global $wp_roles;

            foreach ($rowset as $row) {
                $user = [
                    'id'       => $row->ID,
                    'name'     => $row->display_name,
                    'username' => $row->user_login,
                    'email'    => $row->user_email,
                ];

                $response['data'][] = $user;
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Ajax endpoint responsible for associating existent Client Users to a given client.
     *
     * @since   1.11.0
     * @static
     */
    public static function addExistentUsers()
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'data'    => [],
            'err'     => null,
        ];

        try {
            if ( ! upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || ! isset($_POST['client'])) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            $clientId = (int)$_POST['client'];
            if ($clientId <= 0) {
                throw new \Exception(__('Invalid Client ID.', 'upstream'));
            }

            if ( ! isset($_POST['users']) && empty($_POST['users'])) {
                throw new \Exception(__('Users IDs cannot be empty.', 'upstream'));
            }

            $currentUser  = get_userdata(get_current_user_id());
            $nowTimestamp = time();
            $now          = date('Y-m-d H:i:s', $nowTimestamp);

            $clientUsersMetaKey = '_upstream_new_client_users';
            $clientUsersList    = array_filter((array)get_post_meta($clientId, $clientUsersMetaKey, true));
            $clientNewUsersList = [];

            $usersIdsList = (array)$_POST['users'];
            foreach ($usersIdsList as $user_id) {
                $user_id = (int)$user_id;
                if ($user_id > 0) {
                    $clientUsersList[] = [
                        'user_id'     => $user_id,
                        'assigned_by' => $currentUser->ID,
                        'assigned_at' => $now,
                    ];
                }
            }

            foreach ($clientUsersList as $clientUser) {
                $clientUser            = (array)$clientUser;
                $clientUser['user_id'] = (int)$clientUser['user_id'];

                if ( ! isset($clientNewUsersList[$clientUser['user_id']])) {
                    $clientNewUsersList[$clientUser['user_id']] = $clientUser;
                }
            }
            update_post_meta($clientId, $clientUsersMetaKey, array_values($clientNewUsersList));

            global $wpdb;

            $rowset = (array)get_users([
                'fields'  => ['ID', 'display_name', 'user_login', 'user_email'],
                'include' => $usersIdsList,
            ]);

            $assignedAt = upstream_format_date($now);

            foreach ($rowset as $user) {
                $response['data'][] = [
                    'id'          => (int)$user->ID,
                    'name'        => $user->display_name,
                    'email'       => $user->user_email,
                    'assigned_by' => $currentUser->display_name,
                    'assigned_at' => $assignedAt,
                ];
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Ajax endpoint responsible for fetching all permissions a given Client User might have.
     *
     * @since   1.11.0
     * @static
     */
    public static function fetchUserPermissions()
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'data'    => [],
            'err'     => null,
        ];

        try {
            if ( ! upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_GET) || ! isset($_GET['client']) || ! isset($_GET['user'])) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            $client_id = (int)$_GET['client'];
            if ($client_id <= 0) {
                throw new \Exception(__('Invalid Client ID.', 'upstream'));
            }

            $client_user_id = (int)$_GET['user'];
            if ($client_user_id <= 0) {
                throw new \Exception(__('Invalid User ID.', 'upstream'));
            }

            if ( ! upstream_do_client_user_belongs_to_client($client_user_id, $client_id)) {
                throw new \Exception(__("This Client User is not associated with this Client.", 'upstream'));
            }

            $response['data'] = array_values(upstream_get_client_user_permissions($client_user_id));

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    /**
     * Ajax endpoint responsible for updating a given Client User permissions.
     *
     * @since   1.11.0
     * @static
     */
    public static function updateUserPermissions()
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'err'     => null,
        ];

        try {
            if ( ! upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || ! isset($_POST['client'])) {
                throw new \Exception(__('Invalid request.', 'upstream'));
            }

            $client_id = (int)$_POST['client'];
            if ($client_id <= 0) {
                throw new \Exception(__('Invalid Client ID.', 'upstream'));
            }

            $client_user_id = isset($_POST['user']) ? (int)$_POST['user'] : 0;
            if ($client_user_id <= 0) {
                throw new \Exception(__('Invalid User ID.', 'upstream'));
            }

            if ( ! upstream_do_client_user_belongs_to_client($client_user_id, $client_id)) {
                throw new \Exception(__("This Client User is not associated with this Client.", 'upstream'));
            }

            $clientUser = new \WP_User($client_user_id);
            if (array_search('upstream_client_user', $clientUser->roles) === false) {
                throw new \Exception(__("This user doesn't seem to be a valid Client User.", 'upstream'));
            }

            if (isset($_POST['permissions']) && ! empty($_POST['permissions'])) {
                $permissions    = upstream_get_client_users_permissions();
                $newPermissions = (array)$_POST['permissions'];

                $deniedPermissions = (array)array_diff(array_keys($permissions), $newPermissions);
                foreach ($deniedPermissions as $permissionKey) {
                    // Make sure this is a valid permission.
                    if (isset($permissions[$permissionKey])) {
                        $clientUser->add_cap($permissionKey, false);
                    }
                }

                foreach ($newPermissions as $permissionKey) {
                    // Make sure this is a valid permission.
                    if (isset($permissions[$permissionKey])) {
                        $clientUser->add_cap($permissionKey, true);
                    }
                }
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }
}
