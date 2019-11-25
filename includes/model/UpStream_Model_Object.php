<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_Object
{

    protected $id = 0;

    protected $type = null;

    protected $title = null;

    protected $assignedTo = [];

    protected $createdBy = 0;

    protected $description = null;


    /**
     * UpStream_Model_Object constructor.
     * @param $id
     */
    public function __construct($id = 0)
    {
        if (!ctype_alnum($id))
            throw new UpStream_Model_ArgumentException(__('ID must be a valid alphanumeric.', 'upstream'));

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

    public function __get($property)
    {
        switch ($property) {

            case 'id':
            case 'title':
            case 'assignedTo':
            case 'createdBy':
            case 'description':
                return $this->{$property};
            default:
                throw new UpStream_Model_ArgumentException(__('This is not a valid property.', 'upstream'));
                break;

        }
    }

    public function __set($property, $value)
    {
        switch ($property) {

            case 'id':
                if (!ctype_alnum($value))
                    throw new UpStream_Model_ArgumentException(__('ID must be a valid alphanumeric.', 'upstream'));
                $this->{$property} = $value;
                break;

            case 'title':
                $this->{$property} = sanitize_text_field($value);
                break;

            case 'description':
                $this->{$property} = sanitize_textarea_field($value);
                break;

            case 'assignedTo':
                // TODO: check if the user exists
                $this->{$property} = $value;
                break;

            case 'createdBy':
                // TODO: check if the user exists
                $this->{$property} = $value;
                break;

            default:
                throw new UpStream_Model_ArgumentException(__('This is not a valid property.', 'upstream'));
                break;

        }
    }

    public static function loadDate($data, $field)
    {
        if (!empty($data[$field . '__YMD'])) {
            return $data[$field . '__YMD'];
        } else if (!empty($data[$field])) {
            return self::timestampToYMD($data[$field]);
        }
        return null;
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

    public static function isValidDate($ymd)
    {
        $matches = array();
        $pattern = '/^([0-9]{1,2})\\/([0-9]{1,2})\\/([0-9]{4})$/';

        if (!preg_match($pattern, $ymd, $matches))
            return false;

        if (!checkdate($matches[2], $matches[1], $matches[3]))
            return false;

        return true;
    }

}