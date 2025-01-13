<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpEventLog
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpEventLog extends ActiveRecord
{
    public const DB_TABLE_NAME = 'xvmp_event_log';
    public const ACTION_UPLOAD = 1;
    public const ACTION_EDIT = 2;
    public const ACTION_DELETE = 3;
    public const ACTION_ADD = 4;
    public const ACTION_REMOVE = 5;
    public const ACTION_CHANGE_OWNER = 6;
    protected static array $logged_media_fields = array(
        'title',
        'description',
        'published',
        'mediapermissions',
        'categories',
        'tags'
    );
    /**
     * @var ?int
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_fieldtype        integer
     * @db_length           8
     * @con_sequence        true
     */
    protected ?int $id = 0;
    /**
     * @var String
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected string $login;
    /**
     * @var int
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected int $action;
    /**
     * @var int
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected int $obj_id;
    /**
     * @var int
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected int $mid;
    /**
     * @var string|null
     */
    protected ?string $title;
    /**
     * @var Array
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           4000
     */
    protected array $data;
    /**
     * @var int
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected int $timestamp;

    public static function returnDbTableName() : string
    {
        return self::DB_TABLE_NAME;
    }

    public static function logEvent($action, $obj_id, $data, $old_data = null) : bool
    {
        $eventlog_data = array();

        switch ($action) {
            case self::ACTION_EDIT:
                foreach (self::$logged_media_fields as $field) {
                    if ($old_data[$field] != $data[$field]) {
                        $eventlog_data[$field] = array($old_data[$field], $data[$field]);
                    }
                }
                break;
            case self::ACTION_CHANGE_OWNER:
                $eventlog_data['owner'] = $data['owner'];
                break;
            default:
                foreach (self::$logged_media_fields as $field) {
                    $eventlog_data[$field] = $data[$field];
                }
                break;
        }

        if (empty($eventlog_data)) {
            return false;
        }

        $eventLog = new self();
        $eventLog->setMid($data['mid']);
        $eventLog->setObjId($obj_id);
        $eventLog->setAction($action);
        $eventLog->setTitle($data['title']);
        $eventLog->setData($eventlog_data);
        $eventLog->create();
        return true;
    }

    public function create() : void
    {
        global $DIC;
        $ilUser = $DIC['ilUser'];
        $this->setTimestamp(time());
        $this->setLogin($ilUser->getLogin());
        parent::create();
    }

    /**
     * @return String
     */
    public function getLogin() : string
    {
        return $this->login;
    }

    /**
     * @param String $login
     */
    public function setLogin(string $login) : void
    {
        $this->login = $login;
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id) : void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getAction() : int
    {
        return $this->action;
    }

    /**
     * @param int $action
     */
    public function setAction(int $action) : void
    {
        $this->action = $action;
    }

    /**
     * @return int
     */
    public function getObjId() : int
    {
        return $this->obj_id;
    }

    /**
     * @param int $obj_id
     */
    public function setObjId(int $obj_id) : void
    {
        $this->obj_id = $obj_id;
    }

    /**
     * @return int
     */
    public function getMid() : int
    {
        return $this->mid;
    }

    /**
     * @param int $mid
     */
    public function setMid(int $mid) : void
    {
        $this->mid = $mid;
    }

    /**
     * @return String
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    public function setTitle(string $title) : void
    {
        $this->title = $title;
    }

    /**
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data) : void
    {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getTimestamp() : int
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     */
    public function setTimestamp(int $timestamp) : void
    {
        $this->timestamp = $timestamp;
    }

    public function sleep($field_name) : bool|string|null
    {
        switch ($field_name) {
            case 'data':
                return json_encode($this->data);
            default:
                return parent::sleep($field_name);
        }
    }

    public function wakeUp($field_name, $field_value)
    {
        switch ($field_name) {
            case 'data':
                return json_decode($field_value, true);
            default:
                return parent::wakeUp($field_name, $field_value);
        }
    }
}
