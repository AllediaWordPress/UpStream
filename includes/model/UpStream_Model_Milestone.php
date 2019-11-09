<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_Milestone extends UpStream_Model_Post_Object
{

    public $progress = 0;

    public $startDate = null;

    public $endDate = null;

    public $color = null;

    public $reminders = [];

    /**
     * UpStream_Model_Milestone constructor.
     */
    public function __construct($id)
    {
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

    public function store()
    {
        parent::store();

        if ($this->parentId > 0) update_post_meta($this->id, 'upst_project_id', $this->parentId);
        if ($this->progress > 0) update_post_meta($this->id, 'upst_progress', $this->progress);
        if ($this->color != null) update_post_meta($this->id, 'upst_color', $this->color);
        if ($this->startDate != null) update_post_meta($this->id, 'upst_start_date', $this->startDate);
        if ($this->endDate != null) update_post_meta($this->id, 'upst_end_date', $this->endDate);

        delete_post_meta($this->id, 'upst_assigned_to');
        foreach ($this->assignedTo as $a) add_post_meta($this->id, 'upst_assigned_to', $a);

        $this->storeCategories();
    }

}