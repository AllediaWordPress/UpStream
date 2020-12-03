<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_File extends UpStream_Model_Meta_Object
{
    protected $fileId = 0;

    protected $upfsFileId = null;

    protected $createdAt = 0;

    protected $reminders = [];

    protected $metadataKey = '_upstream_project_files';

    protected $type = UPSTREAM_ITEM_TYPE_FILE;

    /**
     * UpStream_Model_File constructor.
     */
    public function __construct($parent, $item_metadata)
    {
        parent::__construct($parent, $item_metadata);

        $this->type = UPSTREAM_ITEM_TYPE_FILE;
    }


    protected function loadFromArray($item_metadata)
    {
        parent::loadFromArray($item_metadata);

        $this->createdAt = !empty($item_metadata['created_at']) ? $item_metadata['created_at'] : null;

        if (upstream_filesytem_enabled() && isset($item_metadata['file']) && upstream_upfs_info($item_metadata['file'])) {
            $this->upfsFileId = $item_metadata['file'];
        } elseif (isset($item_metadata['file']) && $item_metadata['file']) {
            $fid = @attachment_url_to_postid($item_metadata['file']);
            if ($fid) {
                $this->fileId = $fid;
            }
        }

        if (!$this->fileId && !$this->upfsFileId) {
            if (!empty($item_metadata['file_id'])) {
                $file = get_attached_file($item_metadata['file_id']);
                if ($file != false) {
                    $this->fileId = $item_metadata['file_id'];
                }
            }
        }

        if (!empty($item_metadata['reminders'])) {
            foreach ($item_metadata['reminders'] as $reminder_data) {

                try {
                    $d = json_decode($reminder_data, true);
                    $reminder = new UpStream_Model_Reminder($d);
                    $this->reminders[] = $reminder;
                } catch (\Exception $e) {
                    // don't add anything else
                }

            }
        }
    }

    public function storeToArray(&$item_metadata)
    {
        parent::storeToArray($item_metadata);

        if ($this->fileId > 0) {
            $url = wp_get_attachment_url($this->fileId);

            if ($url != false) {
                $item_metadata['file'] = $url;
                $item_metadata['file_id'] = $this->fileId;
            }
        } elseif ($this->upfsFileId && upstream_filesytem_enabled()) {
            $item_metadata['file'] = $this->upfsFileId;
        }

        if ($this->createdAt >= 0) $item_metadata['created_at'] = $this->createdAt;
        $item_metadata['reminders'] = [];

        foreach ($this->reminders as $reminder) {
            $r = [];
            $reminder->storeToArray($r);
            $item_metadata['reminders'][] = json_encode($r);
        }

    }

    public function __get($property)
    {
        switch ($property) {

            case 'fileId':
                if (upstream_filesytem_enabled())
                    return $this->upfsFileId;
                else
                    return $this->fileId;

            case 'filename':
                if (upstream_filesytem_enabled()) {
                    if ($file = upstream_upfs_info($this->upfsFileId)) {
                        return $file->orig_filename;
                    }
                } else {
                    $fileId = $this->fileId;
                    if ($fileId > 0) {
                        $file = get_attached_file($fileId);
                        return $file ? basename($file) : '';
                    }
                }
                return '';

            case 'fileURL':
                if (upstream_filesytem_enabled()) {
                    if ($file = upstream_upfs_info($this->upfsFileId)) {
                        return upstream_upfs_get_file_url($this->upfsFileId);
                    }
                } else {
                    $fileId = $this->fileId;
                    if ($fileId > 0) {
                        $url = wp_get_attachment_url($fileId);
                        return $url || '';
                    }
                }
                return '';

            case 'createdAt':
                if ($this->createdAt > 0)
                    return self::timestampToYMD($this->createdAt);
                else
                    return '';

            default:
                return parent::__get($property);
        }
    }

    public function __set($property, $value)
    {
        switch ($property) {

            case 'fileId':
                if (upstream_filesytem_enabled()) {
                    throw new UpStream_Model_ArgumentException(__('Set not implemented for Upfs.', 'upstream'));
                } else {
                    $file = get_attached_file($value);
                    if ($file === false)
                        throw new UpStream_Model_ArgumentException(sprintf(__('File ID %s is invalid.', 'upstream'), $value));

                    $this->fileId = $value;
                }
                break;

            default:
                parent::__set($property, $value);
                break;
        }
    }


    public static function fields()
    {
        $fields = parent::fields();

        $fields['fileId'] = [ 'type' => 'file', 'title' => __('File'), 'search' => false, 'display' => true ];
        $fields['createdAt'] = [ 'type' => 'date', 'title' => __('Upload Date'), 'search' => true, 'display' => true ];

        $fields = self::customFields($fields, UPSTREAM_ITEM_TYPE_FILE);

        return $fields;
    }

    public static function create($parent, $title, $createdBy)
    {
        $item_metadata = ['title' => $title, 'created_by' => $createdBy];

        return new self($parent, $item_metadata);
    }

}