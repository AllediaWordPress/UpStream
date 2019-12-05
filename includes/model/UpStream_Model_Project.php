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

    protected $startDate = null;

    protected $endDate = null;

    protected $clientUserIds = [];

    protected $clientId = 0;

    protected $statusCode = null;

    protected $postType = 'project';

    /**
     * UpStream_Model_Project constructor.
     */
    public function __construct($id)
    {
        if ($id > 0) {
            parent::__construct($id, [
                'clientUserIds' => function ($m) {
                    $arr = isset($m['_upstream_project_client_users'][0]) ? unserialize($m['_upstream_project_client_users'][0]) : null;
                    $arr = is_array($arr) ? $arr : [];
                    $arr = array_filter($arr);
                    return $arr;
                },
                'clientId' => '_upstream_project_client',
                'statusCode' => '_upstream_project_status',
                'description' => '_upstream_project_description',
                'startDate' => function ($m) {
                    if (!empty($m['_upstream_project_start__YMD'][0]))
                        return $m['_upstream_project_start__YMD'][0];
                    elseif (!empty($m['_upstream_project_start'][0]))
                        return UpStream_Model_Object::timestampToYMD($m['_upstream_project_start'][0]);
                },
                'endDate' => function ($m) {
                    if (!empty($m['_upstream_project_end__YMD'][0]))
                        return $m['_upstream_project_end__YMD'][0];
                    elseif (!empty($m['_upstream_project_end'][0]))
                        return UpStream_Model_Object::timestampToYMD($m['_upstream_project_end'][0]);
                },
                'assignedTo' => function ($m) {
                    return !empty($m['_upstream_project_owner'][0]) ? $m['_upstream_project_owner'] : [];
                },
            ]);

            $this->loadChildren();
            $this->loadCategories();
        } else {
            parent::__construct(0, []);
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

        $categoryIds = [];
        if (!isset($categories->errors)) {
            foreach ($categories as $category) {
                $categoryIds[] = $category->term_id;
            }
        }

        $this->categoryIds = $categoryIds;
    }


    protected function storeCategories()
    {
        if (is_project_categorization_disabled()) {
            return;
        }

        $res = wp_set_object_terms($this->id, $this->categoryIds, 'project_category');

        if ($res instanceof \WP_Error) {
            // TODO: throw
        }

    }

    public function store()
    {
        parent::store();

        if ($this->clientId > 0) update_post_meta($this->id, '_upstream_project_client', $this->clientId);
        if ($this->clientUserIds != null) update_post_meta($this->id, '_upstream_project_client_users', $this->clientUserIds);
        if ($this->statusCode != null) update_post_meta($this->id, '_upstream_project_status', $this->statusCode);
        if ($this->description != null) update_post_meta($this->id, '_upstream_project_description', $this->description);
        if (count($this->assignedTo) > 0) update_post_meta($this->id, '_upstream_project_owner', $this->assignedTo[0]);
        if ($this->startDate != null) update_post_meta($this->id, '_upstream_project_start__YMD', $this->startDate);
        if ($this->endDate != null) update_post_meta($this->id, '_upstream_project_end__YMD', $this->endDate);
        if ($this->startDate != null) update_post_meta($this->id, '_upstream_project_start', UpStream_Model_Object::ymdToTimestamp($this->startDate));
        if ($this->endDate != null) update_post_meta($this->id, '_upstream_project_end', UpStream_Model_Object::ymdToTimestamp($this->endDate));

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

        $projectObject = new UpStream_Project($this->id);
        $projectObject->update_project_meta();
    }

    public function addMetaObject($item)
    {
        if (!($item instanceof \UpStream_Model_Meta_Object))
            throw new UpStream_Model_ArgumentException(__('Can only add objects of type UpStream_Model_Meta_Objact', 'upstream'));
        elseif ($item instanceof UpStream_Model_Task)
            $this->tasks[] = $item;
        elseif ($item instanceof UpStream_Model_File)
            $this->files[] = $item;
        elseif ($item instanceof UpStream_Model_Bug)
            $this->bugs[] = $item;
    }

    public function addTask($title, $createdBy)
    {
        $item = \UpStream_Model_Task::create($this, $title, $createdBy);
        $this->tasks[] = $item;

        return $item;
    }

    public function addBug($title, $createdBy)
    {
        $item = \UpStream_Model_Bug::create($this, $title, $createdBy);
        $this->bugs[] = $item;

        return $item;
    }

    public function addFile($title, $createdBy)
    {
        $item = \UpStream_Model_File::create($this, $title, $createdBy);
        $this->files[] = $item;

        return $item;
    }

    public function __get($property)
    {
        switch ($property) {

            case 'status':
                $s = $this->getStatuses();

                foreach ($s as $sKey => $sValue) {
                    if ($this->statusCode === $sKey)
                        return $sValue;
                }
                return '';

            case 'statusCode':
            case 'clientId':
            case 'clientUserIds':
            case 'startDate':
            case 'endDate':
            case 'categoryIds':
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
        switch ($property) {

            case 'categoryIds':
                if (!is_array($value))
                    throw new UpStream_Model_ArgumentException(__('Category IDs must be an array.', 'upstream'));

                foreach ($value as $tid) {
                    $id = get_term_by('id', $tid, 'project_category');
                    if ($tid === false)
                        throw new UpStream_Model_ArgumentException(sprintf(__('Term ID %s is invalid.', 'upstream'), $tid));
                }

                $this->categoryIds = $value;

                break;

            case 'status':
                $s = $this->getStatuses();
                $sc = null;

                foreach ($s as $sKey => $sValue) {
                    if ($value === $sValue) {
                        $sc = $sKey;
                        break;
                    }
                }

                if ($sc == null)
                    throw new UpStream_Model_ArgumentException(sprintf(__('Status %s is invalid.', 'upstream'), $value));

                $this->statusCode = $sc;

                break;

            case 'statusCode':
                $s = $this->getStatuses();
                $sc = null;

                foreach ($s as $sKey => $sValue) {
                    if ($value === $sKey) {
                        $sc = $sKey;
                        break;
                    }
                }

                if ($sc == null)
                    throw new UpStream_Model_ArgumentException(sprintf(__('Status code %s is invalid.', 'upstream'), $value));

                $this->statusCode = $sc;

                break;

            case 'assignedTo':
                if (is_array($value) && count($value) != 1)
                    throw new UpStream_Model_ArgumentException(__('For projects, assignedTo must be an array of length 1.', 'upstream'));

                parent::__set($property, $value);
                break;

            case 'clientId':
                // this will throw a model exception if the client doesn't exist
                $client = \UpStream_Model_Manager::get_instance()->getByID(UPSTREAM_ITEM_TYPE_CLIENT, $value);
                $this->clientId = $client->id;
                break;

            case 'clientUserIds':
                if ($this->clientId == 0)
                    throw new UpStream_Model_ArgumentException(__('Cannot assign client users if the project has no client.', 'upstream'));

                if (!is_array($value))
                    throw new UpStream_Model_ArgumentException(__('Client user IDs must be an array.', 'upstream'));

                if (count(array_unique($value)) != count($value))
                        throw new UpStream_Model_ArgumentException(__('Input cannot contain duplicates.', 'upstream'));

                $client = \UpStream_Model_Manager::get_instance()->getByID(UPSTREAM_ITEM_TYPE_CLIENT, $this->clientId);
                for ($i = 0; $i < count($value); $i++) {
                    if (!$client->includesUser($value[$i]))
                        throw new UpStream_Model_ArgumentException(sprintf(__('User ID %s does not exist in this client.', 'upstream'), $value[$i]));
                }
                $this->clientUserIds = $value;

                break;

            case 'startDate':
            case 'endDate':
                if (!self::isValidDate($value))
                    throw new UpStream_Model_ArgumentException(__('Argument is not a valid date.', 'upstream'));

                $this->{$property} = $value;
                break;

            default:
                parent::__set($property, $value);
                break;

        }
    }

    public function findMilestones()
    {
        $posts = get_posts(
            [
                'post_type'      => 'upst_milestone',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_key'       => 'upst_project_id',
                'meta_value'     => $this->id,
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
            ]
        );

        $milestones = [];

        foreach ($posts as $post) {
            $milestone = \UpStream_Model_Manager::get_instance()->getByID(UPSTREAM_ITEM_TYPE_MILESTONE,
                $post->ID, UPSTREAM_ITEM_TYPE_PROJECT, $this->id);
            $milestones[] = $milestone;
        }

        return $milestones;
    }

    public function getStatuses()
    {
        $option   = get_option('upstream_projects');
        $statuses = isset($option['statuses']) ? $option['statuses'] : '';
        $array    = [];
        if ($statuses) {
            foreach ($statuses as $status) {
                if (isset($status['type'])) {
                    $array[$status['id']] = $status['name'];
                }
            }
        }

        return $array;
    }

    public static function create($title, $createdBy)
    {
        if (get_userdata($createdBy) === false)
            throw new UpStream_Model_ArgumentException(__('User ID does not exist.', 'upstream'));

        $item = new \UpStream_Model_Project(0);

        $item->title = sanitize_text_field($title);
        $item->createdBy = $createdBy;

        return $item;
    }

}