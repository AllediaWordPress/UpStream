<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_Reminder extends UpStream_Model_Object
{

    public $intervalId = 0;

    public $timestamp = 0;

    public $sentAt = 0;

    /**
     * UpStream_Model_Reminder constructor.
     */
    public function __construct($item_metadata)
    {
        parent::__construct();

        $this->load($item_metadata);
    }

    protected function load($item_metadata)
    {
        $this->id = isset($item_metadata['id']) ? $item_metadata['id'] : 0;
        $this->intervalId = isset($item_metadata['reminder']) && $item_metadata['reminder'] <= 1000 ? $item_metadata['reminder'] : 0;
        $this->timestamp = isset($item_metadata['reminder']) && $item_metadata['reminder'] > 1000 ? $item_metadata['reminder'] : 0;
        $this->sentAt = isset($item_metadata['sent_at']) && $item_metadata['sent_at'] != null ? $item_metadata['sent_at'] : 0;
    }

    public function store(&$item_metadata)
    {
        if (!empty($this->id)) $item_metadata['id'] = $this->id;
        if ($this->intervalId > 0) $item_metadata['reminder'] = $this->intervalId;
        if ($this->timestamp > 0) $item_metadata['reminder'] = $this->timestamp;
        if ($this->sentAt > 0) {
            $item_metadata['sent'] = true;
            $item_metadata['sent_at'] = $this->sentAt;
        } else {
            $item_metadata['sent'] = false;
        }
    }
}