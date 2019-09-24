<?php

namespace UpStream;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Traits\Singleton;

/**
 * This class will act as a controller handling incoming requests regarding comments on UpStream items.
 *
 * @since   1.13.0
 */
class Comments
{
    use Singleton;

    /**
     * The current full namespace.
     *
     * @since   1.13.0
     * @access  private
     * @static
     *
     * @var     string $namespace
     */
    private static $namespace;

    /**
     * Class constructor.
     *
     * @since   1.13.0
     */
    public function __construct()
    {
        self::$namespace = get_class(
            empty(self::$instance)
                ? $this
                : self::$instance
        );

        $this->attachHooks();

        self::removeCommentType();
    }

    /**
     * Attach all relevant actions to handle comments.
     *
     * @since   1.13.0
     * @access  private
     */
    private function attachHooks()
    {
        add_action('wp_ajax_upstream:project.add_comment', [self::$namespace, 'storeComment']);
        add_action('wp_ajax_upstream:project.add_comment_reply', [$this, 'storeCommentReply']);
        add_action('wp_ajax_upstream:project.trash_comment', [self::$namespace, 'trashComment']);
        add_action('wp_ajax_upstream:project.unapprove_comment', [self::$namespace, 'unapproveComment']);
        add_action('wp_ajax_upstream:project.approve_comment', [self::$namespace, 'approveComment']);
        add_action('wp_ajax_upstream:project.fetch_comments', [self::$namespace, 'fetchComments']);

        add_filter('comment_notification_subject', [self::$namespace, 'defineNotificationHeader'], 10, 2);
        add_filter('comment_notification_recipients', [self::$namespace, 'defineNotificationRecipients'], 10, 2);
        add_filter('comment_notification_text', [self::$namespace, 'addItemTitleToNotification'], 10, 2);

        add_filter('upstream_allowed_tags_in_comments', [self::$namespace, 'filter_allowed_tags']);
        add_filter(
            'comment_notification_headers',
            [self::$namespace, 'filter_comment_notification_headers'],
            10,
            2
        );
        add_filter('comment_notification_text', [self::$namespace, 'filter_comment_notification_text'], 10, 2);
    }

    /**
     * Empties the comment_type="comment" column from UpStream comments.
     *
     * @since   1.16.3
     * @static
     */
    public static function removeCommentType()
    {
        $didRemoveCommentsType = (bool)get_option('upstream:remove_comments_type');
        if ( ! $didRemoveCommentsType) {
            global $wpdb;

            $wpdb->query(sprintf(
                'UPDATE `%s` AS `comment`
                   LEFT JOIN `%s` AS `post`
                     ON `post`.`ID` = `comment`.`comment_post_ID`
                 SET `comment_type` = ""
                 WHERE `comment_type` = "comment"
                   AND `post_type` = "project"',
                $wpdb->prefix . 'comments',
                $wpdb->prefix . 'posts'
            ));

            update_option('upstream:remove_comments_type', 1);
        }
    }

    /**
     * @param array $allowed_tags
     *
     * @return array
     */
    public static function filter_allowed_tags($allowed_tags)
    {
        global $allowedtags;

        // Add default allowed tags.
        $allowed_tags = array_merge($allowed_tags, $allowedtags);

        // Add basic tags.
        if ( ! array_key_exists('p', $allowed_tags)) {
            $allowed_tags['p'] = [
                'class' => true,
                'id'    => true,
            ];
        }

        if ( ! array_key_exists('br', $allowed_tags)) {
            $allowed_tags['br'] = [];
        }

        if ( ! array_key_exists('strong', $allowed_tags)) {
            $allowed_tags['strong'] = [
                'class' => true,
                'id'    => true,
            ];
        }

        if ( ! array_key_exists('em', $allowed_tags)) {
            $allowed_tags['em'] = [
                'class' => true,
                'id'    => true,
            ];
        }

        if ( ! array_key_exists('span', $allowed_tags)) {
            $allowed_tags['span'] = [
                'class' => true,
                'id'    => true,
                'style' => true,
            ];
        }

        if ( ! array_key_exists('del', $allowed_tags)) {
            $allowed_tags['del'] = [
                'class' => true,
                'id'    => true,
            ];
        }

        if ( ! array_key_exists('ul', $allowed_tags)) {
            $allowed_tags['ul'] = [
                'class' => true,
                'id'    => true,
            ];
        }

        if ( ! array_key_exists('ol', $allowed_tags)) {
            $allowed_tags['ol'] = [
                'class' => true,
                'id'    => true,
            ];
        }

        if ( ! array_key_exists('li', $allowed_tags)) {
            $allowed_tags['li'] = [
                'class' => true,
                'id'    => true,
            ];
        }

        if ( ! array_key_exists('a', $allowed_tags)) {
            $allowed_tags['a'] = [
                'class'   => true,
                'id'      => true,
                'href'    => true,
                'charset' => true,
                'name'    => true,
                'rel'     => true,
                'target'  => true,
                'type'    => true,
            ];
        } else {
            $allowed_tags['a']['class']   = true;
            $allowed_tags['a']['id']      = true;
            $allowed_tags['a']['href']    = true;
            $allowed_tags['a']['charset'] = true;
            $allowed_tags['a']['name']    = true;
            $allowed_tags['a']['rel']     = true;
            $allowed_tags['a']['target']  = true;
            $allowed_tags['a']['type']    = true;
        }

        // If the current can't post images, we return current supported tags.
        if ( ! current_user_can('upstream_comment_images')) {
            return $allowed_tags;
        }

        // The user can post images, so let's allow the img tag.
        if ( ! is_array($allowed_tags)) {
            $allowed_tags = [];
        }

        $allowed_tags['img'] = [
            'class'  => true,
            'src'    => true,
            'alt'    => true,
            'width'  => true,
            'height' => true,
        ];

        return $allowed_tags;
    }

    /**
     * AJAX endpoint that stores a new comment.
     *
     * @since   1.13.0
     * @static
     */
    public static function storeComment()
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error'   => null,
        ];

        try {
            // Check if the request payload is potentially invalid.
            if (
                ! defined('DOING_AJAX')
                || ! DOING_AJAX
                || empty($_POST)
                || ! isset($_POST['nonce'])
                || ! isset($_POST['project_id'])
                || ! isset($_POST['item_type'])
                || ! self::isItemTypeValid($_POST['item_type'])
                || ! isset($_POST['content'])
            ) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Prepare data to verify nonce.
            $commentTargetItemType = strtolower($_POST['item_type']);
            if ($commentTargetItemType !== 'project') {
                if (
                    ! isset($_POST['item_id'])
                    || empty($_POST['item_id'])
                ) {
                    throw new \Exception(__("Invalid item.", 'upstream'));
                }

                $item_id = $_POST['item_id'];

                $nonceIdentifier = 'upstream:project.' . $commentTargetItemType . 's.add_comment';
            } else {
                $nonceIdentifier = 'upstream:project.add_comment';
            }

            // Verify nonce.
            if ( ! check_ajax_referer($nonceIdentifier, 'nonce', false)) {
                throw new \Exception(__("Invalid nonce.", 'upstream'));
            }

            // Check if the user has enough permissions to insert a new comment.
            if ( ! upstream_admin_permissions('publish_project_discussion')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            // Check if the project exists.
            $project_id = (int)$_POST['project_id'];
            if ($project_id <= 0) {
                throw new \Exception(__("Invalid Project.", 'upstream'));
            }

            // Check if commenting is disabled on the given project.
            if (upstream_are_comments_disabled($project_id)) {
                throw new \Exception(__("Commenting is disabled on this project.", 'upstream'));
            }

            $user_id = get_current_user_id();

            $comment_content = stripslashes($_POST['content']);

            $item_title = isset($_POST['item_title']) ? sanitize_text_field($_POST['item_title']) : '';

            $comment = new Comment($comment_content, $project_id, $user_id);

            $comment->created_by->ip    = preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);
            $comment->created_by->agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

            $comment->save();

            update_comment_meta($comment->id, 'type', $commentTargetItemType);

            if ($commentTargetItemType !== "project") {
                update_comment_meta($comment->id, 'id', $item_id);
                // We store the item title here because of the project's data structure.
                // It is faster to retrieve from metadata then seek item by item from a project.
                update_comment_meta($comment->id, 'title', $item_title);
            }

            wp_new_comment_notify_moderator($comment->id);
            wp_notify_postauthor($comment->id);

            $useAdminLayout = ! isset($_POST['teeny']) ? true : (bool)$_POST['teeny'] === false;

            $response['comment_html'] = stripslashes($comment->render(true, $useAdminLayout));

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }


    /**
     * Check if the item type is valid.
     *
     * @since   1.13.0
     * @static
     *
     * @param   string $itemType Value to be validated.
     *
     * @return  bool
     */
    public static function isItemTypeValid($itemType)
    {
        $itemTypes = ['project', 'milestone', 'task', 'bug', 'file'];

        return in_array($itemType, $itemTypes);
    }

    /**
     * AJAX endpoint that adds a new comment reply.
     *
     * @since   1.13.0
     * @static
     */
    public static function storeCommentReply()
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error'   => null,
        ];

        try {
            // Check if the request payload is potentially invalid.
            if (
                ! defined('DOING_AJAX')
                || ! DOING_AJAX
                || empty($_POST)
                || ! isset($_POST['nonce'])
                || ! isset($_POST['project_id'])
                || ! isset($_POST['item_type'])
                || ! self::isItemTypeValid($_POST['item_type'])
                || ! isset($_POST['content'])
                || ! isset($_POST['parent_id'])
                || ! is_numeric($_POST['parent_id'])
                || ! check_ajax_referer('upstream:project.add_comment_reply:' . $_POST['parent_id'], 'nonce', false)
            ) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            $commentTargetItemType = strtolower($_POST['item_type']);
            if ($commentTargetItemType !== 'project') {
                if (
                    ! isset($_POST['item_id'])
                    || empty($_POST['item_id'])
                ) {
                    throw new \Exception(__("Invalid request.", 'upstream'));
                }
            }

            // Check if the user has enough permissions to insert a new comment.
            if ( ! upstream_admin_permissions('publish_project_discussion')) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            // Check if the project exists.
            $project_id = (int)$_POST['project_id'];
            if ($project_id <= 0) {
                throw new \Exception(__("Invalid Project.", 'upstream'));
            }

            // Check if commenting is disabled on the given project.
            if (upstream_are_comments_disabled($project_id)) {
                throw new \Exception(__("Commenting is disabled on this project.", 'upstream'));
            }

            $user_id = get_current_user_id();

            $comment                    = new Comment(stripslashes($_POST['content']), $project_id, $user_id);
            $comment->parent_id         = (int)$_POST['parent_id'];
            $comment->created_by->ip    = preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);
            $comment->created_by->agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

            $comment->save();

            update_comment_meta($comment->id, 'type', $commentTargetItemType);

            if ($commentTargetItemType !== "project") {
                update_comment_meta($comment->id, 'id', $_POST['item_id']);
            }

            $useAdminLayout = ! isset($_POST['teeny']) ? true : (bool)$_POST['teeny'] === false;

            $parent = get_comment($comment->parent_id);

            $commentsCache = [
                $parent->comment_ID => json_decode(json_encode([
                    'created_by' => [
                        'name' => $parent->comment_author,
                    ],
                ])),
            ];

            $response['comment_html'] = stripslashes($comment->render(true, $useAdminLayout, $commentsCache));

            wp_new_comment_notify_moderator($comment->id);
            wp_notify_postauthor($comment->id);

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * AJAX endpoint that trashes a comment.
     *
     * @since   1.13.0
     * @static
     */
    public static function trashComment()
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error'   => null,
        ];

        try {
            // Check if the request payload is potentially invalid.
            if (
                ! defined('DOING_AJAX')
                || ! DOING_AJAX
                || empty($_POST)
                || ! isset($_POST['nonce'])
                || ! isset($_POST['project_id'])
                || ! isset($_POST['comment_id'])
                || ! check_ajax_referer('upstream:project.trash_comment:' . $_POST['comment_id'], 'nonce', false)
            ) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Check if the project exists.
            $project_id = (int)$_POST['project_id'];
            if ($project_id <= 0) {
                throw new \Exception(__("Invalid Project.", 'upstream'));
            }

            // Check if the Discussion/Comments section is disabled for the current project.
            if (upstream_are_comments_disabled($project_id)) {
                throw new \Exception(__("Comments are disabled for this project.", 'upstream'));
            }

            // Check if the parent comment exists.
            $comment_id = (int)$_POST['comment_id'];
            $comment    = get_comment($comment_id);

            if (empty($comment)
                // Check if the comment belongs to that project.
                || (
                    isset($comment->comment_post_ID)
                    && (int)$comment->comment_post_ID !== $project_id
                )
            ) {
                throw new \Exception(_x('Comment not found.', 'Removing a comment in projects', 'upstream'));
            }

            $user_id = (int)get_current_user_id();

            if ( ! upstream_admin_permissions('delete_project_discussion')
                 && ! current_user_can('moderate_comments')
                 && (int)$comment->user_id !== $user_id
            ) {
                throw new \Exception(__("You're not allowed to do this.", 'upstream'));
            }

            $success = wp_trash_comment($comment);
            if ( ! $success) {
                throw new \Exception(__("It wasn't possible to delete this comment.", 'upstream'));
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * AJAX endpoint that unapproves a comment.
     *
     * @since   1.13.0
     * @static
     */
    public static function unapproveComment()
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error'   => null,
        ];

        try {
            $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
            $comment    = self::toggleCommentApprovalStatus($comment_id, false);

            $comments = [];
            if ($comment->parent_id > 0) {
                $parentComment = get_comment($comment->parent_id);
                if (is_numeric($parentComment->comment_approved)) {
                    if ((bool)$parentComment->comment_approved) {
                        $comments = [
                            $comment->parent_id => json_decode(json_encode([
                                'created_by' => [
                                    'name' => $parentComment->comment_author,
                                ],
                            ])),
                        ];
                    } else {
                        $user                     = wp_get_current_user();
                        $userHasAdminCapabilities = isUserEitherManagerOrAdmin($user);
                        $userCanModerateComments  = ! $userHasAdminCapabilities ? user_can(
                            $user,
                            'moderate_comments'
                        ) : true;

                        if ($userCanModerateComments) {
                            $comments = [
                                $comment->parent_id => json_decode(json_encode([
                                    'created_by' => [
                                        'name' => $parentComment->comment_author,
                                    ],
                                ])),
                            ];
                        }
                    }
                }
                unset($parentComment);
            }

            $useAdminLayout = ! isset($_POST['teeny']) ? true : (bool)$_POST['teeny'] === false;

            $response['comment_html'] = $comment->render(true, $useAdminLayout, $comments);

            wp_new_comment_notify_moderator($comment->id);

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * Either approves/unapproves a given comment.
     * This method is called by the correspondent AJAX endpoints.
     *
     * @since   1.13.0
     * @access  private
     * @static
     *
     * @throws  \Exception when something went wrong or failed on validations.
     *
     * @param   int     $comment_id        Comment ID being edited.
     * @param   bool    $newApprovalStatus Either the comment will be approved or not.
     *
     * @param   Comment $comment
     */
    private static function toggleCommentApprovalStatus($comment_id, $isApproved)
    {
        // Check if the request payload is potentially invalid.
        if (
            ! defined('DOING_AJAX')
            || ! DOING_AJAX
            || empty($_POST)
            || ! isset($_POST['nonce'])
            || ! isset($_POST['project_id'])
            || ! isset($_POST['comment_id'])
            || ! check_ajax_referer(
                'upstream:project.' . ($isApproved ? 'approve_comment' : 'unapprove_comment') . ':' . $_POST['comment_id'],
                'nonce',
                false
            )
        ) {
            throw new \Exception(__('Invalid request.', 'upstream'));
        }

        // Check if the user has enough permissions to do this.
        if ( ! current_user_can('moderate_comments')) {
            throw new \Exception(__("You're not allowed to do this.", 'upstream'));
        }

        // Check if the project potentially exists.
        $project_id = (int)$_POST['project_id'];
        if ($project_id <= 0) {
            throw new \Exception(sprintf(__('Invalid "%s" parameter.', 'upstream'), 'project_id'));
        }

        // Check if the Discussion/Comments section is disabled for the current project.
        if (upstream_are_comments_disabled($project_id)) {
            throw new \Exception(__('Comments are disabled for this project.', 'upstream'));
        }

        $comment = Comment::load($_POST['comment_id']);
        if ( ! ($comment instanceof Comment)) {
            throw new \Exception(__('Comment not found.', 'upstream'));
        }

        $success = (bool)$isApproved ? $comment->approve() : $comment->unapprove();
        if ( ! $success) {
            throw new \Exception(__('Unable to save the data into database.', 'upstream'));
        }

        return $comment;
    }

    /**
     * AJAX endpoint that approves a comment.
     *
     * @since   1.13.0
     * @static
     */
    public static function approveComment()
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error'   => null,
        ];

        try {
            $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
            $comment    = self::toggleCommentApprovalStatus($comment_id, true);

            $comments = [];
            if ($comment->parent_id > 0) {
                $parentComment = get_comment($comment->parent_id);
                if (is_numeric($parentComment->comment_approved)) {
                    if ((bool)$parentComment->comment_approved) {
                        $comments = [
                            $comment->parent_id => json_decode(json_encode([
                                'created_by' => [
                                    'name' => $parentComment->comment_author,
                                ],
                            ])),
                        ];
                    } else {
                        $user                     = wp_get_current_user();
                        $userHasAdminCapabilities = isUserEitherManagerOrAdmin($user);
                        $userCanModerateComments  = ! $userHasAdminCapabilities ? user_can(
                            $user,
                            'moderate_comments'
                        ) : true;

                        if ($userCanModerateComments) {
                            $comments = [
                                $comment->parent_id => json_decode(json_encode([
                                    'created_by' => [
                                        'name' => $parentComment->comment_author,
                                    ],
                                ])),
                            ];
                        }
                    }
                }
                unset($parentComment);
            }

            $useAdminLayout = ! isset($_POST['teeny']) ? true : (bool)$_POST['teeny'] === false;

            $response['comment_html'] = $comment->render(true, $useAdminLayout, $comments);

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * AJAX endpoint that fetches all comments from a given item/project.
     *
     * @since   1.13.0
     * @static
     */
    public static function fetchComments()
    {
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'data'    => [],
            'error'   => null,
        ];

        try {
            // Check if the request payload is potentially invalid.
            if (
                ! defined('DOING_AJAX')
                || ! DOING_AJAX
                || empty($_GET)
                || ! isset($_GET['nonce'])
                || ! isset($_GET['project_id'])
                || ! isset($_GET['item_type'])
                || ! self::isItemTypeValid($_GET['item_type'])
            ) {
                throw new \Exception(__("Invalid request.", 'upstream'));
            }

            // Check if the project potentially exists.
            $project_id = (int)$_GET['project_id'];
            if ($project_id <= 0) {
                throw new \Exception(__("Invalid Project.", 'upstream'));
            }

            // Prepare data to verify nonce.
            $commentTargetItemType = strtolower($_GET['item_type']);
            $item_id               = nulll;
            if ($commentTargetItemType !== 'project') {
                if (
                    ! isset($_GET['item_id'])
                    || empty($_GET['item_id'])
                ) {
                    throw new \Exception(__("Invalid request.", 'upstream'));
                }

                $item_id = $_GET['item_id'];

                $nonceIdentifier = 'upstream:project.' . $commentTargetItemType . 's.fetch_comments';
            } else {
                $nonceIdentifier = 'upstream:project.fetch_comments';
            }

            // Verify nonce.
            if ( ! check_ajax_referer($nonceIdentifier, 'nonce', false)) {
                throw new \Exception(__("Invalid nonce.", 'upstream'));
            }

            // Check if commenting is disabled on the given project.
            if (upstream_are_comments_disabled($project_id)) {
                throw new \Exception(__("Commenting is disabled on this project.", 'upstream'));
            }

            $useAdminLayout = ! isset($_GET['teeny']) ? true : (bool)$_GET['teeny'] === false;

            $commentsCache = static::getComments($project_id, $commentTargetItemType, $item_id);

            foreach ($commentsCache as $comment) {
                if ($comment->parent_id === 0) {
                    ob_start();
                    if ($useAdminLayout) {
                        upstream_admin_display_message_item($comment, []);
                    } else {
                        upstream_display_message_item($comment, []);
                    }

                    $response['data'][] = trim(ob_get_contents());
                    ob_end_clean();
                }
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        wp_send_json($response);
    }

    /**
     * @param int    $projectId
     * @param string $itemType
     * @param int    $itemId
     *
     * @return array
     */
    public static function getComments($projectId, $itemType, $itemId = null)
    {
        $commentsCache = [];

        $usersCache  = [];
        $usersRowset = get_users([
            'fields' => [
                'ID',
                'display_name',
            ],
        ]);
        foreach ($usersRowset as $userRow) {
            $userRow->ID *= 1;

            $usersCache[$userRow->ID] = (object)[
                'id'     => $userRow->ID,
                'name'   => $userRow->display_name,
                'avatar' => getUserAvatarURL($userRow->ID),
            ];
        }
        unset($userRow, $usersRowset);

        $dateFormat        = get_option('date_format');
        $timeFormat        = get_option('time_format');
        $theDateTimeFormat = $dateFormat . ' ' . $timeFormat;
        $currentTimestamp  = time();

        $user                     = wp_get_current_user();
        $userHasAdminCapabilities = isUserEitherManagerOrAdmin($user);
        $userCanReply             = ! $userHasAdminCapabilities ? user_can(
            $user,
            'publish_project_discussion'
        ) : true;
        $userCanModerate          = ! $userHasAdminCapabilities ? user_can($user, 'moderate_comments') : true;
        $userCanDelete            = ! $userHasAdminCapabilities ? $userCanModerate || user_can(
                $user,
                'delete_project_discussion'
            ) : true;

        $commentsStatuses = ['approve'];
        if ($userHasAdminCapabilities || $userCanModerate) {
            $commentsStatuses[] = 'hold';
        }

        $itemsRowset = (array)get_post_meta(
            $projectId,
            '_upstream_project_' . $itemType . 's',
            true
        );

        if (count($itemsRowset) > 0) {
            foreach ($itemsRowset as $row) {
                if (empty($row)) {
                    continue;
                }

                if ( ! empty($itemId)) {
                    if ($itemId != $row['id']) {
                        continue;
                    }
                }

                $comments = (array)get_comments([
                    'post_id'    => $projectId,
                    'status'     => $commentsStatuses,
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key'   => 'type',
                            'value' => $itemType,
                        ],
                        [
                            'key'   => 'id',
                            'value' => $row['id'],
                        ],
                    ],
                ]);

                if (count($comments) > 0) {
                    foreach ($comments as $comment) {
                        $author = $usersCache[(int)$comment->user_id];

                        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $comment->comment_date_gmt);

                        $commentData = json_decode(json_encode([
                            'id'             => (int)$comment->comment_ID,
                            'parent_id'      => (int)$comment->comment_parent,
                            'content'        => $comment->comment_content,
                            'state'          => $comment->comment_approved,
                            'created_by'     => $author,
                            'created_at'     => [
                                'localized' => "",
                                'humanized' => sprintf(
                                    _x('%s ago', '%s = human-readable time difference', 'upstream'),
                                    human_time_diff($date->getTimestamp(), $currentTimestamp)
                                ),
                            ],
                            'currentUserCap' => [
                                'can_reply'    => $userCanReply,
                                'can_moderate' => $userCanModerate,
                                'can_delete'   => $userCanDelete || $author->id === $user->ID,
                            ],
                            'replies'        => [],
                        ]));

                        $commentData->created_at->localized = $date->format($theDateTimeFormat);

                        $commentsCache[$commentData->id] = $commentData;
                    }

                    foreach ($commentsCache as $comment) {
                        if ($comment->parent_id > 0) {
                            if (isset($commentsCache[$comment->parent_id])) {
                                $commentsCache[$comment->parent_id]->replies[] = $comment;
                            } else {
                                unset($commentsCache[$comment->id]);
                            }
                        }
                    }
                }
            }
        }

        return $commentsCache;
    }

    /**
     * Set additional notification recipients as needed for newly added comments.
     *
     * @since   1.15.0
     * @static
     *
     * @param   array $recipients Recipients list.
     * @param   int   $comment_id The new comment ID.
     *
     * @return  array
     */
    public static function defineNotificationRecipients($recipients, $comment_id)
    {
        $shouldSend = upstreamSendNotificationsForNewComments();
        if (! $shouldSend) {
            return [];
        }

        // 2 minutes.
        $transientExpiration = 60 * 2;

        $comment = get_comment($comment_id);
        $comment = (object)[
            'id'         => (int)$comment->comment_ID,
            'project_id' => (int)$comment->comment_post_ID,
            'parent'     => (int)$comment->comment_parent,
            'created_by' => (int)$comment->user_id,
            'target'     => get_comment_meta($comment_id, 'type', true),
            'target_id'  => (int)$comment->comment_post_ID,
        ];

        // check if we should disable all emaill notifications for this project
        $meta = (array)get_post_meta($comment->project_id, '_upstream_project_disable_all_notifications');
        if (count($meta) > 0 && $meta[0] === 'on') {
            return [];
        }


        // Check if we need to skip further data processing.
        if ( ! in_array($comment->target, ['project', 'milestone', 'task', 'bug', 'file'])) {
            return $recipients;
        }

        $comment->target_label = call_user_func('upstream_' . $comment->target . '_label');

        if ($comment->target !== 'project') {
            $comment->target_id = get_comment_meta($comment_id, 'id', true);
        }

        set_transient('upstream:comment_notification.comment:' . $comment_id, $comment, $transientExpiration);

        $getUser = function ($user_id) use ($transientExpiration) {
            if ($user_id <= 0) {
                return null;
            }

            // Check if the user is cached.
            $user = get_transient('upstream:comment_notification.user:' . $user_id);
            if (empty($user)) {
                // Check if the user exists.
                $user = get_user_by('id', $user_id);
                if ($user === false) {
                    return null;
                }

                // Prepare user data.
                $user = (object)[
                    'id'    => (int)$user->ID,
                    'name'  => (string)$user->display_name,
                    'email' => (string)$user->user_email,
                ];

                // Cache user.
                set_transient('upstream:comment_notification.user:' . $user->id, $user, $transientExpiration);
            }

            return $user;
        };

        $fetchProjectMetaAsMap = function ($project_id, $key, &$map) use ($transientExpiration, $getUser) {

            $rowset = [];
            if ($key === 'milestone') {
                $rowset = (array)(\UpStream\Milestones::getInstance()->getMilestonesAsRowset($project_id));
            }
            else {
                $rowset = (array)get_post_meta($project_id, '_upstream_project_' . $key . 's', true);
            }

            foreach ($rowset as $row) {
                $titleKey = $key !== 'milestone' ? 'title' : 'milestone';

                if (isset($row['id'])
                    && ! empty($row['id'])
                    && isset($row[$titleKey])
                    && ! empty($row[$titleKey])
                ) {
                    $item = (object)[
                        'id'          => $row['id'],
                        'title'       => $row[$titleKey],
                        'assigned_to' => isset($row['assigned_to']) ? $row['assigned_to'] : [],
                        'created_by'  => isset($row['created_by']) ? (int)$row['created_by'] : 0,
                        'type'        => $key,
                    ];

                    if (count($item->assigned_to) > 0) {
                        foreach ($item->assigned_to as $a) {
                            $user = $getUser($a);
                            $recipients[] = $user->email;
                        }
                    }

                    if ($item->created_by > 0) {
                        $user = $getUser($item->created_by);
                        if (empty($user)) {
                            $item->created_by = 0;
                        } else {
                            $item->created_by = $user->id;
                        }
                    }

                    $map[$item->id] = $item;
                }
            }

        };

        // RSD: this cache is causing issues
        //$project = get_transient('upstream:comment_notification.project:' . $comment->project_id);
        if (empty($project)) {
            $project = get_post($comment->project_id);
            $project = (object)[
                'id'          => (int)$project->ID,
                'title'       => $project->post_title,
                'created_by'  => (int)$project->post_author,
                'owner_id'    => (int)get_post_meta($project->ID, '_upstream_project_owner', true),
                'owner_email' => '',
                'milestones'  => [],
                'tasks'       => [],
                'bugs'        => [],
                'files'       => [],
            ];

            if ($project->owner_id > 0) {
                $owner = get_transient('upstream:comment_notification.user:' . $project->owner_id);
                if (empty($owner)) {
                    $owner = get_user_by('id', $project->owner_id);
                    $owner = (object)[
                        'id'    => $project->owner_id,
                        'name'  => (string)$owner->display_name,
                        'email' => (string)$owner->user_email,
                    ];

                    set_transient('upstream:comment_notification.user:' . $owner->id, $owner, $transientExpiration);
                }

                $pms = upstream_project_members_ids($comment->project_id);
                foreach ($pms as $pm) {
                    $user_info = get_userdata($pm);
                    $email = $user_info->user_email;
                    $recipients[] = $email;
                }
              
            }

            if ($comment->target !== 'project') {

                $fetchProjectMetaAsMap($project->id, $comment->target, $project->{$comment->target . 's'});

                foreach ($project->{$comment->target . 's'} as $item) {
                    $r = $comment->target_id;
                    if ($item->id == $comment->target_id) {
                        if (count($item->assigned_to) > 0) {
                            foreach ($item->assigned_to as $a) {
                                $user = $getUser($a);
                                $recipients[] = $user->email;
                            }
                        }

                        if ($item->created_by > 0) {
                            $user         = $getUser($item->created_by);
                            $recipients[] = $user->email;
                        }
                    }
                }
            }

            set_transient(
                'upstream:comment_notification.project:' . $comment->project_id,
                $project,
                $transientExpiration
            );
        } else {
            if ($comment->target !== 'project'
                && empty($project->{$comment->target . 's'})
            ) {
                $fetchProjectMetaAsMap($project->id, $comment->target, $project->{$comment->target . 's'});

                set_transient(
                    'upstream:comment_notification.project:' . $comment->project_id,
                    $project,
                    $transientExpiration
                );
            }
        }

        if ($comment->parent > 0) {
            $parent_id = $comment->parent;

            $usersCache = [];

            do {
                $parentComment = get_comment($parent_id);

                $parentExists = ! empty($parentComment);
                if ($parentExists) {
                    if ( ! isset($usersCache[$parentComment->user_id])) {
                        $usersCache[$parentComment->user_id]         = $getUser($parentComment->user_id);
                        $usersCache[$parentComment->user_id]->notify = userCanReceiveCommentRepliesNotification($parentComment->user_id);
                    }

                    $user = &$usersCache[$parentComment->user_id];

                    $parentCommentAuthor = $getUser($parentComment->user_id);

                    if ($user->notify) {
                        $recipients[] = $parentCommentAuthor->email;
                    }

                    $parent_id = (int)$parentComment->comment_parent;
                }
            } while ($parentExists);
        }

        $recipients = array_unique(array_filter($recipients));

        $recipients = apply_filters('upstream:comment_notification.recipients', $recipients, $comment);

        return $recipients;
    }

    /**
     * Add additional info to comment notifications subject.
     *
     * @since   1.15.0
     * @static
     *
     * @param   string $subject    The original subject.
     * @param   int    $comment_id The new comment ID.
     *
     * @return  string
     */
    public static function defineNotificationHeader($subject, $comment_id)
    {
        $comment = get_transient('upstream:comment_notification.comment:' . $comment_id);
        // Check if we need to skip further data processing in case of comments written outside UpStream's scope.
        if (empty($comment)
            || in_array($comment->target, ['project', 'milestone', 'task', 'bug', 'file'])
        ) {
            return $subject;
        }

        $project = get_transient('upstream:comment_notification.project:' . $comment->project_id);

        $siteName = get_bloginfo('name');

        $subject = sprintf(
            '[%s][%s] %s',
            $siteName,
            $project->title,
            sprintf(
                _x('New comment on %s', 'Comment notification subject', 'upstream'),
                $comment->target_label
            )
        );

        if ($comment->target !== 'project') {
            $meta = (array)get_post_meta($project->id, '_upstream_project_' . $comment->target . 's', true);
            foreach ($meta as $item) {
                if (isset($item['id']) && $item['id'] === $comment->target_id) {
                    $titleKey = $comment->target === 'milestone' ? 'milestone' : 'title';
                    if (isset($item[$titleKey])) {
                        $subject .= sprintf(': "%s"', $item[$titleKey]);
                    }

                    break;
                }
            }
        }

        $subject = apply_filters('upstream:comment_notification.subject', $subject, $comment, $project);

        return $subject;
    }

    /**
     * @param $commentText
     * @param $commentId
     *
     * @return mixed
     */
    public function addItemTitleToNotification($commentText, $commentId)
    {
        // Check if the comment has item_title in the metadata.
        $itemTitle = get_comment_meta($commentId, 'title', true);
        $itemType  = get_comment_meta($commentId, 'type', true);

        if ( ! empty($itemTitle)) {
            if ($itemType === 'milestone') {
                // Get the milestone's title.
                $milestones = getMilestonesTitles();

                if (isset($milestones[$itemTitle])) {
                    $itemTitle = $milestones[$itemTitle];
                }
            }

            $commentText = __('Item Title: ', 'upstream') . $itemTitle . "\r\n\r\n" . $commentText;
        }

        if ( ! empty($itemType)) {
            $labels = upstream_get_default_labels();

            $itemTypeLabel = $labels[$itemType . 's']['singular'];

            $commentText = __('Item Type: ', 'upstream') . $itemTypeLabel . "\r\n" . $commentText;
        }

        return $commentText;
    }

    /**
     * Convert notifications text for comments in projects into HTML.
     *
     * @param string $text
     * @param int    $comment_id
     *
     * @return string
     */
    public static function filter_comment_notification_text($text, $comment_id)
    {
        if (self::is_comment_from_project($comment_id)) {
            // Convert from txt to html.
            $text = str_replace("\n", '<br>', $text);
            $text = self::replace_email_with_html_link($text);
            $text = self::replace_link_with_html_link($text);
        }

        return $text;
    }

    /**
     * @param $comment_id
     *
     * @return bool
     */
    protected static function is_comment_from_project($comment_id)
    {
        // Check if the post is a project
        $comment = get_comment($comment_id);
        $post    = get_post($comment->comment_post_ID);

        return 'project' === $post->post_type;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    protected static function replace_email_with_html_link($text)
    {
        $text = preg_replace('/([^\s@]+@[a-z\._\-0-9]+)/i', '<a href="mailto:${1}" target="_blank">${1}</a>', $text);

        return $text;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    protected static function replace_link_with_html_link($text)
    {
        $text = preg_replace('~([a-z]+:\/\/\S+)~i', '<a href="${1}" target="_blank">${1}</a>', $text);

        return $text;
    }

    /**
     * Convert notifications for comments in projects into HTML.
     *
     * @param string $headers
     * @param int    $comment_id
     *
     * @return string
     */
    public static function filter_comment_notification_headers($headers, $comment_id)
    {
        if (self::is_comment_from_project($comment_id)) {
            // Convert from txt to html.
            $headers = str_replace('text/plain;', 'text/html;', $headers);
        }

        return $headers;
    }
}
