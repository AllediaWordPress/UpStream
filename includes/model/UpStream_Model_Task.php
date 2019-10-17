<?php


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class UpStream_Model_Task extends UpStream_Model_Meta_Object
{
    /**
     * UpStream_Model_Task constructor.
     */
    public function __construct($item_metadata)
    {
        parent::__construct($item_metadata);

        $this->type = UPSTREAM_ITEM_TYPE_TASK;
    }

}