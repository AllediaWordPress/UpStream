<?php


class UpStream_Model_Manager
{
    const PROJECT = "project";
    const MILESTONE = "milestone";
    const TASK = "task";
    const BUG = "bug";

    protected static $instance;

    protected $objects = [];

    public function getByID($object_type, $object_id, $parent_id = 0)
    {
        if (!isset($this->objects[$object_type]) || !isset($this->objects[$object_type][$object_id])) {
            $this->loadObject($object_type, $object_id, $parent_id);
        }

        return $this->objects[$object_type][$object_id];
    }

    protected function loadObject($object_type, $object_id, $parent_id)
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

        } else if (self::MILESTONE === $object_type) {
            $this->objects[$object_type][$object_id] = new UpStream_Model_Milestone($object_id);
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