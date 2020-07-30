<?php

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}


class UpStream_Model_Client extends UpStream_Model_Post_Object
{

    protected $userAssignments = [];

    protected $website = null;

    protected $address = null;

    protected $phone = null;

    protected $postType = 'client';

    protected $type = UPSTREAM_ITEM_TYPE_CLIENT;

    /**
     * UpStream_Model_Client constructor.
     */
    public function __construct($id)
    {
        if ($id > 0) {
            parent::__construct($id, [
                'website' => '_upstream_client_website',
                'address' => '_upstream_client_address',
                'phone' => '_upstream_client_phone',
                'userAssignments' => function ($m) {

                    if (isset($m['_upstream_new_client_users'][0])) {
                        $arr = unserialize($m['_upstream_new_client_users'][0]);

                        $out = [];
                        foreach ($arr as $item) {
                            $s = new stdClass;
                            $s->id = $item['user_id'];
                            $s->assignedBy = $item['assigned_by'];
                            $s->assignedAt = $item['assigned_at'];
                            $out[] = $s;
                        }
                        return $out;

                    }
                    return [];

                },
            ]);

        } else {
            parent::__construct(0, []);
        }

        $this->type = UPSTREAM_ITEM_TYPE_CLIENT;
    }


    public static function fields()
    {
        $fields = parent::fields();

        $fields['website'] = [ 'type' => 'string', 'title' => __('Website'), 'search' => true, 'display' => true  ];
        $fields['address'] = [ 'type' => 'string', 'title' => __('Address'), 'search' => true, 'display' => true  ];
        $fields['phone'] = [ 'type' => 'string', 'title' => __('Phone'), 'search' => true, 'display' => true  ];
        $fields['userIds'] = [ 'type' => 'user_id', 'is_array' => true, 'title' => __('Users'), 'search' => true, 'display' => true ];

        return $fields;
    }    

    public function store()
    {
        parent::store();

        if ($this->phone != null) update_post_meta($this->id, '_upstream_client_phone', $this->phone);
        if ($this->address != null) update_post_meta($this->id, '_upstream_client_address', $this->address);
        if ($this->website != null) update_post_meta($this->id, '_upstream_client_website', $this->website);

        if ($this->userAssignments != null) {

            $arr = [];
            foreach ($this->userAssignments as $assignment) {
                $arr[] = [
                    'user_id' => $assignment->id,
                    'assigned_by' => $assignment->assignedBy,
                    'assigned_at' => $assignment->assignedAt
                ];
            }
            update_post_meta($this->id, '_upstream_new_client_users', $arr);

        }
    }

    public function addUser($userId, $assignedBy)
    {
        if (get_userdata($userId) === false)
            throw new UpStream_Model_ArgumentException(sprintf(__('User ID %s does not exist.', 'upstream'), $userId));

        if (get_userdata($assignedBy) === false)
            throw new UpStream_Model_ArgumentException(sprintf(__('User ID %s does not exist.', 'upstream'), $assignedBy));

        foreach ($this->userAssignments as $assignment) {
            if ($assignment->id == $userId)
                throw new UpStream_Model_ArgumentException(sprintf(__('User ID %s is already attached.', 'upstream'), $assignedBy));
        }

        $assignment = new stdClass;
        $assignment->id = $userId;
        $assignment->assignedBy = $assignedBy;
        $assignment->assignedAt = date('Y-m-d H:i:s');

        $this->userAssignments[] = $assignment;
    }

    public function removeUser($userId)
    {
        for ($i = 0; $i < count($this->userAssignments); $i++) {
            if ($this->userAssignments[$i]->id == $userId) {
                array_splice($this->userAssignments, $i, 1);
                return;
            }
        }

        throw new UpStream_Model_ArgumentException(sprintf(__('User ID %s is not attached.', 'upstream'), $userId));
    }

    public function includesUser($userId)
    {
        for ($i = 0; $i < count($this->userAssignments); $i++) {
            if ($this->userAssignments[$i]->id == $userId) {
                return true;
            }
        }

        return false;
    }

    public function __get($property)
    {
        switch ($property) {

            case 'phone':
            case 'website':
            case 'address':
                return $this->{$property};

            case 'userIds':
                if ($this->userAssignments == null)
                    return [];

                $user_ids = [];
                foreach ($this->userAssignments as $assignment) {
                    $user_ids[] = $assignment->id;
                }

                return $user_ids;

            default:
                return parent::__get($property);

        }
    }

    public function __set($property, $value)
    {
        switch ($property) {

            case 'phone':
            case 'website':
                $this->{$property} = sanitize_text_field($value);
                break;

            case 'address':
                $this->{$property} = sanitize_textarea_field($value);
                break;

            case 'userIds':
                if (!is_array($value))
                    $value = [$value];

                foreach ($value as $user_id) {
                    try {
                        $this->addUser($user_id, get_current_user_id());
                    } catch (\UpStream_Model_ArgumentException $e) {
                        // ignore errors here
                    }
                }
                break;

            default:
                parent::__set($property, $value);
                break;

        }
    }


    public static function create($title, $createdBy)
    {
        if (get_userdata($createdBy) === false)
            throw new UpStream_Model_ArgumentException(__('User ID does not exist.', 'upstream'));

        $item = new \UpStream_Model_Client(0);

        $item->title = sanitize_text_field($title);
        $item->createdBy = $createdBy;

        return $item;
    }

}