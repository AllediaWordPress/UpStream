<?php

namespace UpStream\Traits;

// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Trait that abstracts the metadata functions
 *
 * @package     UpStream
 * @subpackage  Traits
 * @author      UpStream <https://upstreamplugin.com>
 * @copyright   Copyright (c) 2018 UpStream Project Management
 * @license     GPL-3
 * @since       1.11.0
 */
trait PostMetadata
{
    /**
     * @var int
     */
    protected $postId;

    /**
     * @param      $metaKey
     * @param bool $single
     *
     * @return mixed
     */
    public function getMetadata($metaKey, $single = false)
    {
        return get_post_meta($this->postId, $metaKey, $single);
    }

    /**
     * @param array $dataset
     */
    public function updateMetadata($dataset)
    {
        if ( ! empty($dataset)) {
            foreach ($dataset as $metaKey => $metaValue) {
                update_post_meta($this->postId, $metaKey, $metaValue);
            }
        }
    }

    /**
     * @param string|array $metaKey
     */
    public function deleteMetadata($metaKey)
    {
        if (empty($metaKey)) {
            return;
        }

        // Only one meta key?
        if (is_string($metaKey)) {
            delete_post_meta($this->postId, $metaKey);

            return;
        }

        // An array of meta keys?
        if (is_array($metaKey)) {
            foreach ($metaKey as $key) {
                if ( ! empty($key)) {
                    delete_post_meta($this->postId, $key);
                }
            }
        }
    }

    /**
     * @param array $dataset
     *
     * @return array|false
     */
    public function addUniqueMetadata($dataset)
    {
        if (empty($dataset)) {
            return false;
        }

        $metaIds = [];

        foreach ($dataset as $metaKey => $metaValue) {
            $metaIds[$metaKey] = add_post_meta($this->postId, $metaKey, $metaKey, true);
        }

        return $metaIds;
    }

    /**
     * @param string $metaKey
     * @param array  $metaValues
     *
     * @return array|false
     */
    public function addNonUniqueMetadata($metaKey, $metaValues)
    {
        if (empty($metaKey) || empty($metaValues)) {
            return false;
        }

        $metaIds = [];

        foreach ($metaValues as $metaValue) {
            $metaIds[] = add_post_meta($this->postId, $metaKey, $metaValue);
        }

        return $metaIds;
    }

    /**
     * @param string $metaKey
     * @param array  $metaValues
     *
     * @return array|false
     */
    public function updateNonUniqueMetadata($metaKey, $metaValues)
    {
        $this->deleteMetadata($metaKey);

        return $this->addNonUniqueMetadata($metaKey, $metaValues);
    }
}
