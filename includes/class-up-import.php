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
if ( ! defined('ABSPATH') || class_exists('UpStream_Import')) {
    return;
}

class UpStream_Import_Exception extends Exception {}

/**
 * Class UpStream_Import
 */
class UpStream_Import
{
    protected $option_identify_project_by_id = true;
    protected $option_identify_milestone_by_id = true;

    protected $option_created_by = 1;

    protected $columns = [];

    protected $client_column = -1;
    protected $project_column = -1;
    protected $milestone_column = -1;
    protected $task_column = -1;
    protected $file_column = -1;
    protected $bug_column = -1;

    protected $model_manager;

    /**
     * UpStream_Admin_Import constructor.
     */
    public function __construct()
    {
        $this->model_manager = \UpStream_Model_Manager::get_instance();
    }

    /**
     * @param int $project_column
     */
    public function setProjectColumn($project_column)
    {
        $this->project_column = $project_column;
    }

    public static function importFile($file)
    {
        if (true) {

            $importer = new UpStream_Import();
            $arr = fgetcsv($file);
            $importer->importTable($arr);

        }
    }

    protected function cleanLine(&$line)
    {
        $newline = [];

        foreach ($line as $l) {
            $newline[] = trim($l);
        }

        return $newline;
    }

    protected function importTable(&$arr)
    {
        if ( count($arr) < 1 ) {
            throw new UpStream_Import_Exception(__('The imported file does not contain a header line.', 'upstream'));
        }

        self::loadHeader($arr[1]);

        for ($i = 1; $i < count($arr); $i++) {

            $line = $this->cleanLine($arr[$i]);

            // load project
            $projectId = $this->findItemField(UPSTREAM_ITEM_TYPE_PROJECT, 'id', $line);
            if (!$projectId) {
                $title = $this->findItemField(UPSTREAM_ITEM_TYPE_PROJECT, 'title', $line);
                $projectId = $this->findOrCreateItemByTitle(UPSTREAM_ITEM_TYPE_PROJECT, $title);
            }

            $project = null;
            if ($projectId) {
                try {
                    $project = $this->model_manager->getById(UPSTREAM_ITEM_TYPE_PROJECT, $projectId);
                } catch (\UpStream_Model_ArgumentException $e) {
                    throw new UpStream_Import_Exception(sprintf(__('Project with ID %s does not exist.', 'upstream'), $projectId));
                }
            }

            // here project = null or a project

            // load milestone
            $milestoneId = $this->findItemField(UPSTREAM_ITEM_TYPE_MILESTONE, 'id', $line);
            if (!$milestoneId) {
                $title = $this->findItemField(UPSTREAM_ITEM_TYPE_MILESTONE, 'title', $line);
                $projectId = $this->findOrCreateItemByTitle(UPSTREAM_ITEM_TYPE_MILESTONE, $title, $project);
            }

            $milestone = null;
            if ($milestoneId) {
                try {
                    $milestone = $this->model_manager->getById(UPSTREAM_ITEM_TYPE_MILESTONE, $milestoneId);
                } catch (\UpStream_Model_ArgumentException $e) {
                    throw new UpStream_Import_Exception(sprintf(__('Milestone with ID %s does not exist.', 'upstream'), $milestoneId));
                }
            }

            // here project and milestone = null or object

            $this->importChildrenOfType(UPSTREAM_ITEM_TYPE_TASK, $project, $milestone, $line);
            $this->importChildrenOfType(UPSTREAM_ITEM_TYPE_FILE, $project, $milestone, $line);
            $this->importChildrenOfType(UPSTREAM_ITEM_TYPE_BUG, $project, $milestone, $line);

        }
    }


    protected function importChildrenOfType($type, &$project, &$milestone, &$line)
    {
        // look for tasks
        $itemId = $this->findItemField($type, 'id', $line);
        if (!$itemId) {
            $title = $this->findItemField($type, 'title', $line);
            $itemId = $this->findOrCreateItemByTitle($type, $title, $project, $milestone);
        }

        if ($itemId) {
            try {
                $item = findTask($project, $milestone, $itemId);
            } catch (\UpStream_Model_ArgumentException $e) {
                throw new UpStream_Import_Exception(sprintf(__('Item %s with ID %s does not exist.', 'upstream'), $type, $itemId));
            }

            $this->setFields($line, $item);
        }

    }

    protected function findOrCreateItemByTitle($type, $title, $project = null, $milestone = null)
    {

        if ($type === UPSTREAM_ITEM_TYPE_PROJECT) {
            return $this->model_manager->createObject($type, $title, $this->option_created_by);
        }

        if (!$project) {
            return null;
        }

        $obj = $this->model_manager->createObject($type, $title, $this->option_created_by, $project->id);

        if ($type === UPSTREAM_ITEM_TYPE_TASK && $milestone) {
            $obj->milestone = milestone;
        }

        return $obj;
    }

    protected function findItemField($type, $field, &$line)
    {
        for ($i = 0; $i < count($this->columns); $i++) {

            if ($this->columns[$i]->itemType === $type && $this->columns[$i]->fieldName === $field) {
                return $line[$i];
            }

        }

        return null;
    }


    /**
     * Sets the fields of the object based on the fields in the table
     * @param $line
     * @param $item
     * @throws UpStream_Import_Exception
     */
    protected function setFields(&$line, &$item)
    {
        $changed = false;

        for ($i = 0; $i < count($line); $i++) {

            if ($this->columns[$i]->itemType === $item->type) {
                try {
                    $val = $item->{$this->columns[$i]->fieldName};
                } catch (\UpStream_Model_ArgumentException $e) {
                    throw new UpStream_Import_Exception($e->getMessage());
                }

                if ($val != $line[$i]) {
                    try {
                        $item->{$this->columns[$i]->fieldName} = $line[$i];
                        $changed = true;
                    } catch (\UpStream_Model_ArgumentException $e) {
                        throw new UpStream_Import_Exception($e->getMessage());
                    }
                }
            }

        }

        if ($changed) {
            $item->store();
        }

        return $changed;
    }


    protected function loadHeader(&$header)
    {
        for ($i = 0; $i < count($header); $i++) {

            $header[$i] = trim($header[$i]);
            $s = null;

            if ($header[$i]) {
                $parts = explode('.', $header[$i]);

                if (count($parts < 2)) {
                    throw new UpStream_Import_Exception(sprintf(__('Header column %s must be of the form item.field (example: project.title).', 'upstream'), $header[$i]));
                }

                $itemType = $parts[0];
                $fieldName = $parts[1];

                if (!in_array($itemType, [UPSTREAM_ITEM_TYPE_PROJECT, UPSTREAM_ITEM_TYPE_BUG, UPSTREAM_ITEM_TYPE_MILESTONE,
                    UPSTREAM_ITEM_TYPE_TASK, UPSTREAM_ITEM_TYPE_FILE])) {
                    throw new UpStream_Import_Exception(sprintf(__('Item type %s is not valid.', 'upstream'), $itemType));
                }

                // TODO: check if field is valid
                $s = new stdClass();
                $s->itemType = $itemType;
                $s->fieldName = $fieldName;

                if ($this->option_identify_project_by_id && $itemType == UPSTREAM_ITEM_TYPE_PROJECT && $fieldName == 'id') {
                    $this->project_column = $i;
                } elseif (!$this->option_identify_project_by_id && $itemType == UPSTREAM_ITEM_TYPE_PROJECT && $fieldName == 'title') {
                    $this->project_column = $i;
                } elseif ($this->option_identify_milestone_by_id && $itemType == UPSTREAM_ITEM_TYPE_MILESTONE && $fieldName == 'id') {
                    $this->milestone_column = $i;
                } elseif (!$this->option_identify_milestone_by_id && $itemType == UPSTREAM_ITEM_TYPE_MILESTONE && $fieldName == 'title') {
                    $this->milestone_column = $i;
                } elseif ($itemType == UPSTREAM_ITEM_TYPE_TASK && $fieldName == 'title') {
                    $this->task_column = $i;
                } elseif ($itemType == UPSTREAM_ITEM_TYPE_FILE && $fieldName == 'title') {
                    $this->file_column = $i;
                } elseif ($itemType == UPSTREAM_ITEM_TYPE_BUG && $fieldName == 'title') {
                    $this->bug_column = $i;
                }

            }

            $this->columns[] = $s;

        }

        if ($this->project_column < 0) {
            throw new UpStream_Import_Exception(__('Could not find a valid project identifier column.', 'upstream'));
        }

    }

}
