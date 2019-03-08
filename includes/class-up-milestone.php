<?php

namespace UpStream;

// Prevent direct access.

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * @since   1.24.0
 */
class Milestone extends Struct
{
    /**
     * @var int
     */
    protected $postId;

    /**
     * @var \WP_Post
     */
    protected $post;

    /**
     * @var int
     */
    protected $projectId;

    /**
     * @var array
     */
    protected $assignedTo;

    /**
     * Start date in MySQL timestamp.
     *
     * @var string
     */
    protected $startDate;

    /**
     * End date in MySQL timestamp.
     *
     * @var string
     */
    protected $endDate;

    /**
     * @var string
     */
    protected $notes;

    /**
     * The Post Type for milestones.
     */
    const POST_TYPE = 'upst_milestone';

    /**
     * Project ID meta key.
     */
    const META_PROJECT_ID = 'upst_project_id';

    /**
     * Assigned To meta key.
     */
    const META_ASSIGNED_TO = 'upst_assigned_to';

    /**
     * Start date meta key.
     */
    const META_START_DATE = 'upst_start_date';

    /**
     * End date meta key.
     */
    const META_END_DATE = 'upst_end_date';

    /**
     * Milestone constructor.
     *
     * @param int $postId
     *
     * @throws \Exception
     */
    public function __construct($postId)
    {
        if (empty($postId)) {
            throw new Exception('Invalid milestone post ID');
        }

        $this->postId = $postId;
        $this->post   = $this->getPost();
    }

    /**
     * @return \WP_Post|false
     */
    public function getPost()
    {
        if (empty($this->post)) {
            if (empty($this->postId)) {
                return false;
            }

            $this->post = get_post($this->postId);
        }

        return $this->post;
    }

    /**
     * @param      $metaKey
     * @param bool $single
     *
     * @return mixed
     */
    protected function getMetadata($metaKey, $single = false)
    {
        return get_post_meta($this->postId, $metaKey, $single);
    }

    /**
     * @return int
     */
    public function getProjectId()
    {
        if (null === $this->projectId) {
            $this->projectId = $this->getMetadata(self::META_PROJECT_ID, true);
        }

        return $this->projectId;
    }

    /**
     * @param int $projectId
     *
     * @return Milestone
     */
    public function setProjectId($projectId)
    {
        $this->projectId = (int)$projectId;

        update_post_meta($this->postId, self::META_PROJECT_ID, $projectId);

        return $this;
    }

    /**
     * @return array
     */
    public function getAssignedTo()
    {
        if (null === $this->assignedTo) {
            $this->assignedTo = $this->getMetadata(self::META_ASSIGNED_TO, false);
        }

        return (array)$this->assignedTo;
    }

    /**
     * @param array $assignedTo
     *
     * @return Milestone
     */
    public function setAssignedTo($assignedTo)
    {
        if ( ! is_array($assignedTo)) {
            $assignedTo = [];
        }

        $assignedTo = array_map('intval', $assignedTo);

        delete_post_meta($this->postId, self::META_ASSIGNED_TO);

        $this->assignedTo = [];

        foreach ($assignedTo as $userId) {
            if (empty($userId)) {
                continue;
            }

            $this->assignedTo[] = $userId;
            add_post_meta($this->postId, self::META_ASSIGNED_TO, $userId, false);
        }

        return $this;
    }

    /**
     * @param string $format mysql, unix, upstream
     *
     * @return string
     */
    public function getStartDate($format = 'mysql')
    {
        if (null === $this->startDate) {
            $this->startDate = $this->getMetadata(self::META_START_DATE, true);
        }

        return $this->getDateOnFormat($this->startDate, $format);
    }

    /**
     * @param int|string $startDate
     *
     * @return Milestone
     */
    public function setStartDate($startDate)
    {
        $startDate = $this->getMySQLDate($startDate);

        $this->startDate = $startDate;

        // Assume it is on MySQL date format.
        update_post_meta($this->postId, self::META_START_DATE, $startDate);

        return $this;
    }

    /**
     * @param mixed $date
     *
     * @return false|mixed|string|void
     */
    protected function getMySQLDate($date)
    {
        if ( ! $this->dateIsMySQLDateFormat($date)) {
            if ( ! $this->dateIsUnixTime($date)) {
                // Convert to unix time.
                $date = upstream_date_unixtime($date);
            }

            // Assume it is in unix time format and convert to MySQL date format.
            $date = date('Y-m-d', $date);
        }

        return $date;
    }

    /**
     * @param int|string $date
     *
     * @return bool
     */
    protected function dateIsMySQLDateFormat($date)
    {
        return preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $date);
    }

    /**
     * @param $date
     *
     * @return bool
     */
    protected function dateIsUnixTime($date)
    {
        return preg_match('/^\d+$/', $date);
    }

    /**
     * @param $date
     * @param $format
     *
     * @return false|int|mixed
     */
    protected function getDateOnFormat($date, $format)
    {
        if ($format === 'unix') {
            return strtotime($date);
        }

        if ($format === 'upstream') {
            if ( ! preg_match('/^\d+$/', $date)) {
                $date = strtotime($date);
            }

            return upstream_format_date($date);
        }

        return $date;
    }

    /**
     * @param string $format mysql, unix, upstream
     *
     * @return string
     */
    public function getEndDate($format = 'mysql')
    {
        if (null === $this->endDate) {
            $this->endDate = $this->getMetadata(self::META_END_DATE, true);
        }

        return $this->getDateOnFormat($this->endDate, $format);
    }

    /**
     * @param int|string $endDate
     *
     * @return Milestone
     */
    public function setEndDate($endDate)
    {
        $endDate = $this->getMySQLDate($endDate);

        $this->endDate = $endDate;

        // Assume it is on MySQL date format.
        update_post_meta($this->postId, self::META_END_DATE, $endDate);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNotes()
    {
        if ( ! empty($this->notes)) {
            return $this->notes;
        }

        return $this->getPost()->post_content;
    }

    /**
     * @return \UpStream\Milestones
     */
    protected function getMilestonesInstance()
    {
        return Milestones::getInstance();
    }

    /**
     * @param string $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        $milestones = $this->getMilestonesInstance();

        remove_action('save_post', [$milestones, 'savePost']);
        wp_update_post(
            [
                'ID'           => $this->postId,
                'post_content' => $notes,
            ]
        );
        add_action('save_post', [$milestones, 'savePost']);
    }
}
