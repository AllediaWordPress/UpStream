<?php
// Prevent direct access.
if ( ! defined('ABSPATH')) {
    exit;
}

use UpStream\Traits\Singleton;

/**
 * @since   1.15.0
 */
class UpStream_Ajax
{
    use Singleton;

    /**
     * UpStream_Ajax constructor.
     */
    public function __construct()
    {
        $this->setHooks();
    }

    /**
     * Set the hooks.
     */
    public function setHooks()
    {
        add_action('wp_ajax_upstream_ordering_update', [$this, 'orderingUpdate']);
        add_action('wp_ajax_upstream_collapse_update', [$this, 'collapseUpdate']);
        add_action('wp_ajax_upstream_panel_order_update', [$this, 'panelOrderUpdate']);

        add_action('wp_ajax_upstream_report_data', [$this, 'reportData']);

    }

    public function reportData()
    {
        $this->verifyNonce();

        $urg = UpStream_Report_Generator::get_instance();

        $report = $urg->getReport(sanitize_text_field($_POST['report']));
        $data = $urg->executeReport($report);

        $this->output($data);
    }

    /**
     * Update ordering state.
     */
    public function orderingUpdate()
    {
        $this->verifyNonce();

        if ( ! isset($_POST['column'])) {
            $this->output('column_not_found');

            return;
        }

        if ( ! isset($_POST['orderDir'])) {
            $this->output('order_dir_not_found');

            return;
        }

        if ( ! isset($_POST['tableId'])) {
            $this->output('table_id_not_found');

            return;
        }

        // Sanitize data.
        $tableId  = sanitize_text_field($_POST['tableId']);
        $column   = sanitize_text_field($_POST['column']);
        $orderDir = sanitize_text_field($_POST['orderDir']);

        if (empty($column) || empty($orderDir) || empty($tableId)) {
            $this->output('error');

            return;
        }

        \UpStream\Frontend\updateTableOrder($tableId, $column, $orderDir);

        $this->output('success');
    }

    /**
     * Update the collapse state.
     */
    public function collapseUpdate()
    {
        $this->verifyNonce();

        if ( ! isset($_POST['section'])) {
            $this->output('invalid_section');

            return;
        }

        if ( ! isset($_POST['state']) || ! in_array(sanitize_text_field($_POST['state']), ['opened', 'closed'])) {
            $this->output('invalid_state');

            return;
        }

        // already checked for validity
        $state = sanitize_text_field($_POST['state']);

        // Sanitize data.
        $section = sanitize_text_field($_POST['section']);

        if (empty($state) || empty($section)) {
            $this->output('error');

            return;
        }

        \UpStream\Frontend\updateSectionCollapseState($section, $state);

        $this->output('success');
    }

    /**
     * Update the panel ordering.
     */
    public function panelOrderUpdate()
    {
        $this->verifyNonce();

        if ( ! isset($_POST['rows'])) {
            $this->output('invalid_rows');

            return;
        }

        $rows = [];
        if (is_array($_POST['rows'])) {
            $rows = array_map('sanitize_text_field', $_POST['rows']);
        }

        if (empty($rows)) {
            $this->output('error');

            return;
        }

        \UpStream\Frontend\updatePanelOrder($rows);

        $this->output('success');
    }

    protected function verifyNonce()
    {
        if ( ! isset($_POST['nonce'])) {
            $this->output('security_error');

            return;
        }

        if ( ! wp_verify_nonce($_POST['nonce'], 'upstream-nonce')) {
            $this->output('security_error');

            return;
        }
    }

    /**
     * @param $return
     */
    protected function output($return)
    {
        echo wp_json_encode($return);
        wp_die();
    }
}

