<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_Milestone extends UpStream_Model_Post_Object
{

    protected $progress = 0;

    protected $startDate = null;

    protected $endDate = null;

    protected $color = null;

    protected $reminders = [];

    protected $postType = 'upst_milestone';

    /**
     * UpStream_Model_Milestone constructor.
     */
    public function __construct($id)
    {
        if ($id > 0) {
            parent::__construct($id, [
                'progress' => 'upst_progress',
                'color' => 'upst_color',
                'startDate' => 'upst_start_date',
                'endDate' => 'upst_end_date',
                'parentId' => 'upst_project_id'
            ]);

            $this->categories = $this->loadCategories();

            $res = get_post_meta($id, 'upst_assigned_to');
            foreach ($res as $r) $this->assignedTo[] = (int)$r;

            $res = get_post_meta($id, 'upst_reminders');
            if (!empty($res)) {
                foreach ($res as $reminder_data) {
                    $reminder = new UpStream_Model_Reminder((array)$reminder_data);
                    $this->reminders[] = $reminder;
                }
            }
        }

        $this->type = UPSTREAM_ITEM_TYPE_MILESTONE;
    }

    protected function loadCategories()
    {
        if (upstream_disable_milestone_categories()) {
            return [];
        }

        $categories = wp_get_object_terms($this->id, 'upst_milestone_category');

        if (isset($this->categories->errors)) {
            return [];
        }

        return $categories;
    }

    protected function storeCategories()
    {
        if (upstream_disable_milestone_categories()) {
            return;
        }

        $res = wp_set_object_terms($this->id, $this->categories, 'upst_milestone_category');

        if ($res instanceof \WP_Error) {
            // TODO: throw
        }

    }

    public function getProject()
    {
        if ($this->parentId) {
            try {
                return \UpStream_Model_Manager::get_instance()->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $this->parentId);
            } catch (\Exception $e) {

            }
        }

        return null;
    }

    public function store()
    {
        parent::store();

        if ($this->parentId > 0) update_post_meta($this->id, 'upst_project_id', $this->parentId);
        if ($this->progress > 0) update_post_meta($this->id, 'upst_progress', $this->progress);
        if ($this->color != null) update_post_meta($this->id, 'upst_color', $this->color);
        if ($this->startDate != null) update_post_meta($this->id, 'upst_start_date', $this->startDate);
        if ($this->endDate != null) update_post_meta($this->id, 'upst_end_date', $this->endDate);
        if ($this->startDate != null) update_post_meta($this->id, 'upst_start_date__YMD', $this->startDate);
        if ($this->endDate != null) update_post_meta($this->id, 'upst_end_date__YMD', $this->endDate);

        delete_post_meta($this->id, 'upst_assigned_to');
        foreach ($this->assignedTo as $a) add_post_meta($this->id, 'upst_assigned_to', $a);

        $this->storeCategories();
    }

    public function __get($property)
    {
        switch ($property) {

            case 'progress':
            case 'startDate':
            case 'endDate':
            case 'color':
                return $this->{$property};
            default:
                return parent::__get($property);

        }
    }

    public function __set($property, $value)
    {
        // TODO: add checks
        switch ($property) {

            case 'progress':
            case 'startDate':
            case 'endDate':
            case 'color':
                $this->{$property} = $value;
                break;
            default:
                parent::__set($property, $value);
                break;

        }
    }


    public static function create($title, $createdBy)
    {
        $item = new \UpStream_Model_Milestone(0);

        $item->title = $title;
        $item->createdBy = $createdBy;

        return $item;
    }

}