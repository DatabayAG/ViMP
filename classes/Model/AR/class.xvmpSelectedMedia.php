<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpSelectedMedia
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpSelectedMedia extends ActiveRecord
{
    public const DB_TABLE_NAME = 'xvmp_selected_media';

    public const F_VISIBLE = 'visible';


    public static function returnDbTableName(): string
    {
        return self::DB_TABLE_NAME;
    }


    /**
     * @var int
     *
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_fieldtype        integer
     * @db_length           8
     * @con_sequence        true
     */
    protected ?int $id = 0;
    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected ?int $obj_id;
    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected int $mid;
    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected int $visible = 1;

    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected int $lp_is_required = 0;

    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected int $lp_req_percentage = 80;
    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected int $sort;


    /**
     * @param $mid
     * @param $obj_id
     *
     * @return bool
     */
    public static function isSelected($mid, $obj_id): bool
    {
        return self::where(array('mid' => $mid, 'obj_id' => $obj_id))->hasSets();
    }


    /**
     * @param int $mid
     * @param int $obj_id
     * @param bool $visible
     * @return bool
     * @throws xvmpException
     */
    public static function addVideo(int $mid, int $obj_id, bool $visible = true): bool
    {
        $set = self::where(array('mid' => $mid, 'obj_id' => $obj_id))->first();
        if ($set) {
            return false; // already added
        }

        $set = new self();
        $set->setMid($mid);
        $set->setObjId($obj_id);
        $set->setVisible((int) $visible);
        $sort = self::where(array('obj_id' => $obj_id))->count() + 1;
        $set->setSort($sort * 10);
        $set->create();

        // this will add the video to the cache
        $video = xvmpMedium::getObjectAsArray($mid);

        // log event
        xvmpEventLog::logEvent(xvmpEventLog::ACTION_ADD, $obj_id, $video);

        return true;
    }

    /**
     * @param $mid
     * @param $obj_id
     *
     * @return bool
     * @throws xvmpException
     */
    public static function removeVideo($mid, $obj_id): bool
    {
        $set = self::where(array('mid' => $mid, 'obj_id' => $obj_id))->first();
        if (!$set) {
            return false; // already added
        }

        $set->delete();
        self::reSort($obj_id);

        // log event
        $video = xvmpMedium::getObjectAsArray($mid);
        xvmpEventLog::logEvent(xvmpEventLog::ACTION_REMOVE, $obj_id, $video);

        // remove from cache
        xvmpCacheFactory::getInstance()->delete(xvmpMedium::class . '-' . $mid);

        return true;
    }

    public static function deleteVideo($mid)
    {
        /** @var self $selected */
        foreach (self::where(array('mid' => $mid))->get() as $selected) {
            $selected->delete();
        }
    }


    /**
     * @param $obj_id
     * @param bool $visible_only
     * @return self[]
     * @throws arException
     */
    public static function getSelected($obj_id, bool $visible_only = false): array
    {
        $where = array('obj_id' => $obj_id);
        if ($visible_only) {
            $where['visible'] = true;
        }
        return self::where($where)->orderBy('sort')->get();
    }


    /**
     *
     * @throws arException
     */
    public static function reSort($obj_id)
    {
        $i = 1;
        foreach (self::getSelected($obj_id) as $item) {
            $item->setSort($i * 10);
            $item->update();
            $i++;
        }
    }


    /**
     * @param $mid
     * @param $obj_Id
     */
    public static function moveUp($mid, $obj_Id)
    {
        /** @var self $medium */
        $medium = self::where(array('mid' => $mid, 'obj_id' => $obj_Id))->first();
        $medium->setSort($medium->getSort() - 15);
        $medium->update();
        self::reSort($obj_Id);
    }

    public static function moveDown($mid, $obj_Id)
    {
        /** @var self $medium */
        $medium = self::where(array('mid' => $mid, 'obj_id' => $obj_Id))->first();
        $medium->setSort($medium->getSort() + 15);
        $medium->update();
        self::reSort($obj_Id);
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }


    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }


    /**
     * @return int
     */
    public function getObjId(): int
    {
        return $this->obj_id;
    }


    /**
     * @param int $obj_id
     */
    public function setObjId(int $obj_id)
    {
        $this->obj_id = $obj_id;
    }


    /**
     * @return int
     */
    public function getMid(): int
    {
        return $this->mid;
    }


    /**
     * @param int $mid
     */
    public function setMid(int $mid)
    {
        $this->mid = $mid;
    }


    /**
     * @return int
     */
    public function getVisible(): int
    {
        return $this->visible;
    }


    /**
     * @param int $visible
     */
    public function setVisible(int $visible)
    {
        $this->visible = $visible;
    }


    /**
     * @return int
     */
    public function getSort(): int
    {
        return $this->sort;
    }


    /**
     * @param int $sort
     */
    public function setSort(int $sort)
    {
        $this->sort = $sort;
    }


    /**
     * @return int
     */
    public function getLpIsRequired(): int
    {
        return $this->lp_is_required;
    }


    /**
     * @param int $lp_is_required
     */
    public function setLpIsRequired(int $lp_is_required)
    {
        $this->lp_is_required = $lp_is_required;
    }


    /**
     * @return int
     */
    public function getLpReqPercentage(): int
    {
        return $this->lp_req_percentage;
    }


    /**
     * @param int $lp_req_percentage
     */
    public function setLpReqPercentage(int $lp_req_percentage)
    {
        $this->lp_req_percentage = $lp_req_percentage;
    }


}
