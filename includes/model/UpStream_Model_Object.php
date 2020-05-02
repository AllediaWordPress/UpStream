<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if (! defined('UPSTREAM_ITEM_TYPE_PROJECT')) {

    define('UPSTREAM_ITEM_TYPE_PROJECT', 'project');
    define('UPSTREAM_ITEM_TYPE_MILESTONE', 'milestone');
    define('UPSTREAM_ITEM_TYPE_CLIENT', 'client');
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

    protected $additionaFields = [];

    /**
     * UpStream_Model_Object constructor.
     * @param $id
     */
    public function __construct($id = 0)
    {
        if (!preg_match('/^[a-zA-Z0-9]+$/', $id))
            throw new UpStream_Model_ArgumentException(sprintf(__('ID %s must be a valid alphanumeric.', 'upstream'), $id));

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

    public static function fields()
    {
        return [
            'id' => [ 'type' => 'id', 'title' => __('ID'), 'search' => false, 'display' => false ],
            'title' => [ 'type' => 'string', 'title' => __('Title'), 'search' => true, 'display' => true  ],
            'description' => [ 'type' => 'text', 'title' => __('Description'), 'search' => true, 'display' => true  ],
            'createdBy' => [ 'type' => 'user_id', 'title' => __('Created By'), 'search' => true, 'display' => true  ],
            'assignedTo' => [ 'type' => 'user_id', 'is_array' => true, 'title' => __('Assigned To'), 'search' => true, 'display' => true ],
        ];
    }

    public function __get($property)
    {
        switch ($property) {

            case 'id':
            case 'title':
            case 'assignedTo':
            case 'createdBy':
            case 'type':
            case 'description':
                return $this->{$property};

            default:

                if (array_key_exists($property, $this->additionaFields)) {

                    $value = apply_filters('upstream_model_get_property_value', $this->additionaFields[$property], $this->type, $this->id, $property);
                    return $value;

                } else {
                    throw new UpStream_Model_ArgumentException(sprintf(__('This (%s) is not a valid property.', 'upstream'), $property));
                }

                return $value;
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
                if (trim(sanitize_text_field($value)) == '')
                    throw new UpStream_Model_ArgumentException(__('You must enter a title.', 'upstream'));

                $this->{$property} = trim(sanitize_text_field($value));
                break;

            case 'description':
                $this->{$property} = wp_kses_post($value);
                break;

            case 'assignedTo':
            case 'assignedTo:byUsername':
            case 'assignedTo:byEmail':
                if (!is_array($value))
                    $value = explode(',', $value);

                $new_value = [];

                foreach ($value as $uid) {
                    $user = false;
                    if ($property === 'assignedTo')
                        $user = get_user_by('id', $uid);
                    if ($property === 'assignedTo:byUsername')
                        $user = get_user_by('login', trim($uid));
                    if ($property === 'assignedTo:byEmail')
                        $user = get_user_by('email', trim($uid));

                    if ($user === false)
                        throw new UpStream_Model_ArgumentException(sprintf(__('User "%s" (for field %s) does not exist.', 'upstream'), $uid, $property));

                    $new_value[] = $user->ID;
                }

                $this->assignedTo = $new_value;
                break;

            case 'createdBy':
                if (get_userdata($value) === false)
                    throw new UpStream_Model_ArgumentException(sprintf(__('User ID %s does not exist.', 'upstream'), $value));

                $this->{$property} = $value;
                break;

            default:
                $orig_value = (array_key_exists($property, $this->additionaFields)) ? $this->additionaFields[$property] : null;

                $propertyExists = apply_filters('upstream_model_property_exists', false, $this->type, $this->id, $property);
                if (!$propertyExists) {
                    throw new UpStream_Model_ArgumentException(sprintf(__('This (%s) is not a valid property.', 'upstream'), $property));
                }

                $new_value = apply_filters('upstream_model_set_property_value', $orig_value, $this->type, $this->id, $property, $value);
                $this->additionaFields[$property] = $new_value;
        }
    }

    public static function loadDate($data, $field)
    {
        if (!empty($data[$field . '.YMD']) && self::isValidDate($data[$field . '.YMD'])) {
            return $data[$field . '.YMD'];
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