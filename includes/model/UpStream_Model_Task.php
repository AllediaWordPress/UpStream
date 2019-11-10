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

    public $reminders = [];

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

        $this->description = !empty($item_metadata['notes']) ? $item_metadata['notes'] : '';
        $this->statusCode = !empty($item_metadata['status']) ? $item_metadata['status'] : null;
        $this->progress = !empty($item_metadata['progress']) ? $item_metadata['progress'] : null;
        $this->startDate = !empty($item_metadata['start_date']) ? UpStream_Model_Object::timestampToYMD($item_metadata['start_date']) : null;
        $this->endDate = !empty($item_metadata['end_date']) ? UpStream_Model_Object::timestampToYMD($item_metadata['end_date']) : null;

        if (!empty($item_metadata['reminders'])) {
            foreach ($item_metadata['reminders'] as $reminder_data) {
                $reminder = new UpStream_Model_Reminder(json_decode($reminder_data, true));
                $this->reminders[] = $reminder;
            }
        }
    }

    public function store($parent, &$item_metadata)
    {
        parent::store($parent, $item_metadata);

        if ($this->statusCode != null) $item['status'] = $this->statusCode;
        if ($this->progress > 0) $item['progress'] = $this->progress;
        if ($this->startDate != null) $item['start_date'] = UpStream_Model_Object::ymdToTimestamp($this->startDate);
        if ($this->endDate != null) $item['end_date'] = UpStream_Model_Object::ymdToTimestamp($this->endDate);
        if ($this->description != '') $item['notes'] = $this->description;

    }

}