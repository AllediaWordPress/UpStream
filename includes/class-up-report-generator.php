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
	protected static $instance;

    public $reports = [];

	/**
	 * UpStream_Report_Generator constructor.
	 */
	public function __construct( )
	{
		if (class_exists('UpStream_Report')) {
			$this->reports[] = new UpStream_Report_Projects();
		}
	}

	public function getAllReports()
    {
        return $this->reports;
    }

    public function getReport($id)
    {
        $reports = $this->getAllReports();

        foreach ($reports as $r) {
            if ($r->id === $id) {
                return $r;
            }
        }

        return null;
    }

    public function getReportFieldsFromPost($remove)
    {
        $report_fields = [];
        foreach ($_POST as $key => $value) {

            if (is_array($value)) {
                $v = [];
                foreach ($value as $itm) {
                    $v[] = sanitize_text_field($itm);
                }
                $value = $v;
            } else {
                $value = sanitize_text_field($value);
            }

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

    public function executeReport($report)
    {
        $data = $report->executeReport($this->getReportFieldsFromPost(true));
        return $data;
    }

	public static function get_instance()
	{
		if (empty(static::$instance)) {
			$instance = new self;
			static::$instance = $instance;
		}

		return static::$instance;
	}

}

function upstream_register_report($r, $display = false)
{
    if ($display) {
        \UpStream_Report_Generator::get_instance()->reports[] = $r;
    }
}
