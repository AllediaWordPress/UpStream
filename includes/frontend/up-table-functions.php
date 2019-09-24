<?php

namespace UpStream\Frontend;

use UpStream\Exception;
use UpStream\Factory;
use UpStream\Milestones;
use UpStream_View;

function arrayToAttrs($data)
{
    $attrs = [];

    foreach ($data as $attrKey => $attrValue) {
        $attrs[] = sprintf('%s="%s"', $attrKey, esc_attr($attrValue));
    }

    return implode(' ', $attrs);
}

function getMilestonesFields($areCommentsEnabled = null)
{
    $schema = [
        'milestone'   => [
            'type'        => 'raw',
            'isOrderable' => true,
            'label'       => upstream_milestone_label(),

        ],
        'assigned_to' => [
            'type'        => 'user',
            'isOrderable' => true,
            'label'       => __('Assigned To', 'upstream'),
        ],
        'tasks'       => [
            'type'           => 'custom',
            'label'          => upstream_task_label_plural(),
            'isEditable'     => false,
            'renderCallback' => function ($columnName, $columnValue, $column, $row, $rowType, $projectId) {
                $tasksOpenCount = isset($row['task_open']) ? (int)$row['task_open'] : 0;
                $tasksCount     = isset($row['task_count']) ? (int)$row['task_count'] : 0;

                return sprintf(
                    '%d %s / %d %s',
                    $tasksOpenCount,
                    _x('Open', 'Open Tasks', 'upstream'),
                    $tasksCount,
                    _x('Total', 'Total number of Tasks', 'upstream')
                );
            },
        ],
        'progress'    => [
            'type'        => 'percentage',
            'isOrderable' => true,
            'label'       => __('Progress', 'upstream'),
            'isEditable'  => false,
        ],
        'start_date'  => [
            'type'        => 'date',
            'isOrderable' => true,
            'label'       => __('Start Date', 'upstream'),
        ],
        'end_date'    => [
            'type'        => 'date',
            'isOrderable' => true,
            'label'       => __('End Date', 'upstream'),
        ],
        'color' => [
            'type'  => 'colorpicker',
            'label' => __('Color', 'upstream'),
            'isHidden' => true,
        ],
        'notes'       => [
            'type'     => 'wysiwyg',
            'label'    => __('Notes', 'upstream'),
            'isHidden' => true,
        ],
        'comments'    => [
            'type'       => 'comments',
            'label'      => __('Comments'),
            'isHidden'   => true,
            'isEditable' => false,
        ],
    ];

    if ( ! upstream_disable_milestone_categories()) {
        $schema['categories'] = [
            'type'           => 'taxonomies',
            'isOrderable'    => true,
            'label'          => upstream_milestone_category_label_plural(),
            'renderCallback' => function ($columnName, $columnValue, $column, $row, $rowType, $projectId) {
                if (empty($columnValue)) {
                    if ( ! is_array($columnValue)) {
                        $columnValue = [$columnValue];
                    }

                    foreach ($columnValue as &$value) {
                        $term = get_term((int)$value);

                        if ( ! is_wp_error($term)) {
                            $value = $term->name;
                        }
                    }

                    $columnValue = implode(',', $columnValue);
                } else {
                    $columnValue = sprintf('<i class="s-text-color-gray">%s</i>', __('none', 'upstream'));
                }

                return $columnValue;
            },
        ];
    }

    if ($areCommentsEnabled === null) {
        $areCommentsEnabled = upstreamAreCommentsEnabledOnMilestones();
    }

    if ( ! $areCommentsEnabled) {
        unset($schema['comments']);
    }

    return apply_filters('upstream:project.milestones.fields', $schema);
}

function getTasksFields($statuses = [], $milestones = [], $areMilestonesEnabled = null, $areCommentsEnabled = null)
{
    if ($areMilestonesEnabled === null) {
        $areMilestonesEnabled = ! upstream_are_milestones_disabled() && ! upstream_disable_milestones();
    }

    $statuses = empty($statuses) ? getTasksStatuses() : $statuses;
    $options  = [];

    $schema = [
        'title'       => [
            'type'        => 'raw',
            'isOrderable' => true,
            'label'       => __('Title', 'upstream'),
        ],
        'assigned_to' => [
            'type'        => 'user',
            'isOrderable' => true,
            'label'       => __('Assigned To', 'upstream'),
        ],
        'status'      => [
            'type'           => 'custom',
            'label'          => __('Status', 'upstream'),
            'isOrderable'    => true,
            'renderCallback' => function ($columnName, $columnValue, $column, $row, $rowType, $projectId) use (
                &
                $statuses,
                &$options
            ) {
                if (strlen($columnValue) > 0) {
                    if (isset($statuses[$columnValue])) {
                        $columnValue = sprintf(
                            '<span class="label up-o-label" style="background-color: %s;">%s</span>',
                            $statuses[$columnValue]['color'],
                            $statuses[$columnValue]['name']
                        );
                    } else {
                        $columnValue = sprintf(
                            '<span class="label up-o-label" title="%s" style="background-color: %s;">%s <i class="fa fa-ban"></i></span>',
                            __("This Status doesn't exist anymore.", 'upstream'),
                            '#bdc3c7',
                            $columnValue
                        );
                    }
                } else {
                    $columnValue = sprintf('<i class="s-text-color-gray">%s</i>', __('none', 'upstream'));
                }

                return $columnValue;
            },
        ],
        'progress'    => [
            'type'        => 'percentage',
            'isOrderable' => true,
            'label'       => __('Progress', 'upstream'),
        ],
        'milestone'   => [
            'type'           => 'custom',
            'isOrderable'    => true,
            'label'          => upstream_milestone_label(),
            'renderCallback' => function ($columnName, $columnValue, $column, $row, $rowType, $projectId) {

                if ( ! empty($columnValue)) {
                    try {
                        $milestone = Factory::getMilestone($columnValue);

                        $columnValue = sprintf(
                            '<span class="label up-o-label" style="background-color: %s;">%s</span>',
                            $milestone->getColor(),
                            $milestone->getName()
                        );
                    } catch (Exception $e) {
                        $columnValue = sprintf(
                            '<span class="label up-o-label" title="%s" style="background-color: %s;">%s <i class="fa fa-ban"></i></span>',
                            __("This Milestone doesn't exist anymore.", 'upstream'),
                            '#bdc3c7',
                            $columnValue
                        );
                    }
                } else {
                    $columnValue = sprintf('<i class="s-text-color-gray">%s</i>', __('none', 'upstream'));
                }

                return $columnValue;
            },
        ],
        'start_date'  => [
            'type'        => 'date',
            'isOrderable' => true,
            'label'       => __('Start Date', 'upstream'),
        ],
        'end_date'    => [
            'type'        => 'date',
            'isOrderable' => true,
            'label'       => __('End Date', 'upstream'),
        ],
        'notes'       => [
            'type'     => 'wysiwyg',
            'label'    => __('Notes', 'upstream'),
            'isHidden' => true,
        ],
        'comments'    => [
            'type'       => 'comments',
            'label'      => __('Comments'),
            'isHidden'   => true,
            'isEditable' => false,
        ],
    ];

    if ($areCommentsEnabled === null) {
        $areCommentsEnabled = upstreamAreCommentsEnabledOnTasks();
    }

    if ($areMilestonesEnabled === false) {
        unset($schema['milestone']);
    }

    if ( ! $areCommentsEnabled) {
        unset($schema['comments']);
    }

    return apply_filters('upstream:project.tasks.fields', $schema);
}

function getBugsFields($severities = [], $statuses = [], $areCommentsEnabled = null)
{
    if (empty($severities)) {
        $severities = getBugsSeverities();
    }

    if (empty($statuses)) {
        $statuses = getBugsStatuses();
    }

    $options = null;

    $schema = [
        'title'       => [
            'type'        => 'raw',
            'isOrderable' => true,
            'label'       => __('Title', 'upstream'),
        ],
        'assigned_to' => [
            'type'        => 'user',
            'isOrderable' => true,
            'label'       => __('Assigned To', 'upstream'),
        ],
        'severity'    => [
            'type'           => 'custom',
            'label'          => __('Severity', 'upstream'),
            'isOrderable'    => true,
            'renderCallback' => function ($columnName, $columnValue, $column, $row, $rowType, $projectId) use (
                &
                $severities,
                &$options
            ) {
                if (strlen($columnValue) > 0) {
                    if (isset($severities[$columnValue])) {
                        $columnValue = sprintf(
                            '<span class="label up-o-label" style="background-color: %s;">%s</span>',
                            $severities[$columnValue]['color'],
                            $severities[$columnValue]['name']
                        );
                    } else {
                        $columnValue = sprintf(
                            '<span class="label up-o-label" title="%s" style="background-color: %s;">%s <i class="fa fa-ban"></i></span>',
                            __("This Severity doesn't exist anymore.", 'upstream'),
                            '#bdc3c7',
                            $columnValue
                        );
                    }
                } else {
                    $columnValue = sprintf('<i class="s-text-color-gray">%s</i>', __('none', 'upstream'));
                }

                return $columnValue;
            },
        ],
        'status'      => [
            'type'           => 'custom',
            'label'          => __('Status', 'upstream'),
            'isOrderable'    => true,
            'renderCallback' => function ($columnName, $columnValue, $column, $row, $rowType, $projectId) use (
                &
                $statuses,
                &$options
            ) {
                if (strlen($columnValue) > 0) {
                    if (isset($statuses[$columnValue])) {
                        $columnValue = sprintf(
                            '<span class="label up-o-label" style="background-color: %s;">%s</span>',
                            $statuses[$columnValue]['color'],
                            $statuses[$columnValue]['name']
                        );
                    } else {
                        $columnValue = sprintf(
                            '<span class="label up-o-label" title="%s" style="background-color: %s;">%s <i class="fa fa-ban"></i></span>',
                            __("This Status doesn't exist anymore.", 'upstream'),
                            '#bdc3c7',
                            $columnValue
                        );
                    }
                } else {
                    $columnValue = sprintf('<i class="s-text-color-gray">%s</i>', __('none', 'upstream'));
                }

                return $columnValue;
            },
        ],
        'due_date'    => [
            'type'        => 'date',
            'isOrderable' => true,
            'label'       => __('Due Date', 'upstream'),
        ],
        'file'        => [
            'type'        => 'file',
            'isOrderable' => false,
            'label'       => __('File', 'upstream'),
        ],
        'description' => [
            'type'     => 'wysiwyg',
            'label'    => __('Description', 'upstream'),
            'isHidden' => true,
        ],
        'comments'    => [
            'type'       => 'comments',
            'label'      => __('Comments'),
            'isHidden'   => true,
            'isEditable' => false,
        ],
    ];

    if ($areCommentsEnabled === null) {
        $areCommentsEnabled = upstreamAreCommentsEnabledOnBugs();
    }

    if ( ! $areCommentsEnabled) {
        unset($schema['comments']);
    }

    return apply_filters('upstream:project.bugs.fields', $schema);
}

function getFilesFields($areCommentsEnabled = null)
{
    $schema = [
        'title'       => [
            'type'        => 'raw',
            'isOrderable' => true,
            'label'       => __('Title', 'upstream'),
        ],
        'created_by'  => [
            'type'        => 'user',
            'isOrderable' => true,
            'label'       => __('Uploaded by', 'upstream'),
            'isEditable'  => false,
        ],
        'created_at'  => [
            'type'        => 'date',
            'isOrderable' => true,
            'label'       => __('Upload Date', 'upstream'),
            'isEditable'  => false,
        ],
        'assigned_to' => [
            'type'        => 'user',
            'isOrderable' => false,
            'label'       => __('Assigned To', 'upstream'),
        ],
        'file'        => [
            'type'        => 'file',
            'isOrderable' => false,
            'label'       => __('File', 'upstream'),
        ],
        'description' => [
            'type'     => 'wysiwyg',
            'label'    => __('Description', 'upstream'),
            'isHidden' => true,
        ],
        'comments'    => [
            'type'       => 'comments',
            'label'      => __('Comments'),
            'isHidden'   => true,
            'isEditable' => false,
        ],
    ];

    if ($areCommentsEnabled === null) {
        $areCommentsEnabled = upstreamAreCommentsEnabledOnFiles();
    }

    if ( ! $areCommentsEnabled) {
        unset($schema['comments']);
    }

    return apply_filters('upstream:project.files.fields', $schema);
}

function getProjectFields($statuses = [])
{
    if (empty($statuses)) {
        $statuses = upstream_get_all_project_statuses();
    }

    $options = null;

    $schema = [
        'title'        => [
            'type'        => 'raw',
            'isOrderable' => true,
            'label'       => __('Title', 'upstream'),
        ],
        'status'       => [
            'type'           => 'custom',
            'label'          => __('Status', 'upstream'),
            'isOrderable'    => true,
            'renderCallback' => function ($columnName, $columnValue, $column, $row, $rowType, $projectId) use (
                &
                $statuses,
                &$options
            ) {
                if (strlen($columnValue) > 0) {
                    if (isset($statuses[$columnValue])) {
                        $columnValue = sprintf(
                            '<span class="label up-o-label" style="background-color: %s;">%s</span>',
                            $statuses[$columnValue]['color'],
                            $statuses[$columnValue]['name']
                        );
                    } else {
                        $columnValue = sprintf(
                            '<span class="label up-o-label" title="%s" style="background-color: %s;">%s <i class="fa fa-ban"></i></span>',
                            __("This Status doesn't exist anymore.", 'upstream'),
                            '#bdc3c7',
                            $columnValue
                        );
                    }
                } else {
                    $columnValue = sprintf('<i class="s-text-color-gray">%s</i>', __('none', 'upstream'));
                }

                return $columnValue;
            },
        ],
        'owner'        => [
            'type'        => 'user',
            'isOrderable' => true,
            'label'       => __('Owner', 'upstream'),
        ],
        'client'       => [
            'type'        => 'custom',
            'isOrderable' => true,
            'label'       => upstream_client_label(),
        ],
        'client_users' => [
            'type'           => 'array',
            'label'          => __('Client users', 'upstream'),
            'isOrderable'    => true,
            'renderCallback' => function ($columnName, $columnValue, $column, $row, $rowType, $projectId) use (
                &
                $statuses,
                &$options
            ) {
                if (strlen($columnValue) > 0) {
                    if (isset($statuses[$columnValue])) {
                        $columnValue = sprintf(
                            '<span class="label up-o-label" style="background-color: %s;">%s</span>',
                            $statuses[$columnValue]['color'],
                            $statuses[$columnValue]['name']
                        );
                    } else {
                        $columnValue = sprintf(
                            '<span class="label up-o-label" title="%s" style="background-color: %s;">%s <i class="fa fa-ban"></i></span>',
                            __("This Status doesn't exist anymore.", 'upstream'),
                            '#bdc3c7',
                            $columnValue
                        );
                    }
                } else {
                    $columnValue = sprintf('<i class="s-text-color-gray">%s</i>', __('none', 'upstream'));
                }

                return $columnValue;
            },
        ],
        'start'        => [
            'type'        => 'date',
            'isOrderable' => true,
            'label'       => __('Start', 'upstream'),
        ],
        'end'          => [
            'type'        => 'date',
            'isOrderable' => true,
            'label'       => __('End', 'upstream'),
        ],

        'description' => [
            'type'     => 'wysiwyg',
            'label'    => __('Description', 'upstream'),
            'isHidden' => true,
        ],
    ];

    return apply_filters('upstream:project.fields', $schema);
}

function renderTableHeaderColumn($identifier, $data)
{
    $attrs = [
        'data-column' => $identifier,
        'class'       => isset($data['class']) ? (is_array($data['class']) ? implode(
            ' ',
            $data['class']
        ) : $data['class']) : '',
    ];

    $isHidden = isset($data['isHidden']) && (bool)$data['isHidden'];
    if ($isHidden) {
        return;
    }

    $isOrderable = isset($data['isOrderable']) && (bool)$data['isOrderable'];
    if ($isOrderable) {
        $attrs['class'] .= ' is-clickable is-orderable';
        $attrs['role']  = 'button';
        $attrs['scope'] = 'col';
    } ?>
    <th <?php echo arrayToAttrs($attrs); ?>>
        <?php echo isset($data['label']) ? $data['label'] : ''; ?>
        <?php if ($isOrderable): ?>
            <span class="pull-right o-order-direction">
          <i class="fa fa-sort"></i>
        </span>
        <?php endif; ?>
    </th>
    <?php
}

function renderTableHeader($columns = [], $itemType = null)
{
    if (is_null($itemType)) {
        return;
    }

    ob_start(); ?>
    <thead>
    <?php if ( ! empty($columns)): ?>
        <tr scope="row">
            <?php
            foreach ($columns as $columnIdentifier => $column) {
                echo renderTableHeaderColumn($columnIdentifier, $column);
            } ?>

            <?php do_action('upstream_table_columns_header', ['type' => $itemType], $columns); ?>
        </tr>
    <?php endif; ?>
    </thead>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    echo $html;
}

function renderTableColumnValue($columnName, $columnValue, $column, $row, $rowType, $projectId)
{
    $isHidden = isset($column['isHidden']) && (bool)$column['isHidden'] === true;

    $html       = sprintf('<i class="s-text-color-gray">%s</i>', __('none', 'upstream'));
    $columnType = isset($column['type']) ? $column['type'] : 'raw';

    // Detect color values
    if ($columnType === 'raw' && preg_match('/^(#[0-9a-f]+|rgba?\()/i', $columnValue)) {
        $columnType = 'colorpicker';
    }

    if ($columnType === 'user') {
        if ( ! is_array($columnValue)) {
            $columnValue = (array)$columnValue;
        }

        $names = upstream_get_users_display_name($columnValue);

        // RSD: for some reason upstream_get_users_display_name returns 0 when there's nothign to show
        // this fixes the display
        $html = ($names != "0") ? $names : $html;
    } elseif ($columnType === 'taxonomies') {
        if ( ! is_array($columnValue)) {
            $columnValue = (array)$columnValue;
        }

        $html = '';

        if ( ! empty($columnValue)) {
            $names = [];

            foreach ($columnValue as $value) {
                if (is_numeric($value)) {
                    $term = get_term((int)$value);

                    $names[] = $term->name;
                } else {
                    $names[] = $value;
                }
            }

            $html = implode(', ', $names);
        }
    } elseif ($columnType === 'percentage') {
        $html = sprintf('%d%%', (int)$columnValue);
    } elseif ($columnType === 'date') {
        $columnValue = (int)$columnValue;
        if ($columnValue > 0) {
            // RSD: timezone offset is here to ensure compatibility with previous wrong data
            // TODO: should remove at some point
            $html = upstream_format_date($columnValue + UpStream_View::getTimeZoneOffset());
        }
        $offset  = get_option( 'gmt_offset' );
        //$html .= "(". upstream_format_date($columnValue ) ."  " .$columnValue." / ".(($columnValue/3600)%24)." " .(UpStream_View::getTimeZoneOffset() . " // " . ($offset>0 ? $offset*60*60 : 0)).")";

    } elseif ($columnType === 'wysiwyg') {
        $columnValue = preg_replace('/(?!>[\s]*).\r?\n(?![\s]*<)/', '$0<br />', trim((string)$columnValue));
        if (strlen($columnValue) > 0) {
            $html = sprintf('<blockquote>%s</blockquote>', html_entity_decode($columnValue));
        } else {
            $html = '<br>' . $html;
        }
    } elseif ($columnType === 'comments') {
        $html = upstreamRenderCommentsBox($row['id'], $rowType, $projectId, false, true);
    } elseif ($columnType === 'custom') {
        if (isset($column['renderCallback']) && is_callable($column['renderCallback'])) {
            $html = call_user_func(
                $column['renderCallback'],
                $columnName,
                $columnValue,
                $column,
                $row,
                $rowType,
                $projectId
            );
        }
    } elseif ($columnType === 'file') {
        if (strlen($columnValue) > 0) {
            if (@is_array(getimagesize($columnValue))) {
                $html = sprintf(
                    '<a href="%s" target="_blank">
                <img class="avatar itemfile" width="32" height="32" src="%1$s">
              </a>',
                    $columnValue
                );
            } else {
                $html = sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    $columnValue,
                    basename($columnValue)
                );
            }
        } elseif ($isHidden) {
            $html = '<br>' . $html;
        }
    } elseif ($columnType === 'array') {
        $columnValue = array_filter((array)$columnValue);

        if (isset($column['options'])) {
            $values = [];

            if (is_array($column['options'])) {
                foreach ($columnValue as $value) {
                    if (isset($column['options'][$value])) {
                        $values[] = $column['options'][$value];
                    }
                }
            }

            $values = implode(', ', $values);
        } elseif ( ! empty($columnValue)) {
            $values = implode(', ', $columnValue);
        }

        if ( ! empty($values)) {
            if ($isHidden) {
                $html = '<br><span data-value="' . implode(',', $columnValue) . '">' . $values . '</span>';
            } else {
                $html = '<br><span>' . implode(',', $columnValue) . '</span>';
            }
        } else {
            $html = '<br>' . $html;
        }
    } elseif ($columnType === 'colorpicker') {
        $columnValue = trim((string)$columnValue);
        if (strlen($columnValue) > 0) {
            $html = '<br><div class="up-c-color-square has-tooltip" data-toggle="tooltip" title="' . $columnValue . '">';
            $html .= '<div style="background-color: ' . $columnValue . '"></div>';
            $html .= '</div>';
        }

        if ($isHidden) {
            $html = '<span data-value="' . esc_attr($columnValue) . '">' . $html . '</span>';
        }
    } elseif ($columnType === 'radio') {
        if (is_array($columnValue)) {
            $columnValue = $columnValue[0];
        }

        $columnValue = trim((string)$columnValue);

        if (strlen($columnValue) > 0) {
            if ($columnValue === '__none__') {
                $html = '<i class="s-text-color-gray">' . _('none', 'upstream') . '</i>';
            } else {
                $html = esc_html($columnValue);
            }
        }

        $html = '<br>' . $html;

        if ($isHidden) {
            $html = '<span data-value="' . esc_attr($columnValue) . '">' . $html . '</span>';
        }
    } else {
        if (is_array($columnValue)) {
            $columnValue = $columnValue[0];
        }

        $columnValue = trim((string)$columnValue);
        if (strlen($columnValue) > 0) {
            $html = esc_html($columnValue);
        }

        if ($isHidden) {
            $html = '<span data-value="' . esc_attr($columnValue) . '">' . $html . '</span>';
        }

        // TODO: RSD: why is this here?
        //$html = '<br>' . $html;
    }

    $html = apply_filters(
        'upstream:frontend:project.table.body.td_value',
        $html,
        $columnName,
        $columnValue,
        $column,
        $row,
        $rowType,
        $projectId
    );

    echo $html;
}

function renderTableBody($data, $visibleColumnsSchema, $hiddenColumnsSchema, $rowType, $projectId, $tableSettings = [])
{
    $visibleColumnsSchemaCount = count($visibleColumnsSchema);
    ob_start(); ?>
    <tbody>
    <?php if (count($data) > 0):
        $isRowIndexOdd = true; ?>
        <?php foreach ($data as $id => $row):
        $rowAttrs = [
            'class'   => 'is-filtered t-row-' . ($isRowIndexOdd ? 'odd' : 'even'),
            'data-id' => $id,
        ];

        if ( ! empty($hiddenColumnsSchema)) {
            $rowAttrs['class']         .= ' is-expandable';
            $rowAttrs['aria-expanded'] = 'false';
        }

        $isFirst = true; ?>
        <tr <?php echo arrayToAttrs($rowAttrs); ?>>
            <?php foreach ($visibleColumnsSchema as $columnName => $column):
                $columnValue = isset($row[$columnName]) ? $row[$columnName] : null;

                if (in_array($column['type'], ['user', 'array'])) {
                    if ( ! is_array($columnValue)) {
                        $columnValue = [(int)$columnValue];
                    }
                }

                if ($column['type'] === 'taxonomies' && is_array($columnValue)) {
                    $columnValue = Milestones::getInstance()->getCategoriesNames($columnValue);
                }

                $columnAttrs = [
                    'data-column' => $columnName,
                    'data-value'  => is_array($columnValue) ? implode(', ', $columnValue) : $columnValue,
                    'data-type'   => $column['type'],
                ];

                // Check if we have an specific value in the column, for ordering.
                $columnAttrs['data-order'] = $columnAttrs['data-value'];
                if (isset($row[$columnName . '_order'])) {
                    $columnAttrs['data-order'] = $row[$columnName . '_order'];
                }

                if ($isFirst) {
                    $columnAttrs['class'] = 'is-clickable';
                    $columnAttrs['role']  = 'button';
                } ?>
                <td <?php echo arrayToAttrs($columnAttrs); ?>>
                    <?php if ($isFirst): ?>
                        <i class="fa fa-angle-right"></i>&nbsp;
                    <?php endif; ?>

                    <?php renderTableColumnValue($columnName, $columnValue, $column, $row, $rowType, $projectId); ?>
                </td>


                <?php $isFirst = false; ?>
            <?php endforeach; ?>

            <?php do_action('upstream_table_columns_data', $tableSettings, $visibleColumnsSchema, $projectId, $row); ?>
        </tr>

        <?php if ( ! empty($hiddenColumnsSchema)): ?>
        <tr data-parent="<?php echo $id; ?>" aria-expanded="false" style="display: none;">
            <td colspan="<?php echo $visibleColumnsSchemaCount; ?>">
                <div>
                    <?php foreach ($hiddenColumnsSchema as $columnName => $column):
                        $columnValue = isset($row[$columnName]) ? $row[$columnName] : null; ?>
                        <div class="form-group" data-column="<?php echo $columnName; ?>">
                            <label><?php echo isset($column['label']) ? $column['label'] : ''; ?></label>
                            <?php renderTableColumnValue(
                                $columnName,
                                $columnValue,
                                $column,
                                $row,
                                $rowType,
                                $projectId
                            ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
    <?php endif;
        $isRowIndexOdd = ! $isRowIndexOdd; ?>
    <?php endforeach; ?>
    <?php else: ?>
        <tr data-empty>
            <td colspan="<?php echo $visibleColumnsSchemaCount; ?>">
                <?php _e('No results found.', 'upstream'); ?>
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    echo $html;
}

function renderTable($tableAttrs = [], $columnsSchema = [], $data = [], $itemType = '', $projectId = 0)
{
    $tableAttrs['class'] = array_filter(isset($tableAttrs['class']) ? (! is_array($tableAttrs['class']) ? explode(
        ' ',
        $tableAttrs['class']
    ) : (array)$tableAttrs['class']) : []);
    $tableAttrs['class'] = array_unique(array_merge($tableAttrs['class'], [
        'o-data-table',
        'table',
        'table-bordered',
        'table-responsive',
        'table-hover',
        'is-orderable',
    ]));

    $tableAttrs['cellspacing'] = 0;
    $tableAttrs['width']       = '100%';


    $visibleColumnsSchema = [];
    $hiddenColumnsSchema  = [];

    foreach ($columnsSchema as $columnName => $columnArgs) {
        if (isset($columnArgs['isHidden']) && (bool)$columnArgs['isHidden'] === true) {
            $hiddenColumnsSchema[$columnName] = $columnArgs;
        } else {
            $visibleColumnsSchema[$columnName] = $columnArgs;
        }
    }

    // Get the table ordering, if set.
    $tableId = array_key_exists('id', $tableAttrs) ? $tableAttrs['id'] : '';

    if ( ! empty($tableId)) {
        $ordering = getTableOrder($tableId);

        if ( ! empty($ordering)) {
            $tableAttrs['data-ordered-by'] = $ordering['column'];
            $tableAttrs['data-order-dir']  = $ordering['orderDir'];
        }
    }

    $tableAttrs['class'] = implode(' ', $tableAttrs['class']); ?>
    <table <?php echo arrayToAttrs($tableAttrs); ?>>
        <?php renderTableHeader($visibleColumnsSchema, $itemType); ?>
        <?php renderTableBody($data, $visibleColumnsSchema, $hiddenColumnsSchema, $itemType, $projectId,
            $tableAttrs); ?>
    </table>
    <?php
    $optArr = array(
        'milestone' => upstream_milestone_label_plural(),
        'task'      => upstream_task_label_plural(),
        'bug'       => upstream_bug_label_plural(),
        'file'      => upstream_file_label_plural(),
    );
    $countValue = count($data) > 0 ? count($data) : '';
    echo "<span class='sub_count p_count' id='" . $itemType . "_count'>" . $countValue . "</span>";
    ?>
    <span class="p_count">
        <?php
            if (count($data) > 0) {
                echo sprintf(_x(' %s found', 'upstream'), $optArr[$itemType]);
            }
        ?>
    </span>
    <?php
}

function renderTableFilter($filterType, $columnName, $args = [], $renderFormGroup = true)
{
    if ( ! in_array($filterType, ['search', 'select'])
         || empty($columnName)
    ) {
        return false;
    }

    $renderFormGroup = (bool)$renderFormGroup;

    $isHidden = ! isset($args['hidden']) || (isset($args['hidden']) && (bool)$args['hidden'] === true);

    ob_start();

    if ($renderFormGroup) {
        echo '<div class="form-group">';
    }

    if ($filterType === 'search') {
        $inputAttrs = [
            'type'                  => 'search',
            'class'                 => 'form-control',
            'data-column'           => $columnName,
            'data-compare-operator' => isset($args['operator']) ? $args['operator'] : 'contains',
        ];

        if (isset($args['attrs']) && ! empty($args['attrs'])) {
            $inputAttrs = array_merge($args['attrs'], $inputAttrs);
        } ?>
        <div class="input-group">
            <div class="input-group-addon">
                <i class="fa fa-search"></i>
            </div>
            <input <?php echo arrayToAttrs($inputAttrs); ?>>
        </div>
        <?php
    } elseif ($filterType === 'select') {
        $inputAttrs = [
            'class'                 => 'form-control o-select2',
            'data-column'           => $columnName,
            'multiple'              => 'multiple',
            'data-compare-operator' => isset($args['operator']) ? $args['operator'] : 'contains',
        ];

        if (isset($args['attrs']) && ! empty($args['attrs'])) {
            $inputAttrs = array_merge($args['attrs'], $inputAttrs);
        }

        $hasIcon = isset($args['icon']) && ! empty($args['icon']);
        if ($hasIcon): ?>
            <div class="input-group">
            <div class="input-group-addon">
                <i class="fa fa-filter"></i>
            </div>
        <?php endif; ?>

        <select <?php echo arrayToAttrs($inputAttrs); ?>>
            <option value></option>
            <option value="__none__"><?php _e('None', 'upstream'); ?></option>
            <?php
            if (isset($args['options']) && is_array($args['options']) && count($args['options'])): ?>
                <?php foreach ($args['options'] as $optionValue => $optionLabel): ?>
                    <option value="<?php echo (string)$optionValue; ?>"><?php echo $optionLabel; ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <?php if ($hasIcon): ?>
            </div>
        <?php endif;
    }

    if ($renderFormGroup) {
        echo '</div>';
    }

    $filterHtml = ob_get_contents();
    ob_end_clean();

    echo $filterHtml;
}

/**
 * @param $tableId
 *
 * @return string
 */
function getTableOrderOption($tableId)
{
    $userId = get_current_user_id();

    return 'upstream_ordering_' . $userId . '_' . $tableId;
}

/**
 * @param $tableId
 * @param $column
 * @param $dir
 */
function updateTableOrder($tableId, $column, $dir)
{
    // Update the ordering data for the table.
    $data = maybe_serialize([
        'column'   => $column,
        'orderDir' => $dir,
    ]);

    $option = getTableOrderOption($tableId);

    update_option($option, $data);
}

/**
 * @param $tableId
 *
 * @return array
 */
function getTableOrder($tableId)
{
    $option = getTableOrderOption($tableId);

    $value = maybe_unserialize(get_option($option));

    if ( ! is_array($value) || ! array_key_exists('column', $value) || ! array_key_exists('orderDir', $value)) {
        $value = false;
    }

    return $value;
}

/**
 * @param $section
 *
 * @return string
 */
function getSectionCollapseStateOption($section)
{
    $userId = get_current_user_id();

    return 'upstream_collapse_state_' . $userId . '_' . $section;
}

/**
 * @param $section
 * @param $state
 */
function updateSectionCollapseState($section, $state)
{
    $option = getSectionCollapseStateOption($section);

    $state = sanitize_text_field($state);

    update_option($option, $state);
}

/**
 * @param $section
 *
 * @return array
 */
function getSectionCollapseState($section)
{
    $option = getSectionCollapseStateOption($section);

    $value = get_option($option);

    if (empty($value)) {
        $value = false;
    }

    return $value;
}

/**
 * @param $rows
 */
function updatePanelOrder($rows)
{
    $option = 'upstream_panel_order';

    $value = [];

    foreach ($rows as $row) {
        $row = sanitize_text_field($row);
        $row = str_replace('project-section-', '', $row);

        if ( ! empty($row)) {
            $value[] = $row;
        }
    }

    update_option($option, $value);
}

/**
 * @return array
 */
function getPanelOrder()
{
    return get_option('upstream_panel_order');
}
