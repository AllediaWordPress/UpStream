<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Traits\Singleton;

/**
 * @since   1.15.0
 */
class UpStream_View
{
    use Singleton;

    protected static $project = null;
    protected static $milestones = [];
    protected static $tasks = [];
    protected static $users = [];

    public function __construct()
    {
        self::$namespace = get_class(
            empty(self::$instance)
                ? $this
                : self::$instance
        );
    }

    public static function getMilestones($projectId = 0)
    {
        return \UpStream\Milestones::getInstance()->getMilestonesAsRowset($projectId);
    }

    public static function getProject($id = 0)
    {
        if (empty($project)) {
            self::setProject($id);
        }

        return self::$project;
    }

    public static function setProject($id = 0)
    {
        self::$project = new UpStream_Project($id);
    }

    public static function getTimeZoneOffset() {
		$offset  = get_option( 'gmt_offset' );
		$sign    = $offset < 0 ? '-' : '+';
		$hours   = (int) $offset;
		$minutes = abs( ( $offset - (int) $offset ) * 60 );
        $offset  = (int)sprintf( '%s%d%02d', $sign, abs( $hours ), $minutes );
        $calc_offset_seconds = $offset < 0 ? $offset * -1 * 60 : $offset * 60;
		return (int)( $calc_offset_seconds );
	}

    public static function getTasks($projectId = 0)
    {
        $project = self::getProject($projectId);

        if (count(self::$tasks) === 0) {
            $data   = [];
            $rowset = array_filter((array)$project->get_meta('tasks'));

            $statuses = getTasksStatuses();

            foreach ($rowset as $row) {
                $row['created_by']   = (int)$row['created_by'];
                $row['created_time'] = isset($row['created_time']) ? (int)$row['created_time'] : 0;
                $assignees           = [];
                if (isset($row['assigned_to'])) {
                    $assignees = array_map(
                        'intval',
                        ! is_array($row['assigned_to']) ? (array)$row['assigned_to'] : $row['assigned_to']
                    );
                }

                $row['assigned_to'] = $assignees;

                if ( ! empty($assignees)) {
                    // Get the name of assignees to fix ordering.
                    $row['assigned_to_order'] = upstream_get_users_display_name($assignees);
                }

                $row['status_order'] = isset($row['status']) ? @$statuses[$row['status']]['order'] : '0';
                $row['progress']     = isset($row['progress']) ? (float)$row['progress'] : 0.00;
                $row['notes']        = isset($row['notes']) ? (string)$row['notes'] : '';

                $row['start_date']   = ! isset($row['start_date']) || ! is_numeric($row['start_date']) || $row['start_date'] < 0 ? 0 : (int)$row['start_date'];// + self::getTimeZoneOffset();
                $row['end_date']     = ! isset($row['end_date']) || ! is_numeric($row['end_date']) || $row['end_date'] < 0 ? 0 : (int)$row['end_date'];// + self::getTimeZoneOffset();

                $data[$row['id']] = $row;
            }

            self::$tasks = $data;
        } else {
            $data = self::$tasks;
        }

        return $data;
    }

    public static function getBugs($projectId = 0)
    {
        $rowset = [];

        $severities = getBugsSeverities();
        $statuses   = getBugsStatuses();

        $meta = (array)get_post_meta($projectId, '_upstream_project_bugs', true);
        foreach ($meta as $data) {
            if ( ! isset($data['id'])
                 || ! isset($data['created_by'])
            ) {
                continue;
            }

            $data['created_by']   = (int)$data['created_by'];
            $data['created_time'] = isset($data['created_time']) ? (int)$data['created_time'] : 0;

            $assignees = [];
            if (isset($data['assigned_to'])) {
                $assignees = array_map(
                    'intval',
                    ! is_array($data['assigned_to']) ? (array)$data['assigned_to'] : $data['assigned_to']
                );
            }

            $data['assigned_to'] = $assignees;

            if ( ! empty($assignees)) {
                // Get the name of assignees to fix ordering.
                $data['assigned_to_order'] = upstream_get_users_display_name($assignees);
            }

            $data['description']    = isset($data['description']) ? (string)$data['description'] : '';
            $data['severity']       = isset($data['severity']) ? (string)$data['severity'] : '';
            $data['severity_order'] = isset($data['severity']) ? @$severities[$data['severity']]['order'] : '0';
            $data['status']         = isset($data['status']) ? (string)$data['status'] : '';
            $data['status_order']   = isset($data['status']) ? @$statuses[$data['status']]['order'] : '0';
            $data['start_date']     = ! isset($data['start_date']) || ! is_numeric($data['start_date']) || $data['start_date'] < 0 ? 0 : (int)$data['start_date'];// + self::getTimeZoneOffset();
            $data['end_date']       = ! isset($data['end_date']) || ! is_numeric($data['end_date']) || $data['end_date'] < 0 ? 0 : (int)$data['end_date'];// + self::getTimeZoneOffset();

            $rowset[$data['id']] = $data;
        }

        return $rowset;
    }

    protected static function getUsers()
    {
        if (count(self::$users) === 0) {
            self::$users = upstreamGetUsersMap();
        }

        return self::$users;
    }
}
