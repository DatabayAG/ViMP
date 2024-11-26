<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpUserProgress
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpUserProgress extends ActiveRecord
{
    public const DB_TABLE_NAME = 'xvmp_user_progress';

    public const F_USR_ID = 'usr_id';
    public const F_MID = xvmpMedium::F_MID;
    public const F_RANGES = 'ranges';
    public const F_TOTAL_WATCHED = 'total_watched';
    public const F_VIDEO_DURATION = 'video_duration';


    /**
     * @return string
     */
    public static function returnDbTableName(): string
    {
        return self::DB_TABLE_NAME;
    }


    /**
     * @var ?int
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
     * @var ?int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected ?int $usr_id;
    /**
     * @var ?int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected ?int $mid;
    /**
     * @var String
     *
     * @db_has_field        true
     * @db_fieldtype        clob
     */
    protected string $ranges = '[]';
    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected int $total_watched;
    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     */
    protected int $video_duration;


    /**
     * @return int
     */
    public function getId(): ?int
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
    public function getUsrId(): int
    {
        return $this->usr_id;
    }


    /**
     * @param int $usr_id
     */
    public function setUsrId(int $usr_id) : void
    {
        $this->usr_id = $usr_id;
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
    public function setMid(int $mid) : void
    {
        $this->mid = $mid;
    }


    /**
     * @return String
     */
    public function getRanges(): string
    {
        return $this->ranges;
    }


    /**
     * @param String $ranges
     */
    public function setRanges(string $ranges) : void
    {
        $this->ranges = $ranges;
    }


    /**
     * @return int
     */
    public function getVideoDuration(): int
    {
        return $this->video_duration;
    }


    /**
     * @param int $video_duration
     */
    public function setVideoDuration(int $video_duration) : void
    {
        $this->video_duration = $video_duration;
    }


    /**
     * @return int
     */
    public function getTotalWatched(): int
    {
        return $this->total_watched;
    }


    /**
     * @param int $total_watched
     */
    public function setTotalWatched(int $total_watched) : void
    {
        $this->total_watched = $total_watched;
    }


    /**
     * @param int $usr_id
     * @param int $mid
     * @param string $ranges
     * @throws xvmpException
     */
    public static function storeProgress(int $usr_id, int $mid, string $ranges) : void
    {
        $progress = self::where(array('usr_id' => $usr_id, 'mid' => $mid))->first();
        if (!$progress) {
            $progress = new self();
            $progress->setUsrId($usr_id);
            $progress->setMid($mid);
            $progress->setVideoDuration(xvmpMedium::getObjectAsArray($mid)['duration']);
        }
        $progress->setRanges($ranges);
        $progress->store();
    }


    /**
     *
     */
    public function store(): void
    {
        $this->calcTotalWatched();
        parent::store();

        // learning progress
        xvmpUserLPStatus::updateLPStatuses(0, true, $this->getUsrId());
    }

    /**
     *
     */
    protected function calcTotalWatched() : void
    {
        $ranges = json_decode($this->getRanges());
        $watched_seconds = 0;
        foreach ($ranges as $range) {
            $watched_seconds += ceil($range->e - $range->s);
        }
        $this->total_watched = (int) min($watched_seconds, $this->getVideoDuration());
    }

    /**
     * @param $usr_id
     * @param $mid
     *
     * @return float|int
     */
    public static function calcPercentage($usr_id, $mid) : float|int
    {
        /** @var self $progress */
        $progress = self::where(array('usr_id' => $usr_id, 'mid' => $mid))->first();
        if (!$progress || ($progress->getTotalWatched() == 0)) {
            return 0;
        }
        return ceil(($progress->getTotalWatched() / $progress->getVideoDuration()) * 100);
    }


}
