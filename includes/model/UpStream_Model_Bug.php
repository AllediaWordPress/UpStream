<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_Bug extends UpStream_Model_Meta_Object
{

    public $severityCode = null;

    public $statusCode = null;

    public $dueDate = null;

    /**
     * UpStream_Model_Bug constructor.
     */
    public function __construct($item_metadata)
    {
        parent::__construct($item_metadata);

        $this->type = UPSTREAM_ITEM_TYPE_BUG;
    }


    protected function load($item_metadata)
    {
        parent::load($item_metadata);

        $this->description = !empty($item_metadata['description']) ? $item_metadata['description'] : '';
        $this->statusCode = !empty($item_metadata['status']) ? $item_metadata['status'] : null;
        $this->severityCode = !empty($item_metadata['severity']) ? $item_metadata['severity'] : null;
        $this->dueDate = !empty($item_metadata['due_date']) ? UpStream_Model_Object::timestampToYMD($item_metadata['due_date']) : null;
    }

    public function store($parent, &$item_metadata)
    {
        parent::store($parent, $item_metadata);

        if ($this->statusCode != null) $item['status'] = $this->statusCode;
        if ($this->severityCode != null) $item['severity'] = $this->severityCode;
        if ($this->dueDate != null) $item['due_date'] = UpStream_Model_Object::ymdToTimestamp($this->dueDate);
        if ($this->description != '') $item['description'] = $this->description;

    }

}