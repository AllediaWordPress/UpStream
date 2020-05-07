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

    /**
     * Gets all of the parameter options to show when someone sets up a report. This is
     * a form {
     *   ID : { type : project, task, etc,
     *          field1 : ...,
     *          field2 : ... }
     * }
     *
     * @return array of all options to be used for the report parameters page
     */
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

    protected static function combineArray($arr)
    {
        return implode(', ', $arr);
    }

    protected function parseProjectParams($params, $prefix)
    {
        $field_options = $this->getAllFieldOptions();

        $item_additional_check_callback = function($item) {
            return $item instanceof UpStream_Model_Project;
        };

        foreach ($field_options as $sectionId => $optionsInfo) {
            $prefix = $sectionId . '_';
            $items = $this->parseFields($params, $prefix, $item_additional_check_callback);

        }
        return $items;
    }

    private static function dateBetween($lower_bound, $upper_bound, $val)
    {
        if (!$val) {
            return false;
        }

        try {
            if (!$lower_bound) {
                $lower_bound = new DateTime('9999-01-01');
            }
            else {
                $lower_bound = new DateTime($lower_bound);
            }
            if (!$upper_bound) {
                $upper_bound = new DateTime('9999-01-01');
            }
            else {
                $upper_bound = new DateTime($upper_bound);
            }

            $val = new DateTime($val);
            $d1 = $lower_bound->diff($val)->format('%R%a');
            $d2 = $val->diff($upper_bound)->format('%R%a');

            if ($d1 < 0) {
                return false;
            }
            if ($d2 < 0) {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return true;
        }
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
                    if ($field['type'] === 'string' || $field['type'] === 'text') {
                        if (trim($value) == '') continue;

                        if (!stristr($item->{$field_name}, $value))
                            return false;
                    } elseif ($field['type'] === 'date') {
                        $value_start = $params[$prefix . $field_name . '_start'];
                        $value_end = $params[$prefix . $field_name . '_end'];
                        return self::dateBetween($value_start, $value_end, $item->{$field_name});
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

        $display_fields = $params['display_fields'];
        $items = $this->getIncludedItems($params);
        $data = [];

        $users_info = upstream_get_viewable_users();
        $users = $users_info['by_uid'];

        $columns = [];

        for ($i = 0; $i < count($items); $i++) {

            $item = $items[$i];
            $fields = $items[$i]->fields();
            $row = [];

            foreach ($fields as $field_name => $field) {
                if ($field['display'] && in_array($field_name, $display_fields)) {

                    $columns[$field_name] = $field;

                    $val = $item->{$field_name};
                    $f = null;

                    if (!is_array($val)) {
                        $val = [$val];
                    }

                    for ($j = 0; $j < count($val); $j++) {
                        if (!empty($field['options_cb'])) {
                            $options = call_user_func($field['options_cb']);
                            if (isset($options[$val[$j]]))
                                $val[$j] = $options[$val[$j]];
                        } elseif ($field['type'] === 'user_id') {
                            if (isset($users[$val[$j]]))
                                $val[$j] = $users[$val[$j]];
                        } elseif ($field['type'] === 'date') {
                            if ($val[$j]) {
                                $dp = explode('-', $val[$j]);
                                $val[$j] = 'Date(' . $dp[0] . ',' . ($dp[1]-1) . ',' . $dp[2] . ')';
                                $f = null;
                            } else {
                                // TODO: this is hacked to work with Google Charts which won't accept a null date
                                $val[$j] = 'Date(2020,1,1)';
                                $f = '(empty)';
                            }
                        }
                    } // end for

                    $row[] = $this->makeItem($val, $f);
                }
            } // end foreach

            $data[] = ['c' => $row];
        }

        $columnInfo = $this->makeColumnInfo($columns);

        return [ 'cols' => $columnInfo, 'rows' => $data ];
    }

    protected function makeItem($val, $f = null)
    {
        $r = [ 'v' => self::combineArray($val) ];
        if ($f) {
            $r['f'] = $f;
        }
        return $r;
    }

    public function getIncludedItems($params)
    {
        return [];
    }

    protected function makeColumnInfo(&$columns)
    {
        $columnInfo = [];

        foreach ($columns as $cid => $column) {
            $ci = [];
            $ci['id'] = $cid;
            $ci['label'] = $column['title'];

            switch ($column['type']) {

                case 'date':
                    $ci['type'] = 'date';
                    break;

                default:
                    $ci['type'] = 'string';
            }
            $columnInfo[] = $ci;
        }

        return $columnInfo;
    }

}

class UpStream_Report_Projects extends UpStream_Report
{
    public $title = 'Projects';
    public $id = 'projects';

    public function getAllFieldOptions()
    {
        return ['projects' => [ 'type' => 'project' ]];
    }

    public function getIncludedItems($params)
    {
        $items = self::parseProjectParams($params, 'project_');

        return $items;
    }
}

class UpStream_Report_Gantt extends UpStream_Report {

}