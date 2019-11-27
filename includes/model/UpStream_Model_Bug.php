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

    public $reminders = [];

    protected $metadataKey = '_upstream_project_bugs';

    /**
     * UpStream_Model_Bug constructor.
     */
    public function __construct($parent, $item_metadata)
    {
        parent::__construct($parent, $item_metadata);

        $this->type = UPSTREAM_ITEM_TYPE_BUG;
    }


    protected function loadFromArray($item_metadata)
    {
        parent::loadFromArray($item_metadata);

        $this->statusCode = !empty($item_metadata['status']) ? $item_metadata['status'] : null;
        $this->severityCode = !empty($item_metadata['severity']) ? $item_metadata['severity'] : null;
        $this->dueDate = !empty($item_metadata['due_date']) ? UpStream_Model_Object::timestampToYMD($item_metadata['due_date']) : null;

        if (!empty($item_metadata['reminders'])) {
            foreach ($item_metadata['reminders'] as $reminder_data) {
                $reminder = new UpStream_Model_Reminder(json_decode($reminder_data, true));
                $this->reminders[] = $reminder;
            }
        }
    }

    public function storeToArray(&$item_metadata)
    {
        parent::storeToArray($item_metadata);

        if ($this->statusCode != null) $item_metadata['status'] = $this->statusCode;
        if ($this->severityCode != null) $item_metadata['severity'] = $this->severityCode;
        if ($this->dueDate != null) $item_metadata['due_date'] = UpStream_Model_Object::ymdToTimestamp($this->dueDate);
        if ($this->dueDate != null) $item_metadata['due_date__YMD'] = $this->dueDate;

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

            case 'severity':
                $s = $this->getSeverities();

                foreach ($s as $sKey => $sValue) {
                    if ($this->severityCode === $sKey)
                        return $sValue;
                }
                return '';

            case 'severityCode':
                return $this->{$property};

            default:
                return parent::__get($property);
        }
    }

    public function __set($property, $value)
    {
        switch ($property) {

            case 'severity':
                $s = $this->getSeverities();

                foreach ($s as $sKey => $sValue) {
                    if ($value === $sValue) {
                        $this->severityCode = $sKey;
                        break;
                    }
                }

                break;

            case 'severityCode':
                $s = $this->getSeverities();

                foreach ($s as $sKey => $sValue) {
                    if ($value === $sKey) {
                        $this->severityCode = $sKey;
                        break;
                    }
                }

                break;
        }
    }

    protected function getSeverities()
    {
        $option     = get_option('upstream_bugs');
        $severities = isset($option['severities']) ? $option['severities'] : '';
        $array      = [];
        if ($severities) {
            foreach ($severities as $severity) {
                if (isset($severity['name'])) {
                    $array[$severity['id']] = $severity['name'];
                }
            }
        }

        return $array;
    }

    public static function create($parent, $title, $createdBy)
    {
        $item_metadata = ['title' => $title, 'created_by' => $createdBy];

        return new self($parent, $item_metadata);
    }

}