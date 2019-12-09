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
if ( ! defined('ABSPATH') || class_exists('UpStream_Admin_Reviews')) {
    return;
}

/**
 * Class UpStream_Admin_Import
 */
class UpStream_Admin_Import
{
    public static function importFile($file)
    {
        if (true) {
            $arr = fgetcsv($file);
            self::importTable($arr);
        }
    }

    public static function importTable()
    {

    }

}
