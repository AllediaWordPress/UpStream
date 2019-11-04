<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_Object
{

    public $id = 0;

    public $type = null;

    public $title = null;

    public $assignedTo = [];

    public $createdBy = 0;

    /**
     * UpStream_Model_Object constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @param $user_id The user_id of the user to check
     * @return bool True if this object is assigned to user_id, or false otherwise
     */
    public function isAssignedTo($user_id)
    {
        foreach ($this->assignedTo as $a) {
            if ($a == $user_id) {
                return true;
            }
        }
        return false;
    }

}