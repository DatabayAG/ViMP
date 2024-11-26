<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpDeletedMedium
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpDeletedMedium extends xvmpMedium
{
    public function __construct()
    {
        $this->username = '';
        $this->status = 'deleted';
        $this->title = $this->getTitle();
        $this->description = $this->getDescription();
        $this->duration = 0;
        $this->duration_formatted = '';
        $this->thumbnail = $this->getThumbnail();
        $this->medium = $this->getMedium();
        $this->startdate = null;
        $this->enddate = null;
        $this->created_at = $this->getCreatedAt();
    }


    /**
     * @return String
     */
    public function getTitle(): string
    {
        return ilViMPPlugin::getInstance()->txt('not_available');
    }


    /**
     * @param int $max_length
     * @return String
     */
    public function getDescription(int $max_length = 0): string
    {
        return ilViMPPlugin::getInstance()->txt('not_available_description');
    }


    /**
     * @return int
     */
    public function getDuration(): int
    {
        return 0;
    }


    /**
     * @return String
     */
    public function getDurationFormatted(): string
    {
        return '';
    }


    /**
     * @param int $width
     * @param int $height
     * @return String
     */
    public function getThumbnail(int $width = 0, int $height = 0): string
    {
        return ilViMPPlugin::getInstance()->getDirectory() . '/templates/images/not_available.png';
    }


    /**
     * @return string
     */
    public function getMedium(): string
    {
        return '';
    }


    /**
     * @param string $format
     * @return String
     */
    public function getCreatedAt(string $format = ''): string
    {
        return '';
    }

    public function __toArray(): array
    {
        return [
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'mid' => $this->getMid(),
            'thumbnail' => $this->getThumbnail(),
            'status' => $this->getStatus(),
            'username' => '',
            'created_at' => $this->getCreatedAt()
        ];
    }
}
