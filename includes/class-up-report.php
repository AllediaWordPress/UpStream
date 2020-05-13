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

    public function getDisplayOptions()
    {
        return [
            'visualization_type' => 'Table'
        ];
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

        if (!is_array($haystack)) $haystack = [$haystack];
        if (!is_array($needles)) $needles = [$needles];

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

    protected function parseProjectParams($params, $sectionId)
    {
        $prefix = $sectionId . '_';
        $field_options = $this->getAllFieldOptions();

        $ids = $params[$prefix . 'id'];

        $item_additional_check_callback = function($item) use ($ids) {
            return ($item instanceof UpStream_Model_Project) &&
                (count($ids) == 0 || in_array($item->id, $ids));
        };

        $optionsInfo = $field_options[$sectionId];
        $items = $this->parseFields($params, $prefix, $item_additional_check_callback);

        return $items;
    }

    protected function parseTaskParams($params, $sectionId)
    {
        $prefix = $sectionId . '_';
        $field_options = $this->getAllFieldOptions();

        $ids = $params[$prefix . 'id'];

        $item_additional_check_callback = function($item) use ($ids) {
            return ($item instanceof UpStream_Model_Task) &&
                (count($ids) == 0 || in_array($item->id, $ids));
        };

        $optionsInfo = $field_options[$sectionId];
        $items = $this->parseFields($params, $prefix, $item_additional_check_callback);

        return $items;
    }

    protected function parseMilestoneParams($params, $sectionId)
    {
        $prefix = $sectionId . '_';
        $field_options = $this->getAllFieldOptions();

        $ids = $params[$prefix . 'id'];

        $item_additional_check_callback = function($item) use ($ids) {
            return ($item instanceof UpStream_Model_Milestone) &&
                (count($ids) == 0 || in_array($item->id, $ids));
        };

        $optionsInfo = $field_options[$sectionId];
        $items = $this->parseFields($params, $prefix, $item_additional_check_callback);

        return $items;
    }

    private static function dateBetween($lower_bound, $upper_bound, $val)
    {
        if (empty($upper_bound) && empty($lower_bound)) return true;
        if (!$val) return false;

        try {
            if (empty($lower_bound)) {
                $lower_bound = new DateTime('1970-01-01');
            }
            else {
                $lower_bound = new DateTime($lower_bound);
            }
            if (empty($upper_bound)) {
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

    private static function numberBetween($lower_bound, $upper_bound, $val)
    {
        if (empty($upper_bound) && empty($lower_bound)) return true;
        if (!$val) return false;

        try {
            if (empty($lower_bound) || !is_numeric($lower_bound)) {
                $lower_bound = -999999;
            }
            if (empty($upper_bound) || !is_numeric($upper_bound)) {
                $upper_bound = 999999;
            }

            if ($val < $lower_bound) {
                return false;
            }
            if ($val > $upper_bound) {
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
                if (empty($value)) continue;

                if (is_array($value)) {
                    if ($field['type'] === 'user_id' || $field['type'] === 'select') {
                        if (! $this->arrayIn($value, $item->{$field_name}))
                            return false;
                    }
                } else {
                    if ($field['type'] === 'string' || $field['type'] === 'text') {
                        if (!stristr($item->{$field_name}, $value))
                            return false;
                    } elseif ($field['type'] === 'color') {
                        if (!stristr($item->{$field_name}, $value))
                            return false;
                    } elseif ($field['type'] === 'date') {
                        $value_start = $params[$prefix . $field_name . '_start'];
                        $value_end = $params[$prefix . $field_name . '_end'];
                        if (!self::dateBetween($value_start, $value_end, $item->{$field_name}))
                            return false;
                    } elseif ($field['type'] === 'number') {
                        $value_start = $params[$prefix . $field_name . '_lower'];
                        $value_end = $params[$prefix . $field_name . '_upper'];
                        if (!self::numberBetween($value_start, $value_end, $item->{$field_name}))
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

    public function executeReport($params, $rowCallback = null)
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

            foreach ($display_fields as $field_name) {
                if (isset($fields[$field_name]) && $fields[$field_name]['display']) {

                    $field = $fields[$field_name];
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
                                $val[$j] = 'Date(' . $dp[0] . ',' . sprintf('%02d', $dp[1]-1) . ',' . $dp[2] . ')';
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

            if ($rowCallback != null) {
                $row = call_user_func($rowCallback, $row);
            }

            if ($row != null) {
                $data[] = ['c' => $row];
            }
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
    public $title = 'Projects by Criteria';
    public $id = 'projects';

    public function getDisplayOptions()
    {
        return [
            'show_display_fields_box' => true,
            'visualization_type' => 'Table'
        ];
    }

    public function getAllFieldOptions()
    {
        return ['projects' => [ 'type' => 'project' ]];
    }

    public function getIncludedItems($params)
    {
        $items = self::parseProjectParams($params, 'projects');

        return $items;
    }
}

class UpStream_Report_Milestones extends UpStream_Report
{
    public $title = 'Milestones by Criteria';
    public $id = 'milestones';

    public function getDisplayOptions()
    {
        return [
            'show_display_fields_box' => true,
            'visualization_type' => 'Table'
        ];
    }

    public function getAllFieldOptions()
    {
        return ['milestones' => [ 'type' => 'milestone' ]];
    }

    public function getIncludedItems($params)
    {
        $items = self::parseMilestoneParams($params, 'milestones');

        return $items;
    }
}

class UpStream_Report_Tasks extends UpStream_Report
{
    public $title = 'Tasks by Criteria';
    public $id = 'tasks';

    public function getDisplayOptions()
    {
        return [
            'show_display_fields_box' => true,
            'visualization_type' => 'Table'
        ];
    }

    public function getAllFieldOptions()
    {
        return ['tasks' => [ 'type' => 'task' ]];
    }

    public function getIncludedItems($params)
    {
        $items = self::parseTaskParams($params, 'tasks');

        return $items;
    }
}

class UpStream_Report_Project_Gantt_Chart extends UpStream_Report
{
    public $title = 'Gantt Chart by Project';
    public $id = 'project_gantt';

    public function getAllFieldOptions()
    {
        return ['projects' => [ 'type' => 'project' ]];
    }

    public function getDisplayOptions()
    {
        return [
            'visualization_type' => 'Gantt'
        ];
    }

    public function getIncludedItems($params)
    {
        $items = self::parseProjectParams($params, 'projects');

        return $items;
    }

    public function executeReport($params)
    {
        $params['display_fields'] = ['id', 'title', 'startDate', 'endDate'];
        $data = parent::executeReport($params, function($row) {

            if ($row[3]['f'] == '(empty)' || $row[4]['f'] == '(empty)') {
                //don't show items with no date
                return null;
            }

            $newrow = [ $row[0], $row[1], $row[0], $row[2], $row[3], null, null, null ];

            return $newrow;
        });

        $d = $data['cols'];
        $data['cols'] = [
            $d[0],
            $d[1],
            [ 'id' => 'resource', 'label' => 'Resource', 'type' => 'string'],
            $d[2],
            $d[3],
            [ 'id' => 'duration', 'label' => 'Duration', 'type' => 'number'],
            [ 'id' => 'pct', 'label' => 'Percent Complete', 'type' => 'number'],
            [ 'id' => 'dependencies', 'label' => 'Dependencies', 'type' => 'string']
        ];

        return $data;
    }
}

class UpStream_Report_Milestone_Gantt_Chart extends UpStream_Report
{
    public $title = 'Gantt Chart by Milestone';
    public $id = 'milestones_gantt';

    public function getAllFieldOptions()
    {
        return ['milestones' => [ 'type' => 'milestone' ]];
    }

    public function getDisplayOptions()
    {
        return [
            'visualization_type' => 'Gantt'
        ];
    }

    public function getIncludedItems($params)
    {
        $items = self::parseMilestoneParams($params, 'milestones');

        return $items;
    }

    public function executeReport($params)
    {
        $params['display_fields'] = ['id', 'title', 'color', 'startDate', 'endDate'];
        $data = parent::executeReport($params, function($row) {

            if ($row[3]['f'] == '(empty)' || $row[4]['f'] == '(empty)') {
                //don't show items with no date
                return null;
            }

            $row[] = null;
            $row[] = null;
            $row[] = null;

            return $row;
        });

        $data['cols'][2] = [ 'id' => 'resource', 'label' => 'Resource', 'type' => 'string'];
        $data['cols'][] = [ 'id' => 'duration', 'label' => 'Duration', 'type' => 'number'];
        $data['cols'][] = [ 'id' => 'pct', 'label' => 'Percent Complete', 'type' => 'number'];
        $data['cols'][] = [ 'id' => 'dependencies', 'label' => 'Dependencies', 'type' => 'string'];

        usort($data['rows'], function ($a, $b) {
            $sa = $a['c'][3]['v'];
            $sb = $b['c'][3]['v'];
            $ea = $a['c'][4]['v'];
            $eb = $b['c'][4]['v'];
            return $sa == $sb ? strcmp($ea, $eb) : strcmp($sa, $sb);
        });

        $colors = [];
        for ($i = 0; $i < count($data['rows']); $i++) {
            $colors[] = ['color' => $data['rows'][$i]['c'][2]['v']];
            $data['rows'][$i]['c'][2]['v'] = $data['rows'][$i]['c'][0]['v'];
        }

        $data['options'] = [ 'gantt' => [ 'palette' => $colors ] ];

        return $data;
    }
}

class UpStream_Report_Task_Gantt_Chart extends UpStream_Report
{
    public $title = 'Gantt Chart by Task';
    public $id = 'task_gantt';

    public function getAllFieldOptions()
    {
        return ['tasks' => [ 'type' => 'task' ]];
    }

    public function getDisplayOptions()
    {
        return [
            'visualization_type' => 'Gantt'
        ];
    }

    public function getIncludedItems($params)
    {
        $items = self::parseTaskParams($params, 'tasks');

        return $items;
    }

    public function executeReport($params)
    {
        $params['display_fields'] = ['id', 'title', 'startDate', 'endDate', 'progress'];
        $data = parent::executeReport($params, function($row) {

            if ($row[3]['f'] == '(empty)' || $row[4]['f'] == '(empty)') {
                //don't show items with no date
                return null;
            }

            $newrow = [ $row[0], $row[1], $row[0], $row[2], $row[3], null, $row[4], null ];

            return $newrow;
        });

        $d = $data['cols'];
        $data['cols'] = [
            $d[0],
            $d[1],
            [ 'id' => 'resource', 'label' => 'Resource', 'type' => 'string'],
            $d[2],
            $d[3],
            [ 'id' => 'duration', 'label' => 'Duration', 'type' => 'number'],
            [ 'id' => 'pct', 'label' => 'Percent Complete', 'type' => 'number'],
            [ 'id' => 'dependencies', 'label' => 'Dependencies', 'type' => 'string']
            ];

        return $data;
    }
}
