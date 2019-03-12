<?php

namespace UpStream;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * @since   1.24.0
 */
class Factory
{
    /**
     * @param $postId
     *
     * @return Milestone
     */
    public static function getMilestone($postId)
    {
        return new Milestone($postId);
    }

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
}
