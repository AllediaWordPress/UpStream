<?php
/**
 * Setup menus in WP admin.
 *
 * @author   UpStream
 * @category Admin
 * @package  UpStream/Admin
 * @version  1.0.0
 */

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! class_exists('UpStream_Admin_Projects_Menu')) :

    /**
     * UpStream_Admin_Menus Class.
     */
    class UpStream_Admin_Projects_Menu
    {
        private static $userIsUpStreamUser = null;

        /**
         * Hook in tabs.
         */
        public function __construct()
        {
            if (self::$userIsUpStreamUser === null) {
                $user                     = wp_get_current_user();
                self::$userIsUpStreamUser = count(array_intersect(
                        $user->roles,
                        ['administrator', 'upstream_manager']
                    )) === 0;
            }

            add_action('admin_menu', [$this, 'custom_menu_items'], 9);
            add_filter('custom_menu_order', [$this, 'submenu_order']);
            add_action('admin_head', [$this, 'hideAddNewProjectButtonIfNeeded']);
        }

        public function hideAddNewProjectButtonIfNeeded()
        {
            if (is_admin()) {
                global $pagenow;

                if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'project' && self::$userIsUpStreamUser) {
                    echo '<style type="text/css">.page-title-action { display: none; }</style>';
                }
            }
        }

        /**
         * Add menu item.
         */
        public function custom_menu_items()
        {
            add_submenu_page(
                'edit.php?post_type=project',
                upstream_client_label_plural(),
                upstream_client_label_plural(),
                'edit_clients',
                'edit.php?post_type=client'
            );

            add_submenu_page(
                'edit.php?post_type=project',
                upstream_milestone_category_label_plural(),
                upstream_milestone_category_label_plural(),
                'edit_projects',
                'edit-tags.php?taxonomy=upst_milestone_category&post_type=upst_milestone'
            );
        }

        public function submenu_order($menu)
        {
            global $submenu;

            $subMenuIdentifier = 'edit.php?post_type=project';
            if (isset($submenu[$subMenuIdentifier])
                && ! empty($submenu[$subMenuIdentifier])
            ) {
                $upstreamSubmenu    = &$submenu[$subMenuIdentifier];
                $newUpStreamSubmenu = [];

                $searchSubmenuItem = function ($needle) use (&$upstreamSubmenu) {
                    foreach ($upstreamSubmenu as $submenuIndex => $submenu) {
                        $regexp = '/' . $needle . '/i';
                        if (preg_match($regexp, $submenu[2])) {
                            return $submenu;
                        }
                    }

                    return null;
                };

                $submenuProjects = $searchSubmenuItem('^edit\.php\?post_type=project$');
                if ($submenuProjects !== null) {
                    $newUpStreamSubmenu[] = $submenuProjects;
                }
                unset($submenuProjects);

                if (self::$userIsUpStreamUser) {
                    $submenuTasks = $searchSubmenuItem('^tasks$');
                    if ($submenuTasks !== null
                        && strpos($submenuTasks[0], 'update-count') !== false
                    ) {
                        $newUpStreamSubmenu[] = $submenuTasks;
                    }
                    unset($submenuTasks);

                    $submenuBugs = $searchSubmenuItem('^bugs$');
                    if ($submenuBugs !== null
                        && strpos($submenuBugs[0], 'update-count') !== false
                    ) {
                        $newUpStreamSubmenu[] = $submenuBugs;
                    }
                    unset($submenuBugs);
                } else {
                    $areCategoriesEnabled = ! is_project_categorization_disabled();
                    $areClientsEnabled    = ! is_clients_disabled();
                    $milestonesEnabled    = ! upstream_disable_milestones();
                    $milestoneTagsEnabled = ! upstream_disable_milestone_categories();

                    if ($milestonesEnabled) {
                        $submenuMilestones = $searchSubmenuItem('^edit\.php\?post_type=upst_milestone');
                        if ($submenuMilestones !== null) {
                            $newUpStreamSubmenu[] = $submenuMilestones;
                        }
                        unset($submenuMilestones);
                    }

                    if ($milestoneTagsEnabled) {
                        $submenuMilestoneTags = $searchSubmenuItem('^edit-tags\.php\?taxonomy=upst_milestone_category&post_type=upst_milestone');
                        if ($submenuMilestoneTags !== null) {
                            $newUpStreamSubmenu[] = $submenuMilestoneTags;
                        }
                        unset($submenuMilestoneTags);
                    }

                    $submenuTasks = $searchSubmenuItem('^tasks$');
                    if ($submenuTasks !== null) {
                        $newUpStreamSubmenu[] = $submenuTasks;
                    }
                    unset($submenuTasks);

                    $submenuBugs = $searchSubmenuItem('^bugs$');
                    if ($submenuBugs !== null) {
                        $newUpStreamSubmenu[] = $submenuBugs;
                    }
                    unset($submenuBugs);

                    if ($areClientsEnabled) {
                        $submenuClients = $searchSubmenuItem('^edit\.php\?post_type=client$');
                        if ($submenuClients !== null) {
                            $newUpStreamSubmenu[] = $submenuClients;
                        }
                        unset($submenuClients);
                    }

                    if ($areCategoriesEnabled) {
                        $submenuCategories = $searchSubmenuItem('^edit\-tags\.php\?taxonomy\=project_category\&amp;post_type=project$');
                        if ( ! $submenuCategories !== null) {
                            $newUpStreamSubmenu[] = $submenuCategories;
                        }
                        unset($submenuCategories);

                        $submenuTags = $searchSubmenuItem('^edit\-tags\.php\?taxonomy\=upstream_tag\&amp;post_type=project$');
                        if ( ! $submenuTags !== null) {
                            $newUpStreamSubmenu[] = $submenuTags;
                        }
                        unset($submenuTags);
                    }
                }

                $upstreamSubmenu = apply_filters('upstream:custom_menu_order', $newUpStreamSubmenu);
            }

            return $menu;
        }
    }

endif;

return new UpStream_Admin_Projects_Menu();
