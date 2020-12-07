<?php

if ( ! defined('ABSPATH') || class_exists('UpStream_Report')) {
    return;
}

/**
 * Class UpStream_Report
 */
class UpStream_Report
{
    public $title = '';
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
        if (!is_array($arr))
            return $arr;
        elseif (count($arr) == 1)
            return $arr[0];
        else
            return implode(', ', $arr);
    }

    protected function parseProjectParams($params, $sectionId)
    {
        $prefix = $sectionId . '_';
        $field_options = $this->getAllFieldOptions();

        $ids = $params[$prefix . 'id'];

        $item_additional_check_callback = function($item) use ($ids) {

            if (!$item instanceof UpStream_Model_Project)
                return false;

            if (!upstream_user_can_access_project(get_current_user_id(), $item->id))
                return false;

            return (in_array($item->id, $ids));
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

            if (!$item instanceof UpStream_Model_Task)
                return false;

            if (!upstream_override_access_object(true, UPSTREAM_ITEM_TYPE_TASK, $item->id, UPSTREAM_ITEM_TYPE_PROJECT, $item->parent->id, UPSTREAM_PERMISSIONS_ACTION_VIEW))
                return false;

            return (in_array($item->id, $ids));

        };

        $optionsInfo = $field_options[$sectionId];
        $items = $this->parseFields($params, $prefix, $item_additional_check_callback);

        return $items;
    }

    protected function parseBugParams($params, $sectionId)
    {
        $prefix = $sectionId . '_';
        $field_options = $this->getAllFieldOptions();

        $ids = $params[$prefix . 'id'];

        $item_additional_check_callback = function($item) use ($ids) {

            if (!$item instanceof UpStream_Model_Bug)
                return false;

            if (!upstream_override_access_object(true, UPSTREAM_ITEM_TYPE_BUG, $item->id, UPSTREAM_ITEM_TYPE_PROJECT, $item->parent->id, UPSTREAM_PERMISSIONS_ACTION_VIEW))
                return false;

            return (in_array($item->id, $ids));

        };

        $optionsInfo = $field_options[$sectionId];
        $items = $this->parseFields($params, $prefix, $item_additional_check_callback);

        return $items;
    }


	protected function parseFileParams($params, $sectionId)
	{
		$prefix = $sectionId . '_';
		$field_options = $this->getAllFieldOptions();

		$ids = $params[$prefix . 'id'];

		$item_additional_check_callback = function($item) use ($ids) {

            if (!$item instanceof UpStream_Model_File)
                return false;

            if (!upstream_override_access_object(true, UPSTREAM_ITEM_TYPE_FILE, $item->id, UPSTREAM_ITEM_TYPE_PROJECT, $item->parent->id, UPSTREAM_PERMISSIONS_ACTION_VIEW))
                return false;

            return (in_array($item->id, $ids));

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

            if (!$item instanceof UpStream_Model_Milestone)
                return false;

            if (!upstream_override_access_object(true, UPSTREAM_ITEM_TYPE_MILESTONE, $item->id, UPSTREAM_ITEM_TYPE_PROJECT, $item->parentId, UPSTREAM_PERMISSIONS_ACTION_VIEW))
                return false;

            return (in_array($item->id, $ids));

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
        if ((empty($upper_bound) && $upper_bound!= 0) && (empty($lower_bound) && $lower_bound !=0)) return true;
        if (empty($val)) $val = 0;

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

    protected function checkItem($item, $params, $prefix, $item_additional_check_callback)
    {
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
    }

    protected function parseFields($params, $prefix, $item_additional_check_callback)
    {
        $mm = UpStream_Model_Manager::get_instance();

        $items = $mm->findAllByCallback(function($item) use ($params, $prefix, $item_additional_check_callback) {
			return $this->checkItem($item, $params, $prefix, $item_additional_check_callback);
        });

        return $items;
    }

    public function makeCell($val, &$field, &$users, $override_f = null)
    {
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
			    if ( isset( $users[ $val[ $j ] ] ) ) {
				    $val[ $j ] = $users[ $val[ $j ] ];
			    }
		    } elseif ($field['type'] === 'file') {
		    	if (upstream_filesytem_enabled()) {
				    if ( $file = upstream_upfs_info( $val[ $j ] ) ) {
					    $val[ $j ] = $file->orig_filename;
				    }
			    } else {
		    		if ( $val[$j] ) {
					    $file = get_attached_file($val[$j]);
					    $val[ $j ] = $file ? basename($file) : '';
				    } else {
		    		    $val[$j] = '';
                    }
			    }
		    } elseif ($field['type'] === 'date') {
			    if ($val[$j]) {
				    $dp = explode('-', $val[$j]);
				    $val[$j] = 'Date(' . $dp[0] . ',' . sprintf('%02d', $dp[1]-1) . ',' . $dp[2] . ')';
				    $f = null;
			    } else {
				    $val[$j] = '';
				    $f = '(empty)';
			    }
		    }
	    } // end for

        if ($override_f) $f = $override_f;

        if ($field['type'] === 'number') {
            $val[0] = (float)$val[0];
        }

        return $this->makeItem($val, $f);
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

                    $parent_type = null;
                    $parent_id = 0;

                    if ($item instanceof UpStream_Model_Meta_Object) {
                        $parent_type = $item->parent->type;
                        $parent_id = $item->parent->id;
                    } elseif ($item instanceof UpStream_Model_Milestone) {
                        $parent_type = UPSTREAM_ITEM_TYPE_PROJECT;
                        $parent_id = $item->parentId;
                    }

                    if (upstream_override_access_field(true, $item->type, $item->id, $parent_type, $parent_id, $field_name, UPSTREAM_PERMISSIONS_ACTION_VIEW)) {
                        $val = $item->{$field_name};
                        $row[] = $this->makeCell($val, $field, $users);
                    } else {
                        $val = null;
                        $row[] = $this->makeCell($val, $field, $users, '(hidden)');
                    }
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

                case 'number':
                    $ci['type'] = 'number';
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
    public $id = 'projects';

    public function __construct()
    {
        $this->title = __('Project Table', 'upstream-reporting');
    }

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
