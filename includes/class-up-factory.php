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
}
