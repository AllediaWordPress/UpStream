<?php
// Prevent direct access.
if (!defined('ABSPATH')) exit;

use \UpStream\Traits\Singleton;
use \Cmb2Grid\Grid\Cmb2Grid;
use \UpStream\Migrations\ClientUsers as ClientUsersMigration;

// @todo
class UpStream_Metaboxes_Clients
{
    use Singleton;

    /**
     * @var string
     * @todo
     */
    protected static $postType = 'client';

    /**
     * @var string
     * @todo
     */
    protected static $postTypeLabelSingular = null;

    /**
     * @var string
     * @todo
     */
    protected static $postTypeLabelPlural = null;

    /**
     * @todo
     */
    protected static $prefix = '_upstream_client_';

    public function __construct()
    {
        self::$postTypeLabelSingular = upstream_client_label();
        self::$postTypeLabelPlural = upstream_client_label_plural();

        $namespace = get_class(self::$instance);
        add_action('wp_ajax_upstream:client.add_new_user', array($namespace, 'addNewUser'));
        add_action('wp_ajax_upstream:client.remove_user', array($namespace, 'removeUser'));
        add_action('wp_ajax_upstream:client.fetch_unassigned_users', array($namespace, 'fetchUnassignedUsers'));
        add_action('wp_ajax_upstream:client.add_existent_users', array($namespace, 'addExistentUsers'));
        add_action('wp_ajax_upstream:client.migrate_legacy_user', array($namespace, 'migrateLegacyUser'));
        add_action('wp_ajax_upstream:client.discard_legacy_user', array($namespace, 'discardLegacyUser'));

        // Enqueues the default ThickBox assets.
        add_thickbox();

        self::renderMetaboxes();
    }

    private static function renderMetaboxes()
    {
        self::renderDetailsMetabox();
        self::renderLogoMetabox();

        $namespace = get_class(self::$instance);

        add_action('add_meta_boxes', array($namespace, 'createDisclaimerMetabox'));
        add_action('add_meta_boxes', array($namespace, 'createUsersMetabox'));
        add_action('add_meta_boxes', array($namespace, 'createLegacyUsersMetabox'));
    }

    private static function getUsersFromClient($client_id)
    {
        if ((int)$client_id <= 0) {
            return array();
        }

        // Let's cache all users basic info so we don't have to query each one of them later.
        global $wpdb;
        $rowset = $wpdb->get_results(sprintf('
            SELECT `ID`, `display_name`, `user_login`, `user_email`
            FROM `%s`',
            $wpdb->prefix . 'users'
        ));

        // Create our users hash map.
        $users = array();
        foreach ($rowset as $row) {
            $users[(int)$row->ID] = array(
                'id'       => (int)$row->ID,
                'name'     => $row->display_name,
                'username' => $row->user_login,
                'email'    => $row->user_email
            );
        }
        unset($rowset);

        $clientUsersList = array();

        // Retrieve all client users.
        $meta = (array)get_post_meta($client_id, '_upstream_new_client_users');
        if (!empty($meta)) {
            foreach ($meta[0] as $clientUser) {
                if (!empty($clientUser) && is_array($clientUser) && isset($users[$clientUser['user_id']])) {
                    $user = $users[$clientUser['user_id']];

                    $user['assigned_at'] = $clientUser['assigned_at'];
                    $user['assigned_by'] = $users[$clientUser['assigned_by']]['name'];

                    array_push($clientUsersList, (object)$user);
                }
            }
        }

        return $clientUsersList;
    }

    private static function getUnassignedUsersFromClient($client_id)
    {
        // @todo
    }

    private static function renderAddNewUserModal()
    {
        ?>
        <div id="modal-add-new-user" style="display: none;">
            <div id="form-add-new-user">
                <div>
                    <h3><?php echo __('Credentials', 'upstream'); ?></h3>
                    <div class="up-form-group">
                        <label for="new-user-email"><?php echo __('Email', 'upstream') .' *'; ?></label>
                        <input type="email" name="email" id="new-user-email" required />
                    </div>
                    <div class="up-form-group">
                        <label for="new-user-username"><?php echo __('Username', 'upstream') .' *'; ?></label>
                        <input type="text" name="username" id="new-user-username" required />
                        <div class="up-help-block">
                            <ul>
                                <li><?php echo __('Must be between 3 and 60 characters long;', 'upstream'); ?></li>
                                <li><?php echo __('You may use <code>letters (a-z)</code>, <code>numbers (0-9)</code>, <code>-</code> and <code>_</code> symbols;', 'upstream'); ?></li>
                                <li><?php echo __('The first character must be a <code>letter</code>;', 'upstream'); ?></li>
                                <li><?php echo __('Everything will be lowercased.', 'upstream'); ?></li>
                            </ul>
                        </div>
                    </div>
                    <div class="up-form-group">
                        <label for="new-user-password"><?php echo __('Password', 'upstream') .' *'; ?></label>
                        <input type="password" name="password" id="new-user-password" required />
                        <div class="up-help-block">
                            <ul>
                                <li><?php echo __('Must be at least 6 characters long.', 'upstream'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="up-form-group label-default">
                        <label>
                            <input type="checkbox" name="notification" id="new-user-notification" value="1" checked />
                            <span><?php echo __('Send user info via email', 'upstream'); ?></span>
                        </label>
                    </div>
                    <div class="up-form-group">
                        <button type="submit" class="button button-primary" data-label="<?php echo __('Add New User', 'upstream'); ?>" data-loading-label="<?php echo __('Adding...', 'upstream'); ?>"><?php echo __('Add New User', 'upstream'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function renderAddExistentUserModal()
    {
        $client_id = get_the_id();
        $unassignedUsers = self::getUnassignedUsersFromClient($client_id);
        ?>
        <div id="modal-add-existent-user" style="display: none;">
            <div class="upstream-row">
                <p><?php echo sprintf(__('These are all the users assigned with the role <code>%s</code> and not related to this client yet.', 'upstream'), sprintf(__('%s Client User', 'upstream'), upstream_project_label())); ?></p>
            </div>
            <div class="upstream-row">
                <table id="table-add-existent-users" class="wp-list-table widefat fixed striped posts upstream-table">
                    <thead>
                        <tr>
                            <th class="text-center">
                                <input type="checkbox" />
                            </th>
                            <th><?php echo __('Name', 'upstream'); ?></th>
                            <th><?php echo __('Username', 'upstream'); ?></th>
                            <th><?php echo __('Email', 'upstream'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($unassignedUsers) > 0): ?>
                        <?php foreach ($unassignedUsers as $user): ?>
                            <tr data-id="<?php echo $user->id; ?>">
                                <td class="text-center">
                                    <input type="checkbox" value="1" />
                                </td>
                                <td><?php echo $user->name; ?></td>
                                <td><?php echo $user->username; ?></td>
                                <td><?php echo $user->email; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="4"><?php echo __('No users found.', 'upstream'); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="upstream-row submit"></div>
        </div>
        <?php
    }

    private static function renderMigrateUserModal()
    {
        $client_id = get_the_id();
        $unassignedUsers = self::getUnassignedUsersFromClient($client_id);
        ?>

        <div id="modal-migrate-user" style="display: none;">
            <div id="form-migrate-user">
                <div>
                    <h3><?php echo __('User Data', 'upstream'); ?></h3>
                    <div class="up-form-group">
                        <label for="migrate-user-email"><?php echo __('Email', 'upstream') . ' *'; ?></label>
                        <input type="email" name="email" id="migrate-user-email" required size="35" />
                    </div>
                    <div class="up-form-group">
                        <label for="migrate-user-password"><?php echo __('Password', 'upstream') . ' *'; ?></label>
                        <input type="password" name="password" id="migrate-user-password" required size="35" />
                        <div class="up-help-block">
                            <ul>
                                <li><?php echo __('Must be at least 6 characters long.', 'upstream'); ?></li>
                            </ul>
                        </div>
                    </div>
                    <div class="up-form-group">
                        <label for="migrate-user-fname"><?php echo __('First Name', 'upstream'); ?></label>
                        <input type="text" name="fname" id="migrate-user-fname" size="35" />
                    </div>
                    <div class="up-form-group">
                        <label for="migrate-user-lname"><?php echo __('Last Name', 'upstream'); ?></label>
                        <input type="text" name="lname" id="migrate-user-lname" size="35" />
                    </div>
                </div>
                <div>
                    <div class="up-form-group">
                        <button type="submit" class="button button-primary" data-label="<?php echo __('Migrate User', 'upstream'); ?>" data-loading-label="<?php echo __('Migrating...', 'upstream'); ?>"><?php echo __('Migrate User', 'upstream'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function renderUsersMetabox()
    {
        $client_id = get_the_id();

        $usersList = self::getUsersFromClient($client_id);
        ?>

        <?php // @todo: create js/css to make Thickbox responsive. ?>
        <div class="upstream-row">
            <a name="Add New User" href="#TB_inline?width=600&height=425&inlineId=modal-add-new-user" class="thickbox button"><?php echo __('Add New User', 'upstream'); ?></a>
            <a id="add-existent-user" name="Add Existent User" href="#TB_inline?width=600&height=300&inlineId=modal-add-existent-user" class="thickbox button"><?php echo __('Add Existent User', 'upstream'); ?></a>
        </div>
        <div class="upstream-row">
            <table id="table-users" class="wp-list-table widefat fixed striped posts upstream-table">
                <thead>
                    <tr>
                        <th><?php echo __('Name', 'upstream'); ?></th>
                        <th><?php echo __('Username', 'upstream'); ?></th>
                        <th><?php echo __('Email', 'upstream'); ?></th>
                        <th class="text-center"><?php echo __('Assigned at', 'upstream'); ?></th>
                        <th><?php echo __('Assigned by', 'upstream'); ?></th>
                        <th class="text-center"><?php echo __('Remove?', 'upstream'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($usersList) > 0):
                    $timezone = get_option('timezone_string');
                    $timezone = !empty($timezone) ? $timezone : 'UTC';
                    $instanceTimezone = new DateTimeZone($timezone);
                    $dateFormat = get_option('date_format') . ' ' . get_option('time_format');

                    foreach ($usersList as $user):
                    $assignedAt = new DateTime($user->assigned_at);
                    // Convert the date, which is in UTC, to the instance's timezone.
                    $assignedAt->setTimeZone($instanceTimezone);
                    ?>
                    <tr data-id="<?php echo $user->id; ?>">
                        <td><?php echo $user->name; ?></td>
                        <td><?php echo $user->username; ?></td>
                        <td><?php echo $user->email; ?></td>
                        <td class="text-center"><?php echo $assignedAt->format($dateFormat); ?></td>
                        <td><?php echo $user->assigned_by; ?></td>
                        <td class="text-center">
                            <a href="#" onclick="javascript:void(0);" class="up-u-color-red" data-remove-user>
                                <span class="dashicons dashicons-trash"></span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr data-empty>
                        <td colspan="7"><?php echo __("There's no users assigned yet.", 'upstream'); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p>
                <span class="dashicons dashicons-info"></span> <?php echo __('By removing a user it only means that he will no longer be associated with this client. He will not deleted.', 'upstream'); ?>
            </p>
        </div>

        <?php
        self::renderAddNewUserModal();
        self::renderAddExistentUserModal();
    }

    public static function createDisclaimerMetabox()
    {
        add_meta_box(
            self::$prefix . 'warnings',
            '<span class="dashicons dashicons-warning"></span>' . __("UpStream's Disclaimer", 'upstream'),
            array(get_class(self::$instance), 'renderDisclaimerMetabox'),
            self::$postType,
            'normal'
        );
    }

    public static function renderDisclaimerMetabox()
    {
        ?>
        <div class="upstream-row">
            <p><?php echo __("<code>UpStream Client Users</code> are now <code>WordPress Users</code> as well, meaning they will be able to log in using their own password instead of client's, and manage their very own profile.", 'upstream'); ?></p>

            <ul class="up-list-disc">
                <li><?php echo __('UpStream attempted to convert them automatically when the plugin was updated.', 'upstream'); ?></li>
                <li><?php echo __('Client Users that <strong>could not</strong> be automatically converted for some reason will be listed on the <code>Legacy Users</code> metabox on this page. They can be manually either converted/migrated or discarded.', 'upstream'); ?></li>
                <li><?php echo __("Client Users that were <strong>successfully</strong> converted <u>will have the same permissions they have before</u> and <u>their email address set as their new password</u> by default. It's highly advisable that they change their password after that.", 'upstream'); ?></li>
                <li><?php echo __('Client passwords are no longer used.', 'upstream'); ?></li>
            </ul>
        </div>
        <?php
    }

    public static function createUsersMetabox()
    {
        add_meta_box(
            self::$prefix . 'users',
            '<span class="dashicons dashicons-groups"></span>' . __("Users", 'upstream'),
            array(get_class(self::$instance), 'renderUsersMetabox'),
            self::$postType,
            'normal'
        );
    }

    public static function createLegacyUsersMetabox()
    {
        $client_id = upstream_post_id();

        $legacyUsersErrors = get_post_meta($client_id, '_upstream_client_legacy_users_errors');
        if (count($legacyUsersErrors) === 0) {
            return;
        }

        // @todo check if client has errors
        add_meta_box(
            self::$prefix . 'legacy_users',
            '<span class="dashicons dashicons-groups"></span>' . __("Legacy Users", 'upstream'),
            array(get_class(self::$instance), 'renderLegacyUsersMetabox'),
            self::$postType,
            'normal'
        );
    }

    public static function renderLegacyUsersMetabox()
    {
        $client_id = upstream_post_id();

        $legacyUsersErrors = get_post_meta($client_id, '_upstream_client_legacy_users_errors')[0];

        $legacyUsersMeta = get_post_meta($client_id, '_upstream_client_users')[0];
        $legacyUsers = array();
        foreach ($legacyUsersMeta as $a) {
            $legacyUsers[$a['id']] = $a;
        }
        unset($legacyUsersMeta);
        ?>
        <div class="upstream-row">
            <p><?php echo __('The users listed below are those old <code>UpStream Client Users</code> that could not be automatically converted/migrated to <code>WordPress Users</code> by UpStream for some reason. More details on the Disclaimer metabox.', 'upstream'); ?></p>
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
                        $errorMessage = __("This email address is already being used by another user.", 'upstream');
                        break;
                    case 'ERR_EMPTY_EMAIL':
                        $errorMessage = __("Email addresses cannot be empty.", 'upstream');
                        break;
                    default:
                        $errorMessage = $legacyUserError;
                        break;
                }
                ?>
                    <tr data-id="<?php echo $legacyUserId; ?>">
                        <td data-column="fname"><?php echo !empty($userFirstName) ? $userFirstName : '<i>empty</i>'; ?></td>
                        <td data-column="lname"><?php echo !empty($userLastName) ? $userLastName : '<i>empty</i>'; ?></td>
                        <td data-column="email"><?php echo !empty($userEmail) ? $userEmail : '<i>empty</i>'; ?></td>
                        <td data-column="phone"><?php echo !empty($userPhone) ? $userPhone : '<i>empty</i>'; ?></td>
                        <td class="text-center">
                            <a name="Migrating Client User" href="#TB_inline?width=350&height=400&inlineId=modal-migrate-user" class="thickbox" data-modal-identifier="user-migration">
                                <span class="dashicons dashicons-plus-alt"></span>
                            </a>
                        </td>
                        <td class="text-center">
                            <a href="#" onclick="javascript:void(0);" class="up-u-color-red" data-action="legacyUser:discard">
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

            <?php self::renderMigrateUserModal(); ?>
        </div>
        <?php
    }

    public static function renderDetailsMetabox()
    {
        $metabox = new_cmb2_box(array(
            'id'           => self::$prefix . 'details',
            'title'        => '<span class="dashicons dashicons-admin-generic"></span>' . __('Details', 'upstream'),
            'object_types' => array(self::$postType),
            'context'      => 'side',
            'priority'     => 'high'
        ));

        $phoneField = $metabox->add_field(array(
            'name' => __('Phone Number', 'upstream'),
            'id'   => self::$prefix . 'phone',
            'type' => 'text'
        ));

        $websiteField = $metabox->add_field(array(
            'name' => __('Website', 'upstream'),
            'id'   => self::$prefix . 'website',
            'type' => 'text_url'
        ));

        // @todo: may we should use tinymce?
        $addressField = $metabox->add_field(array(
            'name' => __('Address', 'upstream'),
            'id'   => self::$prefix . 'address',
            'type' => 'textarea_small'
        ));

        $metaboxGrid = new Cmb2Grid($metabox);
        $metaboxGridRow = $metaboxGrid->addRow(array($phoneField, $websiteField, $addressField));
    }

    public static function renderLogoMetabox()
    {
        $metabox = new_cmb2_box(array(
            'id'           => self::$prefix . 'logo',
            'title'        => '<span class="dashicons dashicons-format-image"></span>' . __("Logo", 'upstream'),
            'object_types' => array(self::$postType),
            'context'      => 'side',
            'priority'     => 'core'
        ));

        $logoField = $metabox->add_field(array(
            'id'   => self::$prefix . 'logo',
            'type' => 'file'
        ));


        $metaboxGrid = new Cmb2Grid($metabox);
        $metaboxGridRow = $metaboxGrid->addRow(array($logoField));
    }

    public static function addNewUser()
    {
        header('Content-Type: application/json');

        global $wpdb;

        $response = array(
            'success' => false,
            'data'    => null,
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || !isset($_POST['client'])) {
                throw new \Exception("@todo");
            }

            $clientId = (int)$_POST['client'];
            if ($clientId <= 0) {
                throw new \Exception("@todo");
            }

            $data = array(
                'username'     => strtolower(trim(@$_POST['username'])),
                'email'        => trim(@$_POST['email']),
                'password'     => @$_POST['password'],
                'first_name'   => trim(@$_POST['first_name']),
                'last_name'    => trim(@$_POST['last_name']),
                'notification' => isset($_POST['notification']) ? (bool)$_POST['notification'] : false // @todo: should be true?
            );

            // Validate `password` field.
            if (strlen($data['password']) < 6) {
                throw new \Exception("Password must be at least 6 characters long.");
            }

            // Validate `username` field.
            $userDataUsername = $data['username'];
            $userDataUsernameLength = strlen($userDataUsername);
            if ($userDataUsernameLength < 3 || $userDataUsernameLength > 60) {
                throw new \Exception("The username must be between 3 and 60 characters long.");
            } else if (!validate_username($data['username']) || !preg_match('/^[a-z]+[a-z0-9\-\_]+$/i', $userDataUsername)) {
                throw new \Exception("Invalid username.");
            } else {
                $usernameExists = (bool)$wpdb->get_var(sprintf('
                    SELECT COUNT(`ID`)
                    FROM `%s`
                    WHERE `user_login` = "%s"',
                    $wpdb->prefix . 'users',
                    $userDataUsername
                ));

                if ($usernameExists) {
                    throw new \Exception("This username is not available.");
                }
            }

            // Validate the `email` field.
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || !is_email($data['email'])) {
                throw new \Exception("Invalid email.");
            } else {
                $emailExists = (bool)$wpdb->get_var(sprintf('
                    SELECT COUNT(`ID`)
                    FROM `%s`
                    WHERE `user_email` = "%s"',
                    $wpdb->prefix . 'users',
                    $data['email']
                ));

                if ($emailExists) {
                    throw new \Exception("This email address is not available.");
                }
            }

            $userData = array(
                'user_login'    => $userDataUsername,
                'user_pass'     => $data['password'],
                'user_nicename' => $userDataUsername,
                'user_email'    => $data['email'],
                'display_name'  => $userDataUsername,
                'nickname'      => $userDataUsername,
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'role'          => 'upstream_client_user' // @todo : script to create the role when updating UpStream?
            );

            $userDataId = wp_insert_user($userData);
            if (is_wp_error($userDataId)) {
                throw new \Exception($userDataId->get_error_message());
            }

            if ($data['notification']) {
                // @todo
                //wp_new_user_notification($userDataId);
            }

            $currentUser = get_userdata(get_current_user_id());

            $nowTimestamp = time();

            $response['data'] = array(
                'id'          => $userDataId,
                'assigned_at' => upstream_convert_UTC_date_to_timezone($nowTimestamp),
                'assigned_by' => $currentUser->display_name,
                'name'        => empty($data['first_name'] . ' ' . $data['last_name']) ? $data['first_name'] . ' ' . $data['last_name'] : $data['username'],
                'username'    => $userDataUsername,
                'email'       => $data['email']
            );

            $clientUsersMetaKey = '_upstream_new_client_users';
            $clientUsersList = (array)get_post_meta($clientId, $clientUsersMetaKey, true);
            array_push($clientUsersList, array(
                'user_id'     => $userDataId,
                'assigned_by' => $currentUser->ID,
                'assigned_at' => date('Y-m-d H:i:s', $nowTimestamp)
            ));
            update_post_meta($clientId, $clientUsersMetaKey, $clientUsersList);

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    public static function removeUser()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || !isset($_POST['client'])) {
                throw new \Exception("@todo");
            }

            $clientId = (int)$_POST['client'];
            if ($clientId <= 0) {
                throw new \Exception("@todo");
            }

            $userId = (int)@$_POST['user'];
            if ($userId <= 0) {
                throw new \Exception("@todo");
            }

            $clientUsersMetaKey = '_upstream_new_client_users';
            $meta = (array)get_post_meta($clientId, $clientUsersMetaKey);
            if (!empty($meta)) {
                $newClientUsersList = array();
                foreach ($meta[0] as $clientUser) {
                    if (!empty($clientUser) && is_array($clientUser)) {
                        if ((int)$clientUser['user_id'] !== $userId) {
                            array_push($newClientUsersList, $clientUser);
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

    public static function fetchUnassignedUsers()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'data'    => array(),
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_GET) || !isset($_GET['client'])) {
                throw new \Exception("@todo");
            }

            $clientId = (int)$_GET['client'];
            if ($clientId <= 0) {
                throw new \Exception("@todo");
            }

            $clientUsers = self::getUsersFromClient($clientId);
            $excludeTheseIds = array(get_current_user_id());
            if (count($clientUsers) > 0) {
                foreach ($clientUsers as $clientUser) {
                    array_push($excludeTheseIds, $clientUser->id);
                }
            }

            $rowset = get_users(array(
                'exclude'  => $excludeTheseIds,
                'role__in' => array('upstream_client_user'),
                'orderby'  => 'ID'
            ));

            global $wp_roles;

            foreach ($rowset as $row) {
                $user = array(
                    'id'       => $row->ID,
                    'name'     => $row->display_name,
                    'username' => $row->user_login,
                    'email'    => $row->user_email
                );

                array_push($response['data'], $user);
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    public static function addExistentUsers()
    {
        header('Content-Type: application/json');

        $response = array(
            'success' => false,
            'data'    => array(),
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || !isset($_POST['client'])) {
                throw new \Exception("@todo");
            }

            $clientId = (int)$_POST['client'];
            if ($clientId <= 0) {
                throw new \Exception("@todo");
            }

            if (!isset($_POST['users']) && empty($_POST['users'])) {
                throw new \Exception("@todo");
            }

            $currentUser = get_userdata(get_current_user_id());
            $nowTimestamp = time();
            $now = date('Y-m-d H:i:s', $nowTimestamp);

            $clientUsersMetaKey = '_upstream_new_client_users';
            $clientUsersList = (array)get_post_meta($clientId, $clientUsersMetaKey, true);
            $clientNewUsersList = array();

            $usersIdsList = (array)$_POST['users'];
            foreach ($usersIdsList as $user_id) {
                $user_id = (int)$user_id;
                if ($user_id > 0) {
                    array_push($clientUsersList, array(
                        'user_id'     => $user_id,
                        'assigned_by' => $currentUser->ID,
                        'assigned_at' => $now
                    ));
                }
            }

            foreach ($clientUsersList as $clientUser) {
                $clientUser = (array)$clientUser;
                $clientUser['user_id'] = (int)$clientUser['user_id'];

                if (!isset($clientNewUsersList[$clientUser['user_id']])) {
                    $clientNewUsersList[$clientUser['user_id']] = $clientUser;
                }
            }
            update_post_meta($clientId, $clientUsersMetaKey, array_values($clientNewUsersList));

            global $wpdb;

            $rowset = (array)$wpdb->get_results(sprintf('
                SELECT `ID`, `display_name`, `user_login`, `user_email`
                FROM `%s`
                WHERE `ID` IN ("%s")',
                $wpdb->prefix . 'users',
                implode('", "', $usersIdsList)
            ));

            $assignedAt = upstream_convert_UTC_date_to_timezone($now);

            foreach ($rowset as $user) {
                array_push($response['data'], array(
                    'id'          => (int)$user->ID,
                    'name'        => $user->display_name,
                    'email'       => $user->user_email,
                    'username'    => $user->user_login,
                    'assigned_by' => $currentUser->display_name,
                    'assigned_at' => $assignedAt
                ));
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }

    public static function migrateLegacyUser()
    {
        header('Content-Type: application/json');

        global $wpdb;

        $response = array(
            'success' => false,
            'data'    => array(),
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || !isset($_POST['client'])) {
                throw new \Exception(__("Invalid UpStream Client ID.", 'upstream'));
            }

            $client_id = (int)$_POST['client'];

            $data = array(
                'id'       => isset($_POST['user_id']) ? $_POST['user_id'] : null,
                'email'    => isset($_POST['email']) ? $_POST['email'] : null,
                'password' => isset($_POST['password']) ? $_POST['password'] : null,
                'fname'    => isset($_POST['first_name']) ? $_POST['first_name'] : null,
                'lname'    => isset($_POST['last_name']) ? $_POST['last_name'] : null
            );

            $userData = ClientUsersMigration::insertNewClientUser($data, $client_id);
            $response['data'] = $userData;

            $legacy_user_id = $userData['legacy_id'];
            $user_id = $userData['id'];

            // Update the '_upstream_client_users' meta.
            $meta = (array)get_post_meta($client_id, '_upstream_client_users');
            if (!empty($meta)) {
                $meta = $meta[0];
                foreach ($meta as $legacyUserIndex => $legacyUser) {
                    if (isset($legacyUser['id']) && $legacyUser['id'] === $data['id']) {
                        unset($meta[$legacyUserIndex]);
                    }
                }

                update_post_meta($client_id, '_upstream_client_users', $meta);
            }

            // Update the '_upstream_client_legacy_users_errors' meta.
            $meta = (array)get_post_meta($client_id, '_upstream_client_legacy_users_errors');
            if (!empty($meta)) {
                $meta = $meta[0];
                foreach ($meta as $legacyUserId => $legacyUserError) {
                    if ($legacyUserId === $data['id']) {
                        unset($meta[$legacyUserId]);
                    }
                }

                update_post_meta($client_id, '_upstream_client_legacy_users_errors', $meta);
            }

            $rowset = $wpdb->get_results('
                SELECT `post_id`, `meta_key`, `meta_value`
                FROM `' . $wpdb->prefix . 'postmeta`
                WHERE `meta_key` LIKE "_upstream_project_%"
                ORDER BY `post_id` ASC'
            );

            if (count($rowset) > 0) {
                $convertUsersLegacyIdFromHaystack = function(&$haystack) use ($legacy_user_id, $user_id) {
                    foreach ($haystack as &$needle) {
                        if ($needle === $legacy_user_id) {
                            $needle = $user_id;
                        }
                    }
                };

                foreach ($rowset as $projectMeta) {
                    $project_id = (int)$projectMeta->post_id;

                    if ($projectMeta->meta_key === '_upstream_project_activity') {
                        $metaValue = (array)maybe_unserialize($projectMeta->meta_value);
                        foreach ($metaValue as $activityIndex => $activity) {
                            if ($activity['user_id'] === $legacy_user_id) {
                                $activity['user_id'] = $user_id;
                            }

                            if (isset($activity['fields'])) {
                                if (isset($activity['fields']['single'])) {
                                    foreach ($activity['fields']['single'] as $activitySingleIndentifier => $activitySingle) {
                                        if ($activitySingleIndentifier === '_upstream_project_client_users') {
                                            if (isset($activitySingle['add'])) {
                                                if (is_array($activitySingle['add'])) {
                                                    $convertUsersLegacyIdFromHaystack($activitySingle['add']);
                                                }
                                            }

                                            if (isset($activitySingle['from'])) {
                                                if (is_array($activitySingle['from'])) {
                                                    $convertUsersLegacyIdFromHaystack($activitySingle['from']);
                                                }
                                            }

                                            if (isset($activitySingle['to'])) {
                                                if (is_array($activitySingle['to'])) {
                                                    $convertUsersLegacyIdFromHaystack($activitySingle['to']);
                                                }
                                            }
                                        }

                                        $activity['fields']['single'][$activitySingleIndentifier] = $activitySingle;
                                    }
                                }

                                if (isset($activity['fields']['group'])) {
                                    foreach ($activity['fields']['group'] as $groupIdentifier => $groupData) {
                                        if (isset($groupData['add'])) {
                                            foreach ($groupData['add'] as $rowIndex => $row) {
                                                if (isset($row['created_by']) && $row['created_by'] === $legacy_user_id) {
                                                    $row['created_by'] = $user_id;
                                                    $groupData['add'][$rowIndex] = $row;
                                                }
                                            }
                                        }

                                        if (isset($groupData['remove'])) {
                                            foreach ($groupData['remove'] as $rowIndex => $row) {
                                                if (isset($row['created_by']) && $row['created_by'] === $legacy_user_id) {
                                                    $row['created_by'] = $user_id;
                                                    $groupData['remove'][$rowIndex] = $row;
                                                }
                                            }
                                        }

                                        $activity['fields']['group'][$groupIdentifier] = $groupData;
                                    }
                                }
                            }

                            $metaValue[$activityIndex] = $activity;
                        }

                        update_post_meta($project_id, $projectMeta->meta_key, $metaValue);
                    } else if ($projectMeta->meta_key === '_upstream_project_discussion') {
                        $metaValue = (array)maybe_unserialize($projectMeta->meta_value);
                        foreach ($metaValue as $commentIndex => $comment) {
                            if ($comment['created_by'] === $legacy_user_id) {
                                $comment['created_by'] = $user_id;
                                $metaValue[$commentIndex] = $comment;
                            }
                        }

                        update_post_meta($project_id, $projectMeta->meta_key, $metaValue);
                    } else if (preg_match('/(milestones|tasks|bugs|files)$/i', $projectMeta->meta_key)) {
                        $metaValue = (array)maybe_unserialize($projectMeta->meta_value);
                        foreach ($metaValue as $rowIndex => $row) {
                            if (isset($row['created_by']) && $row['created_by'] === $legacy_user_id) {
                                $row['created_by'] = $user_id;

                                $metaValue[$rowIndex] = $row;
                            }
                        }

                        update_post_meta($project_id, $projectMeta->meta_key, $metaValue);
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

    public static function discardLegacyUser()
    {
        header('Content-Type: application/json');

        global $wpdb;

        $response = array(
            'success' => false,
            'err'     => null
        );

        try {
            if (!upstream_admin_permissions('edit_clients')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            if (empty($_POST) || !isset($_POST['client'])) {
                throw new \Exception(__("Invalid UpStream Client ID.", 'upstream'));
            }

            $client_id = (int)$_POST['client'];
            $user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';

            if (empty($user_id)) {
                throw new \Exception(__("Invalid UpStream Client ID.", 'upstream'));
            }

            // Update the '_upstream_client_legacy_users_errors' meta.
            $meta = (array)get_post_meta($client_id, '_upstream_client_legacy_users_errors');
            if (!empty($meta)) {
                $meta = $meta[0];
                foreach ($meta as $legacyUserId => $legacyUserError) {
                    if ($legacyUserId === $user_id) {
                        unset($meta[$legacyUserId]);
                    }
                }

                update_post_meta($client_id, '_upstream_client_legacy_users_errors', $meta);
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['err'] = $e->getMessage();
        }

        echo wp_json_encode($response);

        wp_die();
    }
}
