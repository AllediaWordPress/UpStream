<?php
// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Class Upstream_Counter
 */
class Upstream_Counter
{
    /**
     * @var array
     */
    protected $currentUserData;

    /**
     * @var array
     */
    protected $projects = [];

    /**
     * @var array
     */
    protected $projectIds = [];

    /**
     * @var array
     */
    protected $items = [];

    /**
     * Upstream_Counter constructor.
     *
     * @param int|array $projectIds
     */
    public function __construct($projectIds = null)
    {
        $this->projectIds = $projectIds;
    }

    /**
     * Return the total of items from specific type.
     *
     * @param string $itemType
     *
     * @return int
     */
    public function getTotalItemsOfType($itemType)
    {
        $items = $this->getItemsOfType($itemType);

        return count($items);
    }

    /**
     * Retrieve items from the project.
     *
     * @param string $itemType
     *
     * @return array|mixed
     */
    public function getItemsOfType($itemType)
    {
        $projects = $this->getProjects();

        if (empty($projects)) {
            return [];
        }

        if ( ! isset($this->items[$itemType])) {
            $items = [];

            foreach ($projects as $project) {
                // Check if the item type is disabled for this project.
                $disabled = get_post_meta($project->ID, '_upstream_project_disable_' . $itemType, true) === 'on';
                if ($disabled) {
                    continue;
                }

                // If milestones, don't use the metadata, but the milestone classes instead
                if ('milestones' === $itemType) {
                    $milestonesUtil = \UpStream\Milestones::getInstance();
                    $dataSet        = $milestonesUtil->getMilestonesFromProject($project->ID, true);
                } else {
                    $dataSet = get_post_meta($project->ID, '_upstream_project_' . $itemType, true);
                }

                // RSD: added if statement to fix count bug 873, which appears due to merge
                if ($dataSet && count($dataSet) > 0)
                    $items = array_merge((array)$items, (array)$dataSet);
            }

            $this->items[$itemType] = $items;
        }

        return $this->items[$itemType];
    }

    /**
     * Retrieve all tasks from projects.
     *
     * @return array
     */
    public function getProjects()
    {
        if (empty($this->projects)) {
            $args = [
                'post_type'      => 'project',
                'post_status'    => 'any',
                'posts_per_page' => -1,
            ];

            if ( ! empty($this->projectIds)) {
                $args['include'] = $this->projectIds;
            }

            $this->projects = (array)get_posts($args);
        }

        return $this->projects;
    }

    /**
     * Returns the total count of open items
     *
     * @param string $itemType
     *
     * @return null|int
     */
    public function getTotalOpenItemsOfType($itemType)
    {
        $total = 0;
        $items = $this->getItemsOfType($itemType);

        if (count($items) > 0) {
            // Milestones doesn't have state so they are always open.
            if ($itemType === 'milestones') {
                return count($items);
            }

            $options  = (array)get_option("upstream_{$itemType}");
            $statuses = isset($options['statuses']) ? $options['statuses'] : '';

            if (empty($statuses)) {
                return 0;
            }

            $statuses = wp_list_pluck($statuses, 'type', 'id');

            foreach ($items as $item) {
                if ( ! isset($item['status'])) {
                    continue;
                }

                $itemStatus = $item['status'];
                if (isset($statuses[$itemStatus]) && $statuses[$itemStatus] === 'open') {
                    $total++;
                }
            }
        }

        return $total;
    }

    /**
     * Get the count of items assigned to the current user.
     *
     * @param string $itemType The item type to be searched. I.e.: tasks, bugs, etc.
     *
     * @return  integer
     *
     */
    public function getTotalAssignedToCurrentUserOfType($itemType)
    {
        $rowset = $this->getItemsOfType($itemType);
        if (count($rowset) === 0) {
            return 0;
        }

        $userData      = $this->getCurrentUserData();
        $currentUserId = (int)$userData['id'];

        $assignedItemsCount = 0;

        foreach ($rowset as $row) {
            if ( ! isset($row['assigned_to'])) {
                continue;
            }

            $assignees = array_unique(array_filter(array_map('intval', (array)$row['assigned_to'])));

            if (in_array($currentUserId, $assignees)) {
                $assignedItemsCount++;
            }
        }

        return $assignedItemsCount;
    }

    /**
     * @return array|void|null
     */
    protected function getCurrentUserData()
    {
        if (empty($this->currentUserData)) {
            $this->currentUserData = upstream_user_data();
        }

        return $this->currentUserData;
    }

    /**
     * Returns the count of OPEN tasks for the current user
     *
     * @return int
     */
    public function getTotalOpenItemsOfTypeForCurrentUser($itemType)
    {
        $items = $this->getItemsOfType($itemType);

        if (empty($items)) {
            return 0;
        }

        // Milestones doesn't have state so they are always opened.
        if ($itemType == 'milestones') {
            return count($items);
        }

        $option   = get_option('upstream_' . $itemType);
        $statuses = isset($option['statuses']) ? $option['statuses'] : '';

        if (empty($statuses)) {
            return 0;
        }

        $itemTypes = wp_list_pluck($statuses, 'type', 'id');
        $userData  = $this->getCurrentUserData();

        $count = 0;
        foreach ($items as $key => $item) {
            $item = (array)$item;

            if ( ! isset($item['assigned_to'])) {
                continue;
            }

            if (
                (is_array($item['assigned_to']) && ! in_array($userData['id'], $item['assigned_to']))
                || (is_numeric($item['assigned_to'] && $item['assigned_to'] != $userData['id']))
            ) {
                continue;
            }

            $item_status = isset($item['status']) ? $item['status'] : '';

            if ((isset($itemTypes[$item_status]) && $itemTypes[$item_status] == 'open') || $item_status === "") {
                $count++;
            }
        }

        return $count;
    }
}


// new Upstream_Counts();
