<?php


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class UpStream_Model_Post_Object extends UpStream_Model_Object
{

    protected $categoryIds = [];

    protected $parentId = 0;

    protected $postType = 'post';

    /**
     * UpStream_Model_Post_Object constructor.
     */
    public function __construct($id, $fields)
    {
        parent::__construct($id);

        if ($id > 0) {
            $this->load($id, $fields);
        }
    }

    protected function load($id, $fields)
    {
        $post = get_post($id);

        if (!$post) {
            throw new UpStream_Model_ArgumentException(sprintf(__('Object with ID %s does not exist.', 'upstream'), $id));
        }

        $metadata = get_post_meta($id);
        if (empty($metadata)) {
            return;
        }

        if (!isset($post->post_title)) {
            throw new UpStream_Model_ArgumentException(sprintf(__('Object %s title does not exist.', 'upstream'), $id));
        }
        $this->title = $post->post_title;

        if (!isset($post->post_author)) {
            throw new UpStream_Model_ArgumentException(sprintf(__('Object %s author does not exist.', 'upstream'), $id));
        }
        $this->createdBy = $post->post_author;

        if (!isset($post->post_content)) {
            throw new UpStream_Model_ArgumentException(sprintf(__('Object %s content does not exist.', 'upstream'), $id));
        }
        $this->description = $post->post_content;

        foreach ($fields as $field => $input) {

            if (is_string($input)) {
                if (!empty($metadata[$input])) {

                    if (count($metadata[$input]) > 0) {
                        $this->{$field} = $metadata[$input][0];
                    }

                }
            } else if ($input instanceof Closure) {
                $this->{$field} = $input($metadata);
            }
        }

        $dataToLoad = [];
        foreach ($metadata as $key => $val) {
            if (is_array($val)) {
                $dataToLoad[$key] = $val[0];
            }
        }

        $this->additionaFields = apply_filters('upstream_model_load_fields', $this->additionaFields, $dataToLoad,
            $this->type, $this->id);
    }

    protected function store()
    {

        $res = null;

        if ($this->id > 0) {

            $post_arr = [
                'ID' => $this->id,
                'post_title' => ($this->title == null ? '(New Item)' : $this->title),
                'post_content' => ($this->description == null ? '' : $this->description)
            ];

            $res = wp_update_post($post_arr, true);

        } else {
            $post_arr = [
                'post_title' => ($this->title == null ? '(New Item)' : $this->title),
                'post_author' => $this->createdBy,
                'post_parent' => $this->parentId,
                'post_content' => ($this->description == null ? '' : $this->description),
                'post_status' => 'publish',
                'post_type' => $this->postType
            ];

            $res = wp_insert_post($post_arr, true);
        }

        if ($res instanceof \WP_Error) {
            throw new UpStream_Model_ArgumentException(sprintf(__('Could not load post with ID %s.', 'upstream'), $this->id));
        } else {
            $this->id = (int)$res;
        }

        $dataToStore = [];
        $dataToStore = apply_filters('upstream_model_store_fields', $dataToStore, $this->additionaFields,
            $this->type, $this->id);

        foreach ($dataToStore as $key => $value) {
            update_post_meta($this->id, $key, $value);
        }

    }

    public function __get($property)
    {
        switch ($property) {

            case 'parentId':
                return $this->{$property};

            default:
                return parent::__get($property);

        }
    }

    public function __set($property, $value)
    {
        switch ($property) {

            case 'id':
                if (!filter_var($value, FILTER_VALIDATE_INT))
                    throw new UpStream_Model_ArgumentException(__('ID must be a valid numeric.', 'upstream'));
                $this->{$property} = $value;
                break;

            default:
                parent::__set($property, $value);
                break;

        }
    }

}