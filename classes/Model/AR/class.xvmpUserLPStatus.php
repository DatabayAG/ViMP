<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpUserLPStatus
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpUserLPStatus extends ActiveRecord
{
    public const TABLE_NAME = 'xvmp_lp_status';
    /**
     * @var int|null
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     * @db_is_primary   true
     * @db_sequence     true
     */
    protected ?int $id = 0;
    /**
     * @var int
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected int $obj_id = 0;
    /**
     * @var int
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected int $user_id = 0;
    /**
     * @var string
     * @db_has_field    true
     * @db_fieldtype    timestamp
     */
    protected string $created_at;
    /**
     * @var string
     * @db_has_field    true
     * @db_fieldtype    timestamp
     */
    protected string $updated_at;
    /**
     * @var int
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected int $status = ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;
    /**
     * @var bool
     */
    protected bool $status_changed = false;
    /**
     * @var int
     */
    protected int $old_status;

    /**
     * @return string
     */
    public static function returnDbTableName() : string
    {
        return self::TABLE_NAME;
    }

    /**
     * @param int $id
     * @param bool $is_ref_id
     * @param int $user_id
     */
    public static function updateLPStatuses(int $id = 0, bool $is_ref_id = true, int $user_id = 0) : void
    {
        if (!$id) {
            $id = (int) $_GET['ref_id'];
        }
        $users = ($user_id > 0) ? [$user_id] : xvmp::getCourseMembers($id, $is_ref_id);
        foreach ($users as $usr_id) {
            $user_status = self::getInstance($usr_id, $is_ref_id ? ilObject2::_lookupObjectId($id) : $id);
            $user_status->updateStatus();
            $user_status->store();
        }
    }

    /**
     * @param int $user_id
     * @param int $obj_id
     * @return ActiveRecord|xvmpUserLPStatus
     */
    public static function getInstance(int $user_id, int $obj_id) : xvmpUserLPStatus|ActiveRecord
    {
        $xvmpUserLPStatus = xvmpUserLPStatus::where(array('user_id' => $user_id, 'obj_id' => $obj_id))->first();
        if (!$xvmpUserLPStatus) {
            $xvmpUserLPStatus = new self();
            $xvmpUserLPStatus->setUserId($user_id);
            $xvmpUserLPStatus->setObjId($obj_id);
        }
        return $xvmpUserLPStatus;
    }

    /**
     *
     */
    public function updateStatus() : void
    {
        $progress = false;
        $complete = true;
        /** @var xvmpSelectedMedia $selected_medium */
        foreach (xvmpSelectedMedia::where(array('obj_id' => $this->getObjId(),
                                                'lp_is_required' => 1,
                                                'visible' => 1
        ))->get() as $selected_medium) {
            $reached_percentage = xvmpUserProgress::calcPercentage($this->getUserId(), $selected_medium->getMid());
            if ($reached_percentage > 0) {
                $progress = true;
            }
            if ($reached_percentage < $selected_medium->getLpReqPercentage()) {
                $complete = false;
            }
        }

        if ($complete && $progress) {   // the check for $progress catches the case if no media is "lp_required"
            $this->setStatus(ilLPStatus::LP_STATUS_COMPLETED_NUM);
        } elseif ($progress) {
            $this->setStatus(ilLPStatus::LP_STATUS_IN_PROGRESS_NUM);
        } else {
            $this->setStatus(ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM);
        }
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
     * @return string
     */
    public function getCreatedAt() : string
    {
        return $this->created_at;
    }

    /**
     * @param string $created_at
     */
    public function setCreatedAt(string $created_at) : void
    {
        $this->created_at = $created_at;
    }

    /**
     * @return string
     */
    public function getUpdatedAt() : string
    {
        return $this->updated_at;
    }

    /**
     * @param string $updated_at
     */
    public function setUpdatedAt(string $updated_at) : void
    {
        $this->updated_at = $updated_at;
    }

    /**
     * @return int
     */
    public function getStatus() : int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status) : void
    {
        if ($status != $this->status) {
            $this->old_status = $this->status;
            $this->status_changed = true;
        }
        $this->status = $status;
    }

    /**
     * @param bool $status_changed
     */
    public function setStatusChanged(bool $status_changed) : void
    {
        $this->status_changed = $status_changed;
    }

    /**
     * @return int
     */
    public function getOldStatus() : int
    {
        return $this->old_status;
    }

    /**
     * @param int $old_status
     */
    public function setOldStatus(int $old_status) : void
    {
        $this->old_status = $old_status;
    }

    /**
     *
     */
    public function create() : void
    {
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
        parent::create();
    }

    /**
     *
     */
    public function update() : void
    {
        $this->updated_at = date('Y-m-d H:i:s');
        parent::update();

        if ($this->hasStatusChanged()) {
            ilLPStatusWrapper::_updateStatus($this->getObjId(), $this->getUserId());
        }
    }

    /**
     * @return bool
     */
    public function hasStatusChanged() : bool
    {
        return $this->status_changed;
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
}
