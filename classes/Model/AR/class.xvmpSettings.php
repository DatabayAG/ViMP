<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpSettings
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpSettings extends ActiveRecord
{
    public const DB_TABLE_NAME = 'xvmp_setting';

    public const LAYOUT_TYPE_LIST = 1;
    public const LAYOUT_TYPE_TILES = 2;
    public const LAYOUT_TYPE_PLAYER = 3;

    /**
     * @var int|null
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected ?int $obj_id;
    /**
     * @var int
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected int $is_online = 0;
    /**
     * @var int
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           2
     */
    protected int $layout_type = self::LAYOUT_TYPE_LIST;
    /**
     * @var int
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           2
     */
    protected int $repository_preview = 0;
    /**
     * @var int
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected int $lp_active = 0;

    /**
     * @var int
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           3
     */
    protected ?int $lp_mode = 0;

    public static function returnDbTableName() : string
    {
        return self::DB_TABLE_NAME;
    }

    /**
     * @return int
     */
    public function getIsOnline() : int
    {
        return $this->is_online;
    }

    /**
     * @param int $is_online
     */
    public function setIsOnline(int $is_online) : void
    {
        $this->is_online = $is_online;
    }

    /**
     * @return int
     */
    public function getLayoutType() : int
    {
        return $this->layout_type;
    }

    /**
     * @param int $layout_type
     */
    public function setLayoutType(int $layout_type) : void
    {
        $this->layout_type = $layout_type;
    }

    /**
     * @return int
     */
    public function getRepositoryPreview() : int
    {
        return $this->repository_preview;
    }

    /**
     * @param int $repository_preview
     */
    public function setRepositoryPreview(int $repository_preview) : void
    {
        $this->repository_preview = $repository_preview;
    }

    /**
     * @return bool
     */
    public function getLpActive() : bool
    {
        return $this->lp_active && xvmp::isLearningProgressPossible($this->getObjId());
    }

    /**
     * @param int $lp_active
     */
    public function setLpActive(int $lp_active) : void
    {
        $this->lp_active = $lp_active;
    }

    /**
     * @return int
     */
    public function getLpMode() : int
    {
        return $this->lp_mode;
    }

    /**
     * @param int $mode
     */
    public function setLpMode(int $mode) : void
    {
        $this->lp_mode = $mode;
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

}
