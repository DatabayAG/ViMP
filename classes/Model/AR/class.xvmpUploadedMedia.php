<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpUploadedMedia
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpUploadedMedia extends ActiveRecord
{
    public const DB_TABLE_NAME = 'xvmp_uploaded_media';
    /**
     * @var int|null
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected ?int $mid = 0;
    /**
     * @var string|int
     * @db_has_field        true
     * @db_is_unique        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected string|int $tmp_id = 0;
    /**
     * @var int|null
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected ?int $user_id = null;
    /**
     * @var ?int
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected ?int $ref_id = null;
    /**
     * @var ?string
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected ?string $email = null;
    /**
     * @var ?int
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected ?int $notification = 1;

    public static function returnDbTableName() : string
    {
        return self::DB_TABLE_NAME;
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

    public function getTmpId() : int|string
    {
        return $this->tmp_id;
    }

    /**
     * @param String $tmp_id
     */
    public function setTmpId(string $tmp_id) : void
    {
        $this->tmp_id = $tmp_id;
    }

    /**
     * @return int
     */
    public function getRefId() : int
    {
        return $this->ref_id;
    }

    /**
     * @param int $ref_id
     */
    public function setRefId(int $ref_id) : void
    {
        $this->ref_id = $ref_id;
    }

    /**
     * @return int
     */
    public function getUserId() : int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     */
    public function setUserId(int $user_id) : void
    {
        $this->user_id = $user_id;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email) : void
    {
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getNotification() : int
    {
        return $this->notification;
    }

    /**
     * @param int $notification
     */
    public function setNotification(int $notification) : void
    {
        $this->notification = $notification;
    }

}
