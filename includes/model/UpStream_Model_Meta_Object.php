<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_Meta_Object extends UpStream_Model_Object
{
    /**
     * UpStream_Model_Meta_Object constructor.
     */
    public function __construct($item_metadata)
    {
        $this->load($item_metadata);
    }

    protected function load($item_metadata)
    {
        $this->id = isset($item_metadata['id']) ? $item_metadata['id'] : 0;
        $this->title = isset($item_metadata['title']) ? $item_metadata['title'] : null;
        $this->assignedTo = isset($item_metadata['assigned_to']) ? $item_metadata['assigned_to'] : [];
        $this->createdBy = isset($item_metadata['created_by']) ? $item_metadata['created_by'] : [];
    }
}