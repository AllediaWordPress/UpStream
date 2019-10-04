<?php


class UpStream_Model_Project extends UpStream_Model_Post_Object
{
    public $tasks = [];

    public $bugs = [];

    protected $clientUsers = [];

    protected $client = 0;

    /**
     * UpStream_Model_Project constructor.
     */
    public function __construct($id)
    {
        parent::__construct($id, [
            'clientUsers' => function($m) { return isset($m['_upstream_project_client_users'][0]) ? unserialize($m['_upstream_project_client_users'][0]) : []; },
            'client' => function($m) { return isset($m['_upstream_project_client'][0]) ? $m['_upstream_project_client'][0] : 0; },
            'assignedTo' => '_upstream_project_owner',
            ]);

        $this->type = UPSTREAM_ITEM_TYPE_PROJECT;
        $this->loadChildren();
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

    }
}