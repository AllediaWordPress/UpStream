<?php


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class UpStream_Model_Project extends UpStream_Model_Post_Object
{
    protected $tasks = [];

    protected $bugs = [];

    protected $files = [];

    protected $clientUsers = [];

    protected $client = 0;

    protected $statusCode = null;

    protected $postType = 'project';

    /**
     * UpStream_Model_Project constructor.
     */
    public function __construct($id)
    {
        if ($id > 0) {
            parent::__construct($id, [
                'clientUsers' => function ($m) {
                    return isset($m['_upstream_project_client_users'][0]) ? unserialize($m['_upstream_project_client_users'][0]) : [];
                },
                'client' => '_upstream_project_client',
                'statusCode' => '_upstream_project_status',
                'description' => '_upstream_project_description',
                'assignedTo' => function ($m) {
                    return !empty($m['_upstream_project_owner'][0]) ? $m['_upstream_project_owner'] : [];
                },
            ]);

            $this->loadChildren();
            $this->categories = $this->loadCategories();
        }

        $this->type = UPSTREAM_ITEM_TYPE_PROJECT;
    }

    protected function loadChildren()
    {
        $itemset = get_post_meta($this->id, '_upstream_project_tasks');
        if ($itemset && count($itemset) == 1 && is_array($itemset[0])) {
            foreach ($itemset[0] as $item) {
                $this->tasks[] = new UpStream_Model_Task($this, $item);
            }
        }

        $itemset = get_post_meta($this->id, '_upstream_project_bugs');
        if ($itemset && count($itemset) == 1 && is_array($itemset[0])) {
            foreach ($itemset[0] as $item) {
                $this->bugs[] = new UpStream_Model_Bug($this, $item);
            }
        }

        $itemset = get_post_meta($this->id, '_upstream_project_files');
        if ($itemset && count($itemset) == 1 && is_array($itemset[0])) {
            foreach ($itemset[0] as $item) {
                $this->files[] = new UpStream_Model_File($this, $item);
            }
        }
    }


    protected function loadCategories()
    {
        if (is_project_categorization_disabled()) {
            return [];
        }

        $categories = wp_get_object_terms($this->id, 'project_category');

        if (isset($this->categories->errors)) {
            return [];
        }

        return $categories;
    }


    protected function storeCategories()
    {
        if (is_project_categorization_disabled()) {
            return;
        }

        $res = wp_set_object_terms($this->id, $this->categories, 'project_category');

        if ($res instanceof \WP_Error) {
            // TODO: throw
        }

    }


    public function store()
    {
        parent::store();

        if ($this->client > 0) update_post_meta($this->id, '_upstream_project_client', $this->client);
        if ($this->statusCode != null) update_post_meta($this->id, '_upstream_project_status', $this->statusCode);
        if (count($this->assignedTo) > 0) update_post_meta($this->id, '_upstream_project_owner', $this->assignedTo[0]);

        $items = [];
        foreach ($this->tasks as $item) {
            $r = [];
            $item->storeToArray($r);
            $items[] = $r;
        }
        update_post_meta($this->id, '_upstream_project_tasks', $items);

        $items = [];
        foreach ($this->bugs as $item) {
            $r = [];
            $item->storeToArray($r);
            $items[] = $r;
        }
        update_post_meta($this->id, '_upstream_project_bugs', $items);

        $items = [];
        foreach ($this->files as $item) {
            $r = [];
            $item->storeToArray($r);
            $items[] = $r;
        }
        update_post_meta($this->id, '_upstream_project_files', $items);

        $this->storeCategories();
    }

    public function addTask($title, $createdBy)
    {
        $item = \UpStream_Model_Task::create($this, $title, $createdBy);
        $this->tasks[] = $item;

        return $item;
    }

    public function addBug($title, $createdBy)
    {
        $item = \UpStream_Model_File::create($this, $title, $createdBy);
        $this->bug[] = $item;

        return $item;
    }

    public function addFile($title, $createdBy)
    {
        $item = \UpStream_Model_File::create($this, $title, $createdBy);
        $this->file[] = $item;

        return $item;
    }

    public function __get($property)
    {
        switch ($property) {

            case 'status':
                break;
            case 'statusCode':
            case 'client':
            case 'clientUsers':
            case 'tasks':
            case 'bugs':
            case 'files':
                return $this->{$property};
            default:
                return parent::__get($property);

        }
    }

    public function __set($property, $value)
    {
        // TODO: add checks
        switch ($property) {

            case 'status':
                break;
            case 'statusCode':
            case 'client':
            case 'clientUsers':
            case 'description':
                $this->{$property} = $value;
                break;
            default:
                parent::__set($property, $value);
                break;

        }
    }

    public function findMilestones()
    {

    }

    public static function create($title, $createdBy)
    {
        $item = new \UpStream_Model_Project(0);

        $item->title = $title;
        $item->createdBy = $createdBy;

        return $item;
    }

}