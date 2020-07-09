<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_TimeRecord
{
    protected $id = 0;

    protected $user = 0;

    protected $startTimestamp = 0;

    protected $elapsedTime = 0;

    protected $budgeted = 0;

    protected $spent = 0;

    protected $note = '';

    /**
     * UpStream_Model_Reminder constructor.
     */
    public function __construct($item_metadata)
    {
        $this->loadFromArray($item_metadata);
    }

    protected function loadFromArray($item_metadata)
    {
        $this->id = !empty($item_metadata['id']) ? $item_metadata['id'] : 0;
        $this->user = !empty($item_metadata['user']) ? $item_metadata['user'] : 0;
        $this->startTimestamp = !empty($item_metadata['startTimestamp']) ? $item_metadata['startTimestamp'] : 0;
        $this->elapsedTime = !empty($item_metadata['elapsedTime']) ? $item_metadata['elapsedTime'] : 0;
        $this->budgeted = !empty($item_metadata['budgeted']) ? $item_metadata['budgeted'] : null;
        $this->spent = !empty($item_metadata['spent']) ? $item_metadata['spent'] : null;
        $this->note = !empty($item_metadata['note']) ? $item_metadata['note'] : null;
    }

    public function storeToArray(&$item_metadata)
    {
        if ($this->startTimestamp > 0) {
            $hours = round((time() - $this->startTimestamp) / 3600, 1);
            $this->elapsedTime = $hours;
        }

        $item_metadata['id'] = $this->id;
        $item_metadata['user'] = $this->user;
        $item_metadata['startTimestamp'] = $this->startTimestamp;
        $item_metadata['elapsedTime'] = $this->elapsedTime;
        $item_metadata['budgeted'] = $this->budgeted;
        $item_metadata['spent'] = $this->spent;
        $item_metadata['note'] = $this->note;
    }

    public static function workHoursPerDay()
    {
        $options = get_option('upstream_general');
        $optionName = 'local_work_hours_per_day';
        $hrs = isset($options[$optionName]) ? (int)$options[$optionName] : 8;

        return $hrs;
    }

    public static function monetarySymbol()
    {
        $options = get_option('upstream_general');
        $optionName = 'local_monetary_symbol';
        $sym = isset($options[$optionName]) ? (int)$options[$optionName] : 8;

        return $sym;
    }

    public function startTiming()
    {
        $this->startTimestamp = time();
        $this->elapsedTime = 0;
    }

    public function stopTiming()
    {
        $hours = round((time() - $this->startTimestamp) / 3600, 1);
        $this->elapsedTime = $hours;
        $this->startTimestamp = 0;
    }

    public function isTiming()
    {
        return ($this->startTimestamp != 0);
    }

    public static function formatElapsed($elapsed)
    {
        $days = floor($elapsed / self::workHoursPerDay());
        $hours = $elapsed - ($days * self::workHoursPerDay());
        return round($days, 0) . ' days' . ($hours > 0 ? ', ' . round($hours, 1) . ' hours' : '');
    }

    public function __get($property)
    {
        switch ($property) {

            case 'id':
            case 'startTimestamp':
            case 'user':
            case 'note':
            case 'budgeted':
            case 'spent':
                return $this->{$property};

            case 'elapsedTime':
                if ($this->startTimestamp > 0) {
                    $hours = round((time() - $this->startTimestamp) / 3600, 1);
                    $this->elapsedTime = $hours;
                }

                return $this->elapsedTime;

            default:
                throw new UpStream_Model_ArgumentException(sprintf(__('This (%s) is not a valid property.', 'upstream'), $property));
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

            case 'note':
                $this->{$property} = wp_kses_post($value);
                break;

            case 'user':
            case 'user:byUsername':
            case 'user:byEmail':
                $uid = $value;
                $user = false;

                if ($property === 'user')
                    $user = get_user_by('id', $uid);
                if ($property === 'user:byUsername')
                    $user = get_user_by('login', $uid);
                if ($property === 'user:byEmail')
                    $user = get_user_by('email', $uid);

                if ($user === false)
                    throw new UpStream_Model_ArgumentException(sprintf(__('User "%s" (for field %s) does not exist.', 'upstream'), $uid, $property));

                $this->user = $user->ID;
                break;

            case 'spent':
            case 'budgeted':
                if ($value && !is_numeric($value))
                    throw new UpStream_Model_ArgumentException(sprintf(__('This (%s) is not a number.', 'upstream'), $property));

                $this->{$property} = $value;

                break;

            case 'startTimestamp':
            case 'elapsedTime':
                if (!is_numeric($value))
                    throw new UpStream_Model_ArgumentException(sprintf(__('This (%s) is not a number.', 'upstream'), $property));

                $this->{$property} = $value;

                break;

            default:
                throw new UpStream_Model_ArgumentException(sprintf(__('This (%s) is not a valid property.', 'upstream'), $property));
        }
    }

}