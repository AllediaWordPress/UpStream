<?php
/**
 * Setup message asking for review.
 *
 * @author   UpStream
 * @category Admin
 * @package  UpStream/Admin
 * @version  1.0.0
 */

// Exit if accessed directly or already defined.
if ( ! defined('ABSPATH') || class_exists('UpStream_Report')) {
    return;
}

/**
 * Class UpStream_Report
 */
class UpStream_Report
{
    public $title = '(none)';
    public $id = '';

    /**
     * UpStream_Report constructor.
     */
    public function __construct()
    {
    }

    public function getAllFieldOptions()
    {
        return [];
    }

    public function getFieldOption($section, $key = null)
    {
        $fo = $this->getAllFieldOptions();

        if (!isset($fo[$section])) {
            return null;
        } else {
            if ($key) {
                return isset($fo[$section][$key]) ? $fo[$section][$key] : null;
            } else {
                return $fo[$section];
            }
        }
    }

    private function arrayIn($needles, $haystack, $comparator = null)
    {
        if (!$comparator) {
            $comparator = function($a, $b) { return $a == $b; };
        }

        for ($j = 0; $j < count($haystack); $j++) {
            for ($i = 0; $i < count($needles); $i++) {
                if ($comparator($needles[$i], $haystack[$j])) return true;
            }
        }

        return false;
    }


    protected function parseProjectParams($params, $prefix)
    {
        $field_options = $this->getAllFieldOptions();

        $item_additional_check_callback = function($item) {
            return $item instanceof UpStream_Model_Project;
        };

        $this->parseFields($params, $prefix, $item_additional_check_callback);
    }

    protected function parseFields($params, $prefix, $item_additional_check_callback)
    {
        $mm = UpStream_Model_Manager::get_instance();

        $items = $mm->findAllByCallback(function($item) use ($params, $prefix, $item_additional_check_callback) {

            $fields = $item->fields();
            if ($item_additional_check_callback && $item_additional_check_callback($item) == false) {
                return false;
            }

            foreach ($fields as $field_name => $field) {
                if (!$field['search']) {
                    continue;
                }

                $value = $params[$prefix . $field_name];

                if (is_array($value)) {
                    if ($field['type'] === 'user_id' || $field['type'] === 'select') {
                        $value = empty($field['is_array']) ? [$value] : $value;
                        if (! $this->arrayIn($value, $item->{$field_name}))
                            return false;
                    }
                } else {
                    if (trim($value) == '') continue;

                    if ($field['type'] === 'string' || $field['type'] === 'text') {
                        if (!stristr($item->{$field_name}, $value))
                            return false;
                    } elseif ($item->{$field_name} != $value) {
                        //return false;
                    }
                }

            }

            return true;
        });

        return $items;
    }

    public function executeReport($params)
    {

        $display_fields = $params['display'];
        $items = $this->getIncludedItems($params);
        $data = [];

        for ($i = 0; $i < count($items); $i++) {

            $item = $items[$i];
            $fields = $items[$i]->fields();
            $row = [];

            foreach ($fields as $field_name => $field) {
                if ($field['display'] && in_array($field_name, $display_fields)) {
                    $val = $item->{$field_name};

                    if (!is_array($val)) {
                        $val = [$val];
                    }

                    for ($j = 0; $j < count($val); $j++) {
                        if (!empty($field['options_cb'])) {
                            $options = call_user_func($field['options_cb']);
                            if (isset($options[$val[$j]]))
                                $val[$j] = $options[$val[$j]];
                        } elseif ($field['type'] === 'user_id') {

                        }
                    } // end for
                }
            } // end foreach

        }


    }

    public function getIncludedItems($params)
    {
        return [];
    }
}

class UpStream_Report_Projects extends UpStream_Report
{
    public $title = 'Projects';
    public $id = 'projects';

    public function getAllFieldOptions()
    {
        return ['projects' => []];
    }

    public function getIncludedItems($params)
    {
        $items = self::parseProjectParams($params, 'project_');
    }
}