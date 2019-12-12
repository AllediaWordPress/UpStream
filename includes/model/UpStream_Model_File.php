<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_File extends UpStream_Model_Meta_Object
{
    protected $fileId = 0;

    protected $reminders = [];

    protected $metadataKey = '_upstream_project_files';

    protected $type = UPSTREAM_ITEM_TYPE_MILESTONE;

    /**
     * UpStream_Model_File constructor.
     */
    public function __construct($parent, $item_metadata)
    {
        parent::__construct($parent, $item_metadata);

        $this->type = UPSTREAM_ITEM_TYPE_FILE;
    }


    protected function loadFromArray($item_metadata)
    {
        parent::loadFromArray($item_metadata);

        if (!empty($item_metadata['file_id'])) {
            $file = get_attached_file($item_metadata['file_id']);
            if ($file != false) {
                $this->fileId = $item_metadata['file_id'];
            }
        }

        if (!empty($item_metadata['reminders'])) {
            foreach ($item_metadata['reminders'] as $reminder_data) {

                try {
                    $d = json_decode($reminder_data, true);
                    $reminder = new UpStream_Model_Reminder($d);
                    $this->reminders[] = $reminder;
                } catch (\Exception $e) {
                    // don't add anything else
                }

            }
        }
    }

    public function storeToArray(&$item_metadata)
    {
        parent::storeToArray($item_metadata);

        if ($this->fileId > 0) {
            $url = wp_get_attachment_url($this->fileId);

            if ($url != false) {
                $item_metadata['file'] = $url;
                $item_metadata['file_id'] = $this->fileId;
            }
        }

        $item_metadata['reminders'] = [];

        foreach ($this->reminders as $reminder) {
            $r = [];
            $reminder->storeToArray($r);
            $item_metadata['reminders'][] = $r;
        }
    }

    public function __get($property)
    {
        switch ($property) {

            case 'fileId':
                return $this->{$property};

            default:
                return parent::__get($property);
        }
    }

    public function __set($property, $value)
    {
        switch ($property) {

            case 'fileId':
                $file = get_attached_file($value);
                if ($file === false)
                    throw new UpStream_Model_ArgumentException(sprintf(__('File ID %s is invalid.', 'upstream'), $value));

                $this->fileId = $value;
                break;

            default:
                parent::__set($property, $value);
                break;
        }
    }

    public static function create($parent, $title, $createdBy)
    {
        $item_metadata = ['title' => $title, 'created_by' => $createdBy];

        return new self($parent, $item_metadata);
    }

}