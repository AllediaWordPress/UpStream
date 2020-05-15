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
if ( ! defined('ABSPATH') || class_exists('UpStream_Report_Generator')) {
    return;
}

/**
 * Class UpStream_Report
 */
class UpStream_Report_Generator
{
    public static $reports = [];

    public static function getAllReports()
    {
        return self::$reports;
    }

    public static function getReport($id)
    {
        $reports = self::getAllReports();

        foreach ($reports as $r) {
            if ($r->id === $id) {
                return $r;
            }
        }

        return null;
    }

    public static function getReportFieldsFromPost($remove)
    {
        $report_fields = [];
        foreach ($_POST as $key => $value) {
            if (stristr($key, 'upstream_report__')) {
                if ($remove) {
                    $report_fields[str_replace('upstream_report__', '', $key)] = $value;
                } else {
                    $report_fields[$key] = $value;
                }
            }
        }

        return $report_fields;
    }

    public static function executeReport($report)
    {
        $data = $report->executeReport(self::getReportFieldsFromPost(true));
        return $data;
    }

}

function upstream_register_report($r)
{
    \UpStream_Report_Generator::$reports[] = $r;
}