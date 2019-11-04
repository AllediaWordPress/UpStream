<?php


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class UpStream_Model_Post_Object extends UpStream_Model_Object
{
    
    public $categories = [];

    /**
     * UpStream_Model_Post_Object constructor.
     */
    public function __construct($id, $fields)
    {
        parent::__construct($id);

        $this->load($id, $fields);
    }

    protected function load($id, $fields)
    {
        $post = get_post($id);
        $metadata = get_post_meta($id);

        $this->title = $post->post_title;
        $this->createdBy = $post->post_author;

        foreach ($fields as $field => $input) {

            if (is_string($input)) {
                if (isset($metadata[$input])) {
                    $this->{$field} = $metadata[$input];
                }
            } else if ($input instanceof Closure) {
                $this->{$field} = $input($metadata);
            }

        }
    }
/*
    protected function store()
    {

        // insert or update the post
        $post_arr = [
            'ID' => $this->id,
            'post_title' => $this->title,
            'post_author' => $this->createdBy
        ];

        $post_arr = array_merge($post_arr, $addl_post_arr);
        $res = wp_insert_post($post_arr, true);

        if ($res instanceof \WP_Error) {
            // todo THROW
        }

    }

    protected function deleteMeta($key)
    {
        delete_post_meta($this->id, $key);
    }

    protected function updateMultipleMeta($key, $value)
    {

    }
*/
}