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

    protected $reminders = [];

    protected $metadataKey = '_upstream_project_tasks';

    /**
     * UpStream_Model_Task constructor.
     */
    public function __construct($parent, $item_metadata)
    {
        parent::__construct($parent, $item_metadata);

        $this->type = UPSTREAM_ITEM_TYPE_TASK;
    }

    protected function loadFromArray($item_metadata)
    {
        parent::loadFromArray($item_metadata);

        $this->statusCode = !empty($item_metadata['status']) ? $item_metadata['status'] : null;
        $this->progress = !empty($item_metadata['progress']) ? $item_metadata['progress'] : null;
        $this->startDate = UpStream_Model_Object::loadDate($item_metadata, 'start_date');
        $this->endDate = UpStream_Model_Object::loadDate($item_metadata, 'end_date');
        $this->milestoneId = !empty($item_metadata['milestone']) ? $item_metadata['milestone'] : null;

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
        if ($this->progress >= 0) $item_metadata['progress'] = $this->progress;
        if ($this->startDate != null) $item_metadata['start_date'] = UpStream_Model_Object::ymdToTimestamp($this->startDate);
        if ($this->endDate != null) $item_metadata['end_date'] = UpStream_Model_Object::ymdToTimestamp($this->endDate);
        if ($this->startDate != null) $item_metadata['start_date__YMD'] = $this->startDate;
        if ($this->endDate != null) $item_metadata['end_date__YMD'] = $this->endDate;
        if ($this->milestoneId > 0) $item_metadata['milestone'] = $this->milestoneId;

        $item_metadata['reminders'] = [];

        foreach ($this->reminders as $reminder) {
            $r = [];
            $reminder->storeToArray($r);
            $item_metadata['reminders'][] = json_encode($r);
        }
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
                break;
            case 'notes':
                return $this->description;
            case 'milestoneId':
            case 'statusCode':
            case 'progress':
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
                if (!value instanceof UpStream_Model_Milestone)
                    throw new UpStream_Model_ArgumentException(__('Argument must be of type milestone.', 'upstream'));

                return $this->setMilestone($value);

            case 'status':
                break;

            case 'notes':
                $this->description = sanitize_textarea_field($value);
                break;

            case 'milestoneId':
                // TODO: check valid milestone
                $this->milestoneId = $value;
                break;

            case 'statusCode':
                // TODO: check status code
                break;

            case 'progress':
                if (!filter_var($value, FILTER_VALIDATE_INT) || (int)$value < 0 || (int)$value > 100)
                    throw new UpStream_Model_ArgumentException(__('Argument must be numeric and between 0 and 100.', 'upstream'));

                $this->{$property} = $value;
                break;

            case 'startDate':
            case 'endDate':
                if (!self::isValidDate($value))
                    throw new UpStream_Model_ArgumentException(__('Argument is not a valid date.', 'upstream'));

                $this->{$property} = $value;
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