<?php

namespace UpStream;

use Upstream_Counter;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * @since   1.24.0
 */
abstract class Factory
{
    protected static $counters = [];

    /**
     * @param string @name
     *
     * @return Milestone
     * @throws \Exception
     */
    public static function createMilestone($name)
    {
        $postId = wp_insert_post([
            'post_type'   => Milestone::POST_TYPE,
            'post_title'  => sanitize_text_field($name),
            'post_status' => 'publish',
        ]);

        return self::getMilestone($postId);
    }

    /**
     * @param int|\WP_Post $post
     *
     * @return Milestone
     * @throws \Exception
     */
    public static function getMilestone($post)
    {
        return new Milestone($post);
    }

    /**
     * @return \UpStream_Project_Activity
     */
    public static function getActivity()
    {
        return \UpStream_Project_Activity::getInstance();
    }

    /**
     * @param int|array $projectIds
     *
     * @return Upstream_Counter
     */
    public static function getProjectCounter($projectIds)
    {
        if ( ! isset(self::$counters[$projectIds])) {
            self::$counters[$projectIds] = new Upstream_Counter($projectIds);
        }

        return self::$counters[$projectIds];
    }
}
