<?php


class UpStream_Model_Milestone extends UpStream_Model_Post_Object
{

    /**
     * UpStream_Model_Milestone constructor.
     */
    public function __construct($id)
    {
        parent::__construct($id, [
            'assignedTo' => 'upst_assigned_to',
            ]);
    }
}