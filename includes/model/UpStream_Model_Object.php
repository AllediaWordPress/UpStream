<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if (! defined('UPSTREAM_ITEM_TYPE_PROJECT')) {

    define('UPSTREAM_ITEM_TYPE_PROJECT', 'project');
    define('UPSTREAM_ITEM_TYPE_MILESTONE', 'milestone');
    define('UPSTREAM_ITEM_TYPE_TASK', 'task');
    define('UPSTREAM_ITEM_TYPE_BUG', 'bug');
    define('UPSTREAM_ITEM_TYPE_FILE', 'file');
    define('UPSTREAM_ITEM_TYPE_DISCUSSION', 'discussion');

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
        if (!preg_match('/^[a-zA-Z0-9]+$/', $id))
            throw new UpStream_Model_ArgumentException(sprintf(__('ID ..%s.. must be a valid alphanumeric.', 'upstream'), $id));

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
                throw new UpStream_Model_ArgumentException(sprintf(__('This (%s) is not a valid property.', 'upstream'), $property));
                break;

        }
    }

    public function __set($property, $value)
    {
        switch ($property) {

            case 'id':
                if (!preg_match('/^[a-zA-Z0-9]+$/', $value))
                    throw new UpStream_Model_ArgumentException(sprintf(__('ID %s must be a valid alphanumeric.', 'upstream'), $value));
                $this->{$property} = $value;
                break;

            case 'title':
                $this->{$property} = sanitize_text_field($value);
                break;

            case 'description':
                $this->{$property} = wp_kses_post($value);
                break;

            case 'assignedTo':
                if (!is_array($value))
                    throw new UpStream_Model_ArgumentException(sprintf(__('Assigned to must be an array.', 'upstream'), $value));

                foreach ($value as $uid) {
                    if (get_userdata($uid) === false)
                        throw new UpStream_Model_ArgumentException(sprintf(__('User ID %s does not exist.', 'upstream'), $uid));
                }

                $this->{$property} = $value;
                break;

            case 'createdBy':
                if (get_userdata($value) === false)
                    throw new UpStream_Model_ArgumentException(sprintf(__('User ID %s does not exist.', 'upstream'), $value));

                $this->{$property} = $value;
                break;

            default:
                throw new UpStream_Model_ArgumentException(sprintf(__('This (%s) is not a valid property.', 'upstream'), $property));
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
        // TODO: check timezones with this
        return date_create_from_format('Y-m-d', $ymd)->getTimestamp();
    }

    public static function isValidDate($ymd)
    {
        $d = DateTime::createFromFormat('Y-m-d', $ymd);
        return $d && $d->format('Y-m-d') == $ymd;
    }

}