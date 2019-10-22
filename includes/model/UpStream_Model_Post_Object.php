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
        $this->created_by = $post->post_author;

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
}