<?php


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

class UpStream_Model_Post_Object extends UpStream_Model_Object
{
    
    public $categories = [];

    public $parentId = 0;

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
        $this->description = $post->post_content;

        foreach ($fields as $field => $input) {

            if (is_string($input)) {
                if (isset($metadata[$input])) {

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
                'post_title' => $this->title,
                'post_content' => $this->description
            ];

            $res = wp_update_post($post_arr, true);

        } else {
            $post_arr = [
                'post_title' => $this->title,
                'post_author' => $this->createdBy,
                'post_parent' => $this->parentId,
                'post_content' => $this->description
            ];

            $res = wp_insert_post($post_arr, true);
        }

        if ($res instanceof \WP_Error) {
            // todo THROW
        } else {
            $this->id = (int)$res;
        }

    }

}