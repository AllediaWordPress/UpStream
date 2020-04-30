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

    function getParameterOptions()
    {
        return [];
    }

    function executeReport($params)
    {

    }
}

class UpStream_Report_Projects extends UpStream_Report
{
    public $title = 'Projects';
    public $id = 'projects';

    function executeReport($params)
    {

    }
}