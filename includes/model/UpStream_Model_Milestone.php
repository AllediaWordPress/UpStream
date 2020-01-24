<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_Milestone extends UpStream_Model_Post_Object
{

    protected $progress = 0;

    protected $startDate = null;

    protected $endDate = null;

    protected $color = null;

    protected $reminders = [];

    protected $postType = 'upst_milestone';

    protected $type = UPSTREAM_ITEM_TYPE_MILESTONE;

    /**
     * UpStream_Model_Milestone constructor.
     */
    public function __construct($id)
    {
        if ($id > 0) {
            parent::__construct($id, [
                'progress' => 'upst_progress',
                'color' => 'upst_color',
                'startDate' => 'upst_start_date',
                'endDate' => 'upst_end_date',
                'parentId' => 'upst_project_id'
            ]);

            $this->loadCategories();

            $res = get_post_meta($id, 'upst_assigned_to');
            foreach ($res as $r) $this->assignedTo[] = (int)$r;

            $res = get_post_meta($id, 'upst_reminders');
            if (!empty($res)) {
                foreach ($res as $reminder_data) {
                    $reminder = new UpStream_Model_Reminder((array)$reminder_data);
                    $this->reminders[] = $reminder;
                }
            }
        } else {
            parent::__construct(0, []);
        }

        $this->type = UPSTREAM_ITEM_TYPE_MILESTONE;
    }

    protected function loadCategories()
    {
        if (upstream_disable_milestone_categories()) {
            return [];
        }

        $categories = wp_get_object_terms($this->id, 'upst_milestone_category');

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
        if (upstream_disable_milestone_categories()) {
            return;
        }

        $res = wp_set_object_terms($this->id, $this->categoryIds, 'upst_milestone_category');

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
        if ($this->startDate != null) update_post_meta($this->id, 'upst_start_date__YMD', $this->startDate);
        if ($this->endDate != null) update_post_meta($this->id, 'upst_end_date__YMD', $this->endDate);

        delete_post_meta($this->id, 'upst_assigned_to');
        foreach ($this->assignedTo as $a) add_post_meta($this->id, 'upst_assigned_to', $a);

        $this->storeCategories();
    }

    public function __get($property)
    {
        switch ($property) {

            case 'notes':
                return $this->description;

            case 'progress':
                // TODO: handle progress calc
                break;

            case 'categoryIds':
            case 'startDate':
            case 'endDate':
            case 'color':
                return $this->{$property};

	        case 'categories':
		        $categories = [];
		        foreach ($this->categoryIds as $tid) {
			        $term = get_term_by('id', $tid, 'upst_milestone_category');
			        $categories[] = $term;
		        }
		        return $categories;

            default:
                return parent::__get($property);

        }
    }

    public function __set($property, $value)
    {
        switch ($property) {

            case 'parentId':
                $project = \UpStream_Model_Manager::get_instance()->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $value);
                $this->parentId = $project->id;
                break;

            case 'categoryIds':
                if (!is_array($value))
                    $value = [$value];

                $categoryIds = [];
                foreach ($value as $tid) {
                    $term = get_term_by('id', $tid, 'upst_milestone_category');
                    if ($term === false)
                        throw new UpStream_Model_ArgumentException(sprintf(__('Term ID %s is invalid.', 'upstream'), $tid));
                    $categoryIds[] = $term->term_id;
                }

                $this->categoryIds = $categoryIds;

                break;

            case 'startDate':
            case 'endDate':
                if (!self::isValidDate($value))
                    throw new UpStream_Model_ArgumentException(__('Argument is not a valid date.', 'upstream'));

                $this->{$property} = $value;
                break;

            case 'color':
                if (!preg_match('/\#[a-zA-Z0-9]{6}/', $value))
                    throw new UpStream_Model_ArgumentException(sprintf(__('%s is not a valid hex string.', 'upstream'), $value));

                $this->{$property} = $value;
                break;

            case 'notes':
                $this->description = wp_kses_post($value);
                break;

            default:
                parent::__set($property, $value);
                break;

        }
    }


    public static function create($title, $createdBy, $parentId = 0)
    {
        if (get_userdata($createdBy) === false)
            throw new UpStream_Model_ArgumentException(__('User ID does not exist.', 'upstream'));

        $item = new \UpStream_Model_Milestone(0);

        $item->title = sanitize_text_field($title);
        $item->createdBy = $createdBy;

        if ($parentId > 0) {
            $project = \UpStream_Model_Manager::get_instance()->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $parentId);
            $item->parentId = $project->id;
        }

        return $item;
    }

}