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

    public static function getBuiltinReports()

    {
        if (!class_exists('UpStream_Report')) {
            return [];
        }

        $r = [];
        $r[] = new UpStream_Report_Projects();
        $r[] = new UpStream_Report_Milestones();
        $r[] = new UpStream_Report_Tasks();
        $r[] = new UpStream_Report_Milestone_Gantt_Chart();
        $r[] = new UpStream_Report_Task_Gantt_Chart();

        return $r;
    }

    public static function getAllReports()
    {
        $reports = self::getBuiltinReports();
        $reports = apply_filters('upstream_list_reports', $reports);
        return $reports;
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
