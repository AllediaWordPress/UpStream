<?php


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class UpStream_Model_Task extends UpStream_Model_Meta_Object
{
    protected $statusCode = null;

    protected $progress = 0;

    protected $milestoneId = 0;

    protected $startDate = null;

    protected $endDate = null;

    protected $notes = '';

    protected $reminders = [];

    protected $timeRecords = [];

    protected $metadataKey = '_upstream_project_tasks';

    protected $type = UPSTREAM_ITEM_TYPE_TASK;

    /**
     * UpStream_Model_Task constructor.
     */
    public function __construct($parent, $item_metadata)
    {
        parent::__construct($parent, $item_metadata);
    }

    protected function loadFromArray($item_metadata)
    {
        parent::loadFromArray($item_metadata);

        $this->statusCode = !empty($item_metadata['status']) ? $item_metadata['status'] : null;
        $this->progress = !empty($item_metadata['progress']) ? $item_metadata['progress'] : null;
        $this->startDate = UpStream_Model_Object::loadDate($item_metadata, 'start_date');
        $this->endDate = UpStream_Model_Object::loadDate($item_metadata, 'end_date');
        $this->milestoneId = !empty($item_metadata['milestone']) ? $item_metadata['milestone'] : null;
        $this->notes = !empty($item_metadata['notes']) ? $item_metadata['notes'] : '';

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

        if (!empty($item_metadata['records'])) {
            foreach ($item_metadata['records'] as $tr_data) {

                try {
                    $d = json_decode($tr_data, true);
                    $timeRecord = new UpStream_Model_TimeRecord($d);
                    $this->timeRecords[] = $timeRecord;
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
        if ($this->progress >= 0) $item_metadata['progress'] = $this->progress;
        if ($this->startDate != null) $item_metadata['start_date'] = UpStream_Model_Object::ymdToTimestamp($this->startDate);
        if ($this->endDate != null) $item_metadata['end_date'] = UpStream_Model_Object::ymdToTimestamp($this->endDate);
        if ($this->startDate != null) $item_metadata['start_date.YMD'] = $this->startDate;
        if ($this->endDate != null) $item_metadata['end_date.YMD'] = $this->endDate;
        if ($this->notes != null) $item_metadata['notes'] = $this->notes;
        if ($this->milestoneId > 0) $item_metadata['milestone'] = $this->milestoneId;

        $item_metadata['reminders'] = [];

        foreach ($this->reminders as $reminder) {
            $r = [];
            $reminder->storeToArray($r);
            $item_metadata['reminders'][] = json_encode($r);
        }

        $item_metadata['time_records'] = [];

        foreach ($this->timeRecords as $tr) {
            $r = [];
            $tr->storeToArray($r);
            $item_metadata['records'][] = json_encode($r);
        }
    }

    public function calculateElapsedTime()
    {
        $total = 0;

        foreach ($this->timeRecords as $tr) {
            $total += $tr->elapsedTime;
        }

        return $total;
    }

    public function getMilestone()
    {
        if ($this->milestoneId) {
            try {
                return \UpStream_Model_Manager::get_instance()->getByID(UPSTREAM_ITEM_TYPE_MILESTONE,
                    $this->milestoneId, UPSTREAM_ITEM_TYPE_PROJECT, $this->parent->id);
            } catch (\Exception $e) {

            }
        }

        return null;
    }

    public function setMilestone($milestone)
    {
        $this->milestoneId = $milestone->id;
    }

    public function __get($property)
    {
        switch ($property) {

            case 'milestone':
                return $this->getMilestone();

            case 'status':
                $s = $this->getStatuses();

                foreach ($s as $sKey => $sValue) {
                    if ($this->statusCode === $sKey)
                        return $sValue;
                }
                return '';

            case 'statusCode':
            case 'notes':
            case 'milestoneId':
            case 'progress':
            case 'timeRecords':
            case 'startDate':
            case 'endDate':
                return $this->{$property};

            default:
                return parent::__get($property);
        }
    }

    public function __set($property, $value)
    {
        switch ($property) {

            case 'milestone':
                if (!$value instanceof UpStream_Model_Milestone)
                    throw new UpStream_Model_ArgumentException(__('Argument must be of type milestone.', 'upstream'));
                elseif ($value->id == 0)
                    throw new UpStream_Model_ArgumentException(__('Milestone must be stored before setting.', 'upstream'));

                return $this->setMilestone($value);

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

            case 'notes':
                $this->notes = wp_kses_post($value);
                break;

            case 'milestoneId':
                $milestone = UpStream_Model_Manager::get_instance()->getByID(UPSTREAM_ITEM_TYPE_MILESTONE, $value, UPSTREAM_ITEM_TYPE_PROJECT, $this->parent->id);
                $this->milestoneId = $milestone->id;
                break;

            case 'progress':
                if (!filter_var($value, FILTER_VALIDATE_INT) || (int)$value < 0 || (int)$value > 100 || ((int)$value) % 5 != 0)
                    throw new UpStream_Model_ArgumentException(__('Argument must be a multiple of 5 between 0 and 100.', 'upstream'));

                $this->{$property} = $value;
                break;

            case 'startDate':
            case 'endDate':
                if (!self::isValidDate($value))
                    throw new UpStream_Model_ArgumentException(__('Argument is not a valid date.', 'upstream'));

                $this->{$property} = $value;
                break;

            case 'timeRecords':
                if (!is_array($value))
                    throw new UpStream_Model_ArgumentException(__('Argument must be an array of UpStream_Model_TimeRecord objects.', 'upstream'));

                foreach ($value as $item) {
                    if (!$item instanceof UpStream_Model_TimeRecord)
                        throw new UpStream_Model_ArgumentException(__('Argument must be an array of UpStream_Model_TimeRecord objects.', 'upstream'));
                }

                $this->{$property} = $value;
                break;

            default:
                parent::__set($property, $value);
                break;
        }
    }


    public static function fields()
    {
        $fields = parent::fields();

        $fields['startDate'] = [ 'type' => 'date', 'title' => __('Start Date'), 'search' => true, 'display' => true ];
        $fields['endDate'] = [ 'type' => 'date', 'title' => __('End Date'), 'search' => true, 'display' => true ];
        $fields['description'] = [ 'type' => 'text', 'title' => __('Notes'), 'search' => true, 'display' => true  ];
        $fields['statusCode'] = [ 'type' => 'select', 'title' => __('Status'), 'search' => true, 'display' => true, 'options_cb' => 'UpStream_Model_Task::getStatuses' ];
        $fields['progress'] = [ 'type' => 'number', 'title' => __('Progress'), 'search' => true, 'display' => true  ];

        $fields = self::customFields($fields, UPSTREAM_ITEM_TYPE_TASK);

        return $fields;
    }


    public static function getStatuses()
    {
        $option   = get_option('upstream_tasks');
        $statuses = isset($option['statuses']) ? $option['statuses'] : '';
        $array    = [];
        if ($statuses) {
            foreach ($statuses as $status) {
                $array[$status['id']] = $status['name'];
            }
        }

        return $array;
    }

    public static function create($parent, $title, $createdBy)
    {
        if (get_userdata($createdBy) === false)
            throw new UpStream_Model_ArgumentException(__('User ID does not exist.', 'upstream'));

        $item_metadata =
            ['title' => sanitize_text_field($title),
            'created_by' => $createdBy];

        return new self($parent, $item_metadata);
    }

}