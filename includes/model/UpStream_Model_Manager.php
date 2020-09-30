<?php


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class UpStream_Model_Manager
{
    protected static $instance;

    protected $objects = [];

    public function reset()
    {
        $this->objects = [];
    }

    public function getByID($object_type, $object_id, $parent_type = null, $parent_id = 0)
    {
        if (!in_array($object_type, [ UPSTREAM_ITEM_TYPE_CLIENT, UPSTREAM_ITEM_TYPE_PROJECT, UPSTREAM_ITEM_TYPE_MILESTONE, UPSTREAM_ITEM_TYPE_TASK, UPSTREAM_ITEM_TYPE_BUG, UPSTREAM_ITEM_TYPE_FILE ])) {
            throw new \UpStream_Model_ArgumentException(sprintf(__('Item type %s is not valid.', 'upstream'), $object_type));
        } else if (!$parent_id && in_array($object_type, [ UPSTREAM_ITEM_TYPE_TASK, UPSTREAM_ITEM_TYPE_BUG, UPSTREAM_ITEM_TYPE_FILE ])) {

        }

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
        if (UPSTREAM_ITEM_TYPE_PROJECT === $object_type) {

            $project = new UpStream_Model_Project($object_id);
            $this->objects[$object_type][$object_id] = $project;

            foreach ($project->tasks() as $item) {
                $this->objects[UPSTREAM_ITEM_TYPE_TASK][$item->id] = $item;
            }

            foreach ($project->bugs() as $item) {
                $this->objects[UPSTREAM_ITEM_TYPE_BUG][$item->id] = $item;
            }

            foreach ($project->files() as $item) {
                $this->objects[UPSTREAM_ITEM_TYPE_FILE][$item->id] = $item;
            }

        } else if (UPSTREAM_ITEM_TYPE_MILESTONE === $object_type) {
            $this->objects[$object_type][$object_id] = new UpStream_Model_Milestone($object_id);
        } else if (UPSTREAM_ITEM_TYPE_CLIENT === $object_type) {
            $this->objects[$object_type][$object_id] = new UpStream_Model_Client($object_id);
        } else if (UPSTREAM_ITEM_TYPE_TASK === $object_type) {
            $this->loadObject($parent_type, $parent_id, null, null);
        } else if (UPSTREAM_ITEM_TYPE_BUG === $object_type) {
            $this->loadObject($parent_type, $parent_id, null, null);
        } else if (UPSTREAM_ITEM_TYPE_FILE === $object_type) {
            $this->loadObject($parent_type, $parent_id, null, null);
        }

    }

    public function loadAll()
    {
        $posts = get_posts([
            'post_type' => 'project',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);

        foreach ($posts as $post) {
            $this->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $post->ID);
        }

        $posts = get_posts([
            'post_type' => 'upst_milestone',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);

        foreach ($posts as $post) {
            $this->getByID(UPSTREAM_ITEM_TYPE_MILESTONE, $post->ID);
        }

        $posts = get_posts([
            'post_type' => 'client',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);

        foreach ($posts as $post) {
            $this->getByID(UPSTREAM_ITEM_TYPE_CLIENT, $post->ID);
        }
    }

    public function findAllByCallback($callback)
    {
        $return_items = [];

        if (count($this->objects) == 0) {
            $this->loadAll();
        }

        foreach ($this->objects as $type => $items) {
            foreach ($items as $id => $item) {
                $r = $callback($item);
                if ($r) {
                    $return_items[] = $item;
                }
            }
        }

        return $return_items;
    }

    public function findAccessibleProjects()
    {
        $return_items = [];

        if (count($this->objects) == 0) {
            $this->loadAll();
        }

        foreach ($this->objects[UPSTREAM_ITEM_TYPE_PROJECT] as $id => $item) {
            $access = upstream_can_access_object('view_project', UPSTREAM_ITEM_TYPE_PROJECT,
                $id, null, 0, UPSTREAM_PERMISSIONS_ACTION_VIEW);
            if ($access) {
                $return_items[] = $item;
            }
        }

        usort($return_items, function($item1, $item2) { return strcasecmp($item1->title, $item2->title); });

        return $return_items;

    }

    public function createObject($object_type, $title, $createdBy, $parentId = 0)
    {
        switch ($object_type) {

            case UPSTREAM_ITEM_TYPE_PROJECT:
                return \UpStream_Model_Project::create($title, $createdBy);

            case UPSTREAM_ITEM_TYPE_MILESTONE:
                return \UpStream_Model_Milestone::create($title, $createdBy, $parentId);

            case UPSTREAM_ITEM_TYPE_CLIENT:
                return \UpStream_Model_Client::create($title, $createdBy);

            case UPSTREAM_ITEM_TYPE_TASK:
                $parent = $this->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $parentId);
                return \UpStream_Model_Task::create($parent, $title, $createdBy);

            case UPSTREAM_ITEM_TYPE_FILE:
                $parent = $this->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $parentId);
                return \UpStream_Model_File::create($parent, $title, $createdBy);

            case UPSTREAM_ITEM_TYPE_BUG:
                $parent = $this->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $parentId);
                return \UpStream_Model_Bug::create($parent, $title, $createdBy);

            default:
                throw new \UpStream_Model_ArgumentException(sprintf(__('Item type %s is not valid.', 'upstream'), $object_type));
        }
    }

    public function deleteObject($object_type, $object_id, $parent_type, $parent_id)
    {
        // throws exception if the object doesn't exist...
        $obj = $this->getByID($object_type, $object_id, $parent_type, $parent_id);

        switch ($object_type) {

            case UPSTREAM_ITEM_TYPE_PROJECT:
                wp_delete_post($object_id);
                break;

            case UPSTREAM_ITEM_TYPE_MILESTONE:
                if (class_exists('\UpStream\Factory')) {
                    $milestone = \UpStream\Factory::getMilestone($object_id);

                    if ( ! empty($milestone)) {
                        $milestone->delete();
                    }
                }
                break;

            case UPSTREAM_ITEM_TYPE_CLIENT:

            case UPSTREAM_ITEM_TYPE_TASK:

            case UPSTREAM_ITEM_TYPE_FILE:

            case UPSTREAM_ITEM_TYPE_BUG:

            default:
                throw new \UpStream_Model_ArgumentException(sprintf(__('Item type %s is not valid.', 'upstream'), $object_type));
        }

        unset($this->objects[$object_type][$object_id]);

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