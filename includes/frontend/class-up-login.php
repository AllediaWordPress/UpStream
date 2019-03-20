<?php
if ( ! defined('ABSPATH')) {
    exit;
}

final class UpStream_Login
{
    /**
     * Represent the feedback message for the current action.
     *
     * @since   1.0.0
     * @access  private
     *
     * @var     string $feedbackMessage
     */
    private $feedbackMessage = "";

    /**
     * Class constructor.
     *
     * @since   1.0.0
     */
    public function __construct()
    {
        $this->performUserLoginAction();
    }

    /**
     * Handles the flow of the login/logout process.
     *
     * @since   1.0.0
     * @access  private
     */
    private function performUserLoginAction()
    {
        $action              = isset($_GET['action']) ? $_GET['action'] : null;
        $userIsTryingToLogin = isset($_POST['login']);

        if ($action === "logout" && ! $userIsTryingToLogin) {
            UpStream_Login::doDestroySession();
        } elseif ($userIsTryingToLogin) {
            $data = $this->validateLogInPostData();
            if (is_array($data)) {
                $this->authenticateData($data);
            }
        }
    }

    /**
     * Destroy user's session data.
     *
     * @since   1.9.0
     * @static
     */
    public static function doDestroySession()
    {
        wp_logout();

        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['upstream'])) {
            unset($_SESSION['upstream']);
        }

        if ( ! empty($_GET) && isset($_GET['action']) && $_GET['action'] === 'logout') {
            unset($_GET['action']);
        }
    }

    /**
     * Validate the login form data by checking if a username and a password were provided.
     * If data is valid, an array will be returned. The return will be FALSE otherwise.
     *
     * @since   1.9.0
     * @access  private
     *
     * @return  array | bool
     */
    private function validateLogInPostData()
    {
        if ( ! isset($_POST['upstream_login_nonce']) || ! wp_verify_nonce(
                $_POST['upstream_login_nonce'],
                'upstream-login-nonce'
            )) {
            return false;
        }

        $postData = [
            'username' => isset($_POST['user_email']) ? sanitize_text_field(trim($_POST['user_email'])) : "",
            'password' => isset($_POST['user_password']) ? $_POST['user_password'] : "",
        ];

        if (empty($postData['username'])) {
            $this->feedbackMessage = __("Email address field cannot be empty.", 'upstream');
        } elseif (strlen($postData['username']) < 3 || ! is_email($postData['username'])) {
            $this->feedbackMessage = __("Invalid email address and/or password.", 'upstream');
        } else {
            if (empty($postData['password'])) {
                $this->feedbackMessage = __("Password field cannot be empty.", 'upstream');
            } elseif (strlen($postData['password']) < 5) {
                $this->feedbackMessage = __("Invalid email address and/or password.", 'upstream');
            } else {
                return $postData;
            }
        }

        return false;
    }

    /**
     * Attempt to authenticate a user against the open project given current email address and password.
     *
     * @since   1.9.0
     * @access  private
     *
     * @param   array $data An associative array containing an email (already sanitized) and a raw password.
     *
     * @return  bool
     */
    private function authenticateData($data)
    {
        try {
            if ( ! isset($data['username']) || ! isset($data['password'])) {
                throw new \Exception(__("Invalid email address and/or password.", 'upstream'));
            }

            // Check if there's a user using that email.
            $user = get_user_by('email', $data['username']);
            if (empty($user)) {
                throw new \Exception(__("Invalid email address and/or password.", 'upstream'));
            }

            $userRoles    = (array)$user->roles;
            $projectRoles = array_merge(['administrator',], upstream_get_project_roles());

            // Check if this user has a valid UpStream Role to log in.
            if (count(array_intersect(
                    $userRoles,
                    $projectRoles
                )) === 0) {
                throw new \Exception(__("You don't have enough permissions to log in here.", 'upstream'));
            }

            $project_id  = (int)upstream_post_id();
            $canContinue = false;

            // Make sure he can be authenticated if he's an admin/manager.
            if (count(array_intersect($userRoles, ['administrator', 'upstream_manager'])) > 0) {
                $canContinue = true;
            } elseif (is_clients_disabled()) {
                throw new \Exception(__("Invalid email address and/or password.", 'upstream'));
            } else {
                // Check if he, as an UpStream User, is a current member of this project.
                if (in_array('upstream_user', $userRoles)) {
                    $metaKeyName = '_upstream_project_members';
                } else {
                    // Check if he, as an UpStream Client User, is allowed to log in in this project.
                    $metaKeyName = '_upstream_project_client_users';
                }

                $meta = (array)get_post_meta($project_id, $metaKeyName);
                if (count($meta) > 0) {
                    $canContinue = in_array((string)$user->ID, $meta[0]);
                }
            }

            if ( ! $canContinue) {
                throw new \Exception(__("Sorry, you are not allowed to access this project.", 'upstream'));
            }

            $user = wp_signon([
                'user_login'    => $data['username'],
                'user_password' => $data['password'],
                'remember'      => false,
            ]);

            if (is_wp_error($user)) {
                throw new \Exception(__("Invalid email address and/or password.", 'upstream'));
            }

            // Retrieve the project's client id.
            $client_id = (array)get_post_meta($project_id, '_upstream_project_client');
            if (count($client_id) > 0) {
                $client_id = (int)$client_id[0];
            } else {
                $client_id = 0;
            }

            $_SESSION['upstream'] = [
                'project_id' => $project_id,
                'client_id'  => $client_id,
                'user_id'    => $user->ID,
            ];

            $projectPermalink = get_the_permalink($project_id);
            wp_redirect(esc_url($projectPermalink));

            return true;
        } catch (\Exception $e) {
            $this->feedbackMessage = $e->getMessage();

            return false;
        }
    }

    /**
     * Return the current status of the user's login.
     *
     * @since   1.0.0
     * @static
     *
     * @return  bool
     */
    public static function userIsLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) {
            return false;
        }

        $userIsLoggedIn = (
            isset($_SESSION['upstream']) &&
            ! empty($_SESSION['upstream']['client_id']) &&
            ! empty($_SESSION['upstream']['user_id'])
        );

        return $userIsLoggedIn;
    }

    /**
     * Check if there's a feedback message for the current action.
     *
     * @since   1.9.0
     *
     * @return  bool
     */
    public function hasFeedbackMessage()
    {
        $hasFeedbackMessage = ! empty($this->feedbackMessage);

        return $hasFeedbackMessage;
    }

    /**
     * Retrieve the feedback message for the current action.
     *
     * @since   1.9.0
     *
     * @return  string
     */
    public function getFeedbackMessage()
    {
        $feedbackMessage = (string)$this->feedbackMessage;

        $this->feedbackMessage = "";

        return $feedbackMessage;
    }

    /**
     * Logs the user out.
     *
     * @since   1.9.0
     * @access  private
     */
    private function doLogOut()
    {
        UpStream_Login::doDestroySession();

        $this->feedbackMessage = __("You were just logged out.", 'upstream');
    }
}
