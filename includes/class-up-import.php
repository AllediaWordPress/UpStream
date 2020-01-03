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
    protected $option_created_by = 1;

    protected $columns = [];

    protected $model_manager;

    /**
     * UpStream_Admin_Import constructor.
     */
    public function __construct()
    {
        $this->model_manager = \UpStream_Model_Manager::get_instance();
        $this->model_manager->loadAll();
    }

    /**
     * @param int $project_column
     */
    public function setProjectColumn($project_column)
    {
        $this->project_column = $project_column;
    }

    /**
     * @param $file
     * @return string|null
     */
    public static function importFile($file, $line_start)
    {

        if (true) {

            $error = '';
            $importer = new UpStream_Import();

            ini_set('auto_detect_line_endings',TRUE);
            $handle = fopen($file,'r');

            try {
                $lineNo = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {

                    if ($lineNo >= $line_start) {
                        $importer->importTableLine($data, $lineNo);
                    }
                    $lineNo++;
                    if ($lineNo >= $line_start + 30) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                $error = __('Error loading file: line ', 'upstream') . ($lineNo + 1) . ' ' . $e->getMessage();
            }

            fclose($handle);
            ini_set('auto_detect_line_endings',FALSE);

            return $error;
        }
    }

    public static function prepareFile($file)
    {

        if (true) {

            $message = '';
            $importer = new UpStream_Import();

            ini_set('auto_detect_line_endings',TRUE);
            $handle = fopen($file,'r');

            try {
                $lineNo = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    // TODO: check each line for validity
                    $lineNo++;
                }
            } catch (\Exception $e) {
                $message = __('Error loading file: line ', 'upstream') . ($lineNo + 1) . ' ' . $e->getMessage();
            }

            fclose($handle);
            ini_set('auto_detect_line_endings',FALSE);

            return ['message' => $message, 'lines' => $lineNo];
        }
    }

    /**
     * @param $line
     * @return array
     */
    protected function cleanLine(&$line)
    {
        $newline = [];

        foreach ($line as $l) {
            $newline[] = trim($l);
        }

        return $newline;
    }

    /**
     * @param $arr
     * @param $lineNo
     * @throws UpStream_Import_Exception
     */
    protected function importTableLine(&$arr, $lineNo)
    {
        if ($lineNo == 0) {
            $this->loadHeader($arr);
        } else {
            $line = $this->cleanLine($arr);

            // load project
            $projectId = $this->findItemField(UPSTREAM_ITEM_TYPE_PROJECT, 'id', $line);
            if (!$projectId) {
                $title = $this->findItemField(UPSTREAM_ITEM_TYPE_PROJECT, 'title', $line);
                if ($title) {
                    $projectId = $this->findOrCreateItemByTitle(UPSTREAM_ITEM_TYPE_PROJECT, $title);
                }
            }

            $project = null;
            if ($projectId) {
                try {
                    $project = $this->model_manager->getByID(UPSTREAM_ITEM_TYPE_PROJECT, $projectId);
                } catch (\UpStream_Model_ArgumentException $e) {
                    throw new UpStream_Import_Exception(sprintf(__('Project with ID %s does not exist.', 'upstream'), $projectId));
                }
            }

            if ($project) {
                $this->setFields($line, $project);
            }

            // load milestone
            $milestoneId = $this->findItemField(UPSTREAM_ITEM_TYPE_MILESTONE, 'id', $line);
            if (!$milestoneId) {
                $title = $this->findItemField(UPSTREAM_ITEM_TYPE_MILESTONE, 'title', $line);
                if ($title) {
                    $milestoneId = $this->findOrCreateItemByTitle(UPSTREAM_ITEM_TYPE_MILESTONE, $title, $project);
                }
            }

            $milestone = null;
            if ($milestoneId) {
                try {
                    $milestone = $this->model_manager->getByID(UPSTREAM_ITEM_TYPE_MILESTONE, $milestoneId);
                } catch (\UpStream_Model_ArgumentException $e) {
                    throw new UpStream_Import_Exception(sprintf(__('Milestone with ID %s does not exist.', 'upstream'), $milestoneId));
                }
            }


            if ($milestone) {
                $this->setFields($line, $milestone);
            }

            $this->importChildrenOfType(UPSTREAM_ITEM_TYPE_TASK, $project, $milestone, $line);
            $this->importChildrenOfType(UPSTREAM_ITEM_TYPE_FILE, $project, $milestone, $line);
            $this->importChildrenOfType(UPSTREAM_ITEM_TYPE_BUG, $project, $milestone, $line);


        }
    }

    /**
     * @param $type
     * @param $project
     * @param $itemId
     * @return mixed
     */
    protected function findChildItem($type, &$project, $itemId)
    {
        if ($project) {
            $pid = $project->id;

            return $this->model_manager->getByID($type, $itemId, UPSTREAM_ITEM_TYPE_PROJECT, $project->id);

        } else {
            // TODO: what to do when there's no preoject
        }
    }


    /**
     * @param $type
     * @param $project
     * @param $milestone
     * @param $line
     * @throws UpStream_Import_Exception
     */
    protected function importChildrenOfType($type, &$project, &$milestone, &$line)
    {
        // look for tasks
        $itemId = $this->findItemField($type, 'id', $line);
        if (!$itemId) {
            $title = $this->findItemField($type, 'title', $line);
            if ($title) {
                $itemId = $this->findOrCreateItemByTitle($type, $title, $project, $milestone);
            }
        }

        if ($itemId) {
            try {
                $item = $this->findChildItem($type, $project, $itemId);
            } catch (\UpStream_Model_ArgumentException $e) {
                throw new UpStream_Import_Exception(sprintf(__('Item %s with ID %s does not exist.', 'upstream'), $type, $itemId));
            }

            $this->setFields($line, $item);
        }

    }

    /**
     * @param $type
     * @param $title
     * @param null $project
     * @param null $milestone
     * @return |null
     */
    protected function findOrCreateItemByTitle($type, $title, $project = null, $milestone = null)
    {

        if ($type === UPSTREAM_ITEM_TYPE_PROJECT) {

            $matches = $this->model_manager->findAllByCallback(function($item) use ($title) {
                return $item->type === UPSTREAM_ITEM_TYPE_PROJECT && $item->title == $title;
            });

            $obj = null;

            if (count($matches) > 0) {
                $obj = $matches[0];
            } else {
                $obj = $this->model_manager->createObject($type, $title, $this->option_created_by);
                $obj->store();
            }

            return $obj->id;
        }

        if (!$project) {
            return null;
        }

        $matches = $this->model_manager->findAllByCallback(function($item) use ($title, $type, $project) {
            return $item->type === $type && $item->parentId == $project->id && $item->title == $title;
        });

        if (count($matches) > 0) {
            $obj = $matches[0];
        } else {
            $obj = $this->model_manager->createObject($type, $title, $this->option_created_by, $project->id);
        }

        if ($type === UPSTREAM_ITEM_TYPE_TASK && $milestone) {
            $obj->milestone = $milestone;
        }

        $obj->store();

        return $obj->id;
    }

    /**
     * @param $type
     * @param $field
     * @param $line
     * @return mixed|null
     */
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

        if (!$item) {
            return;
        }

        for ($i = 0; $i < count($line); $i++) {

            if (!$this->columns[$i]) {
                continue;
            }

            if ($this->columns[$i]->itemType === $item->type) {
                $val = null;
                try {
                    $val = $item->{$this->columns[$i]->fieldName};
                } catch (\UpStream_Model_ArgumentException $e) {
                    // ignore this
                }

                if ($line[$i] && $val != $line[$i]) {
                    try {
                        $item->{$this->columns[$i]->fieldName} = htmlentities(iconv("cp1252", "utf-8", trim($line[$i])), ENT_IGNORE, "UTF-8");
                        $changed = true;
                    } catch (\UpStream_Model_ArgumentException $e) {
                        throw new UpStream_Import_Exception(sprintf(__('(column %s, field %s)', 'upstream'), $i+1, $this->columns[$i]->fieldName) . ' ' . $e->getMessage());
                    }
                }
            }

        }

        if ($changed) {
            $item->store();
        }

        return $changed;
    }


    /**
     * @param $header
     * @throws UpStream_Import_Exception
     */
    protected function loadHeader(&$header)
    {
        for ($i = 0; $i < count($header); $i++) {

            $header[$i] = trim($header[$i]);
            $s = null;

            if ($header[$i]) {
                $parts = explode('.', $header[$i]);

                if (count($parts) < 2) {
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
            }

            $this->columns[] = $s;

        }
    }

}
