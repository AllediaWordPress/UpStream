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

    public $description = '';


    /**
     * UpStream_Model_Object constructor.
     * @param $id
     */
    public function __construct($id = 0)
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

    public static function timestampToYMD($timestamp)
    {
	    $offset = get_option( 'gmt_offset' );
	    $sign = $offset < 0 ? '-' : '+';
	    $hours = (int) $offset;
	    $minutes = abs( ( $offset - (int) $offset ) * 60 );
	    $offset = (int)sprintf( '%s%d%02d', $sign, abs( $hours ), $minutes );
	    $calc_offset_seconds = $offset < 0 ? $offset * -1 * 60 : $offset * 60;

        $date = date_i18n('Y-m-d', $timestamp + $calc_offset_seconds);
        return $date;
    }

    public static function ymdToTimestamp($ymd)
    {
        return date_create_from_format('Y-m-d', $ymd);
    }

}