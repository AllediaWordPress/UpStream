<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_File extends UpStream_Model_Meta_Object
{

    protected $metadataKey = '_upstream_project_files';

    /**
     * UpStream_Model_Fil constructor.
     */
    public function __construct($parent, $item_metadata)
    {
        parent::__construct($parent, $item_metadata);

        $this->type = UPSTREAM_ITEM_TYPE_FILE;
    }

    protected function loadFromArray($item_metadata)
    {
        parent::loadFromArray($item_metadata);
    }

    public function storeToArray(&$item_metadata)
    {
        parent::storeToArray($item_metadata);

//        if ($this->statusCode != null) $item['status'] = $this->statusCode;
    }

    public static function create($parent, $title, $createdBy)
    {
        $item_metadata = ['title' => $title, 'created_by' => $createdBy];

        return new self($parent, $item_metadata);
    }

}