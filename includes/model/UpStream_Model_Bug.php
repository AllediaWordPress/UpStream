<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_Bug extends UpStream_Model_Meta_Object
{
    protected $fileId = 0;

    protected $severityCode = null;

    protected $statusCode = null;

    protected $dueDate = null;

    protected $reminders = [];

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
        $this->dueDate = UpStream_Model_Object::loadDate($item_metadata, 'due_date');

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

        if ($this->statusCode != null) $item_metadata['status'] = $this->statusCode;
        if ($this->severityCode != null) $item_metadata['severity'] = $this->severityCode;
        if ($this->dueDate != null) $item_metadata['due_date'] = UpStream_Model_Object::ymdToTimestamp($this->dueDate);
        if ($this->dueDate != null) $item_metadata['due_date__YMD'] = $this->dueDate;

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

            case 'severity':
                $s = $this->getSeverities();

                foreach ($s as $sKey => $sValue) {
                    if ($this->severityCode === $sKey)
                        return $sValue;
                }
                return '';

            case 'status':
                $s = $this->getStatuses();

                foreach ($s as $sKey => $sValue) {
                    if ($this->statusCode === $sKey)
                        return $sValue;
                }
                return '';

            case 'dueDate':
            case 'fileId':
            case 'severityCode':
            case 'statusCode':
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

            case 'severity':
                $s = $this->getSeverities();
                $sc = null;

                foreach ($s as $sKey => $sValue) {
                    if ($value === $sValue) {
                        $sc = $sKey;
                        break;
                    }
                }

                if ($sc == null)
                    throw new UpStream_Model_ArgumentException(sprintf(__('Severity %s is invalid.', 'upstream'), $value));

                $this->severityCode = $sc;
                break;

            case 'severityCode':
                $s = $this->getSeverities();
                $sc = null;

                foreach ($s as $sKey => $sValue) {
                    if ($value === $sKey) {
                        $sc = $sKey;
                        break;
                    }
                }

                if ($sc == null)
                    throw new UpStream_Model_ArgumentException(sprintf(__('Severity code %s is invalid.', 'upstream'), $value));

                $this->severityCode = $sc;
                break;

            case 'status':
                $s = $this->getStatuses();
                $sc = null;

                foreach ($s as $sKey => $sValue) {
                    if ($value === $sValue) {
                        $sc = $sKey;
                        break;
                    }
                }

                if ($sc == null)
                    throw new UpStream_Model_ArgumentException(sprintf(__('Status %s is invalid.', 'upstream'), $value));

                $this->statusCode = $sc;

                break;

            case 'statusCode':
                $s = $this->getStatuses();
                $sc = null;

                foreach ($s as $sKey => $sValue) {
                    if ($value === $sKey) {
                        $sc = $sKey;
                        break;
                    }
                }

                if ($sc == null)
                    throw new UpStream_Model_ArgumentException(sprintf(__('Status code %s is invalid.', 'upstream'), $value));

                $this->statusCode = $sc;
                break;

            case 'dueDate':
                if (!self::isValidDate($value))
                    throw new UpStream_Model_ArgumentException(__('Argument is not a valid date.', 'upstream'));

                $this->{$property} = $value;
                break;

            default:
                parent::__set($property, $value);
                break;
        }
    }

    protected function getStatuses()
    {
        $option   = get_option('upstream_bugs');
        $statuses = isset($option['statuses']) ? $option['statuses'] : '';
        $array    = [];
        if ($statuses) {
            foreach ($statuses as $status) {
                if (isset($status['name'])) {
                    $array[$status['id']] = $status['name'];
                }
            }
        }

        return $array;
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