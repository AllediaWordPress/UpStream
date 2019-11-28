<?php


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class UpStream_Model_Manager
{
    const PROJECT = "project";
    const MILESTONE = "milestone";
    const TASK = "task";
    const BUG = "bug";
    const FILE = "file";

    protected static $instance;

    protected $objects = [];

    public function getByID($object_type, $object_id, $parent_type = null, $parent_id = 0)
    {
        if (empty($this->objects[$object_type]) || empty($this->objects[$object_type][$object_id])) {
            $this->loadObject($object_type, $object_id, $parent_type, $parent_id);
        }

        if (empty($this->objects[$object_type][$object_id])) {
            throw new UpStream_Model_ArgumentException(sprintf(__('This (ID = %s, TYPE = %s, PARENT ID = %s, PARENT TYPE = %s) is not a valid object', 'upstream'), $object_id, $object_type, $parent_id, $parent_type));
        }

        return $this->objects[$object_type][$object_id];
    }

    protected function loadObject($object_type, $object_id, $parent_type, $parent_id)
    {
        // TODO: add exceptions
        if (self::PROJECT === $object_type) {

            $project = new UpStream_Model_Project($object_id);
            $this->objects[$object_type][$object_id] = $project;

            foreach ($project->tasks as $item) {
                $this->objects[self::TASK][$item->id] = $item;
            }

            foreach ($project->bugs as $item) {
                $this->objects[self::BUG][$item->id] = $item;
            }

            foreach ($project->files as $item) {
                $this->objects[self::FILE][$item->id] = $item;
            }

        } else if (self::MILESTONE === $object_type) {
            $this->objects[$object_type][$object_id] = new UpStream_Model_Milestone($object_id);
        } else if (self::TASK === $object_type) {
            $this->loadObject($parent_type, $parent_id, null, null);
        } else if (self::BUG === $object_type) {
            $this->loadObject($parent_type, $parent_id, null, null);
        } else if (self::FILE === $object_type) {
            $this->loadObject($parent_type, $parent_id, null, null);
        }

    }

    public function createObject($object_type, $title, $createdBy, $parentId = 0)
    {
        switch ($object_type) {

            case UPSTREAM_ITEM_TYPE_PROJECT:
                return \UpStream_Model_Project::create($title, $createdBy);

            case UPSTREAM_ITEM_TYPE_MILESTONE:
                return \UpStream_Model_Milestone::create($title, $createdBy, $parentId);

            case UPSTREAM_ITEM_TYPE_TASK:
                $parent = $this->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $parentId);
                return \UpStream_Model_Task::create($parent, $title, $createdBy);

            case UPSTREAM_ITEM_TYPE_FILE:
                $parent = $this->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $parentId);
                return \UpStream_Model_File::create($parent, $title, $createdBy);

            case UPSTREAM_ITEM_TYPE_BUG:
                $parent = $this->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $parentId);
                return \UpStream_Model_Bug::create($parent, $title, $createdBy);

        }
    }

    public static function get_instance()
    {
        if (empty(static::$instance)) {
            $instance = new self;
            static::$instance = $instance;
        }

        return static::$instance;
    }

}