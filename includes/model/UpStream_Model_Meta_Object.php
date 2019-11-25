<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_Meta_Object extends UpStream_Model_Object
{
    protected $parent = null;

    protected $metadataKey = null;

    /**
     * UpStream_Model_Meta_Object constructor.
     */
    public function __construct($parent, $item_metadata)
    {
        parent::__construct();

        $this->parent = $parent;
        $this->loadFromArray($item_metadata);
    }

    protected function loadFromArray($item_metadata)
    {

        $this->id = !empty($item_metadata['id']) ? $item_metadata['id'] : 0;
        $this->title = !empty($item_metadata['title']) ? $item_metadata['title'] : null;
        $this->assignedTo = !empty($item_metadata['assigned_to']) ? $item_metadata['assigned_to'] : [];
        $this->createdBy = !empty($item_metadata['created_by']) ? $item_metadata['created_by'] : [];
        $this->description = !empty($item_metadata['description']) ? $item_metadata['description'] : null;

    }

    public function storeToArray(&$item_metadata)
    {

        if (!($this->parent instanceof UpStream_Model_Post_Object)) {
            // TODO: throw error
        }

        if ($this->id == 0) {
            $this->id = uniqid(get_current_user_id());
        }
        $item_metadata['id'] = $this->id;

        if ($this->title != null) $item_metadata['title'] = $this->title;
        if (count($this->assignedTo) > 0) $item_metadata['assigned_to'] = $this->assignedTo;
        if ($this->createdBy > 0) $item_metadata['created_by'] = $this->createdBy;
        if ($this->description != null) $item_metadata['description'] = $this->description;

    }

    public function store()
    {
        if (!($this->parent instanceof UpStream_Model_Post_Object)) {
            // TODO: throw error
        }

        $added = false;

        $new_item = [];
        $this->storeToArray($new_item);

        $itemset = get_post_meta($this->parent->id, $this->metadataKey);
        if ($itemset && count($itemset) == 1 && is_array($itemset[0])) {
            // it's ok
        } else {
            $itemset = [];
        }

        for ($i = 0; $i < count($itemset[0]); $i++) {
            if ($itemset[0][$i]['id'] == $this->id) {

                $itemset[0][$i] = $new_item;
                $added = true;
                break;

            }
        }

        if (!$added) {
            $itemset[0][] = $new_item;
        }

        update_post_meta($this->parent->id, $this->metadataKey, $itemset[0]);

    }

    public function __get($property)
    {
        switch ($property) {

            default:
                return parent::__get($property);
        }
    }

    public function __set($property, $value)
    {
        switch ($property) {

            default:
                parent::__set($property, $value);
                break;

        }
    }

}