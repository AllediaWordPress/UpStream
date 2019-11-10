<?php


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class UpStream_Model_Project extends UpStream_Model_Post_Object
{
    public $tasks = [];

    public $bugs = [];

    public $files = [];

    protected $clientUsers = [];

    public $client = 0;

    public $statusCode = null;

    /**
     * UpStream_Model_Project constructor.
     */
    public function __construct($id)
    {
        parent::__construct($id, [
            'clientUsers' => function($m) { return isset($m['_upstream_project_client_users'][0]) ? unserialize($m['_upstream_project_client_users'][0]) : []; },
            'client' => '_upstream_project_client',
            'statusCode' => '_upstream_project_status',
            'description' => '_upstream_project_description',
            'assignedTo' => function($m) { return isset($m['_upstream_project_owner']) ? $m['_upstream_project_owner'] : []; },
            ]);

        $this->type = UPSTREAM_ITEM_TYPE_PROJECT;
        $this->loadChildren();
        $this->categories = $this->loadCategories();
    }

    protected function loadChildren()
    {
        $itemset = get_post_meta($this->id, '_upstream_project_tasks');
        if ($itemset && count($itemset) == 1 && is_array($itemset[0])) {
            foreach ($itemset[0] as $item) {
                $this->tasks[] = new UpStream_Model_Task($item);
            }
        }

        $itemset = get_post_meta($this->id, '_upstream_project_bugs');
        if ($itemset && count($itemset) == 1 && is_array($itemset[0])) {
            foreach ($itemset[0] as $item) {
                $this->bugs[] = new UpStream_Model_Bug($item);
            }
        }

        $itemset = get_post_meta($this->id, '_upstream_project_files');
        if ($itemset && count($itemset) == 1 && is_array($itemset[0])) {
            foreach ($itemset[0] as $item) {
                $this->files[] = new UpStream_Model_File($item);
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

//        if ($this->progress > 0) update_post_meta($this->id, '_upstream_project_members', $this->progress);
//        if ($this->color != null) update_post_meta($this->id, '_upstream_project_activity', $this->color);
        if ($this->client > 0) update_post_meta($this->id, '_upstream_project_client', $this->client);
        if ($this->statusCode != null) update_post_meta($this->id, '_upstream_project_status', $this->statusCode);
        if (count($this->assignedTo) > 0) update_post_meta($this->id, '_upstream_project_owner', $this->assignedTo[0]);


        $this->storeCategories();
    }

}