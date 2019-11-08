<?php


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class UpStream_Model_Task extends UpStream_Model_Meta_Object
{
    public $statusCode = null;

    public $progress = 0;

    public $milestone = null;

    public $startDate = null;

    public $endDate = null;

    /**
     * UpStream_Model_Task constructor.
     */
    public function __construct($item_metadata)
    {
        parent::__construct($item_metadata);

        $this->type = UPSTREAM_ITEM_TYPE_TASK;
    }

    protected function load($item_metadata)
    {
        parent::load($item_metadata);

        $this->description = isset($item_metadata['notes']) ? $item_metadata['notes'] : '';
        $this->statusCode = isset($item_metadata['status']) ? $item_metadata['status'] : null;
        $this->startDate = isset($item_metadata['start_date']) ? $item_metadata['start_date'] : null;
        $this->endDate = isset($item_metadata['end_date']) ? $item_metadata['end_date'] : null;
    }

    public function store($parent, &$item_metadata)
    {
        parent::store($parent, $item_metadata);

        if ($this->statusCode != null) $item['status'] = $this->statusCode;
        if ($this->startDate != null) $item['start_date'] = $this->startDate;
        if ($this->endDate != null) $item['end_date'] = $this->endDate;
        if ($this->description != '') $item['notes'] = $this->description;

    }

}