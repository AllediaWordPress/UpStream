<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Returns colorpicker default colors.
 *
 * @return
 */
function upstream_colorpicker_default_colors()
{
    $array = [
        '#D15C9C',
        '#9A5CD1',
        '#5C75D1',
        '#5CBFD1',
        '#5CD165',
        '#D1D15C',
        '#D1A65C',
        '#D17F5C',
        '#D15D5C',
    ];

    return apply_filters('upstream_colorpicker_default_colors', $array);
}

function upstream_render_labels_field_callback($field, $value, $object_id, $object_type, $field_type)
{

    // make sure we specify each part of the value we need.
    $value = wp_parse_args($value, [
        'single' => '',
        'plural' => '',
    ]); ?>
    <div class="alignleft"><p><label for="<?php echo $field_type->_id('_single'); ?>'"><?php _e(
                    'Single',
                    'upstream'
                ); ?></label></p>
        <?php echo $field_type->input([
            'name'  => $field_type->_name('[single]'),
            'id'    => $field_type->_id('_single'),
            'value' => $value['single'],
            'desc'  => '',
        ]); ?>
    </div>
    <div class="alignleft"><p><label for="<?php echo $field_type->_id('_plural'); ?>'"><?php _e(
                    'Plural',
                    'upstream'
                ); ?></label></p>
        <?php echo $field_type->input([
            'name'  => $field_type->_name('[plural]'),
            'id'    => $field_type->_id('_plural'),
            'value' => $value['plural'],
            'desc'  => '',
        ]); ?>
    </div>
    <br class="clear">
    <?php
    echo $field_type->_desc(true);
}

add_filter('cmb2_render_labels', 'upstream_render_labels_field_callback', 10, 5);
