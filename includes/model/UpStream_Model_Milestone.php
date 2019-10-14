<?php


class UpStream_Model_Milestone extends UpStream_Model_Post_Object
{

    /**
     * UpStream_Model_Milestone constructor.
     */
    public function __construct($id)
    {
        parent::__construct($id, [
            'assignedTo' => 'upst_assigned_to',
            ]);

        $this->categories = $this->loadCategories();

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
}