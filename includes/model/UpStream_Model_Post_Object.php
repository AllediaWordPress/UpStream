<?php


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class UpStream_Model_Post_Object extends UpStream_Model_Object
{
    
    protected $categories = [];

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
        $metadata = get_post_meta($id);

        $this->title = $post->post_title;
        $this->createdBy = $post->post_author;
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
            // todo THROW
        } else {
            $this->id = (int)$res;
        }

    }

    public function __get($property)
    {
        switch ($property) {

            case 'categories':
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