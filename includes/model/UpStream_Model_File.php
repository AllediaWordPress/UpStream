<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_File extends UpStream_Model_Meta_Object
{
    /**
     * UpStream_Model_Fil constructor.
     */
    public function __construct($item_metadata)
    {
        parent::__construct($item_metadata);

        $this->type = UPSTREAM_ITEM_TYPE_FILE;
    }

    protected function load($item_metadata)
    {
        parent::load($item_metadata);

        $this->description = !empty($item_metadata['description']) ? $item_metadata['description'] : '';
    }

    public function store($parent, &$item_metadata)
    {
        parent::store($parent, $item_metadata);

        if ($this->statusCode != null) $item['status'] = $this->statusCode;
    }
}