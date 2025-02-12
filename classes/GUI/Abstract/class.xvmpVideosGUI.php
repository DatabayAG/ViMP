<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\ViMP\UIComponents\Player\VideoPlayer;

/**
 * Class xvmpVideosGUI
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
abstract class xvmpVideosGUI extends xvmpGUI
{
    public const TAB_ACTIVE = ilObjViMPGUI::TAB_VIDEOS;
    public const SUBTAB_ACTIVE = ''; // overwrite in subclass

    public const SUBTAB_SEARCH = 'search_videos';
    public const SUBTAB_SELECTED = 'selected_videos';
    public const SUBTAB_OWN = 'own_videos';

    public const CMD_SHOW = 'show';
    public const CMD_SHOW_FILTERED = 'showFiltered';
    public const CMD_APPLY_FILTER = 'applyFilter';
    public const CMD_RESET_FILTER = 'resetFilter';
    public const CMD_ADD_VIDEO = 'addVideo';
    public const CMD_TOGGLE_VIDEO = 'toggleVideo';
    public const CMD_REMOVE_VIDEO = 'removeVideo';

    public const TABLE_CLASS = '';

    /**
     * @param $cmd
     * @return void
     * @throws ilCtrlException
     */
    protected function performCommand($cmd) : void
    {
        VideoPlayer::loadVideoJSAndCSS(false);

        switch ($cmd) {
            case self::CMD_STANDARD:
            case self::CMD_SHOW:
            case self::CMD_SHOW_FILTERED:
                if (!$this->dic->ctrl()->isAsynch()) {
                    $this->setSubTabs();
                    $this->dic->tabs()->activateSubTab(static::SUBTAB_ACTIVE);
                    $this->initUploadButton();
                }
                break;
            case self::CMD_TOGGLE_VIDEO:
                $mid = $_GET[xvmpMedium::F_MID];
                $medium = xvmpMedium::find($mid);
                $checked = $_GET['checked'];
                if ($checked) {
                    ilObjViMPAccess::checkAction(ilObjViMPAccess::ACTION_ADD_VIDEO, $this, $medium);
                } else {
                    ilObjViMPAccess::checkAction(ilObjViMPAccess::ACTION_REMOVE_VIDEO, $this, $medium);
                }
                break;
        }
        parent::performCommand($cmd);
    }

    /**
     * @throws ilCtrlException
     */
    protected function setSubTabs() : void
    {
        if (ilObjViMPAccess::hasWriteAccess()) {
            $this->dic->tabs()->addSubTab(self::SUBTAB_SEARCH, $this->pl->txt(self::SUBTAB_SEARCH),
                $this->dic->ctrl()->getLinkTargetByClass(xvmpSearchVideosGUI::class, xvmpGUI::CMD_STANDARD));
            $this->dic->tabs()->addSubTab(self::SUBTAB_SELECTED, $this->pl->txt(self::SUBTAB_SELECTED),
                $this->dic->ctrl()->getLinkTargetByClass(xvmpSelectedVideosGUI::class, xvmpGUI::CMD_STANDARD));
            $this->dic->tabs()->addSubTab(self::SUBTAB_OWN, $this->pl->txt(self::SUBTAB_OWN),
                $this->dic->ctrl()->getLinkTargetByClass(xvmpOwnVideosGUI::class, xvmpGUI::CMD_STANDARD));
        }
    }

    /**
     * @throws ilCtrlException
     */
    protected function initUploadButton() : void
    {
        $upload_button = ilLinkButton::getInstance();
        $upload_button->setCaption($this->pl->txt('upload_video'), false);
        $upload_button->setUrl($this->dic->ctrl()->getLinkTargetByClass(xvmpOwnVideosGUI::class,
            xvmpOwnVideosGUI::CMD_UPLOAD_VIDEO_FORM));
        $this->dic->toolbar()->addButtonInstance($upload_button);
    }

    /**
     *
     */
    protected function index() : void
    {
        $class_name = static::TABLE_CLASS;
        /** @var xvmpTableGUI $table_gui */
        $table_gui = new $class_name($this, self::CMD_SHOW);
        $this->dic->ui()->mainTemplate()->setContent($table_gui->getHTML() . $this->getModalPlayer()->getHTML());
    }

    /**
     *
     */
    protected function show() : void
    {
        $class_name = static::TABLE_CLASS;
        /** @var xvmpTableGUI $table_gui */
        $table_gui = new $class_name($this, self::CMD_SHOW);
        $table_gui->parseData();
        $table_gui->determineOffsetAndOrder();
        $this->dic->ui()->mainTemplate()->setContent($table_gui->getHTML() . $this->getModalPlayer()->getHTML());
    }

    /**
     *
     */
    protected function showFiltered() : void
    {
        $class_name = static::TABLE_CLASS;
        /** @var xvmpTableGUI $table_gui */
        $table_gui = new $class_name($this, self::CMD_SHOW_FILTERED);
        $table_gui->parseData();
        $table_gui->setExternalSorting(true);
        $table_gui->determineOffsetAndOrder();
        $this->dic->ui()->mainTemplate()->setContent($table_gui->getHTML() . $this->getModalPlayer()->getHTML());
    }

    protected function getVideoPlayer($video, int $obj_id) : VideoPlayer
    {
        return (new VideoPlayer($video, xvmp::isUseEmbeddedPlayer($obj_id, $video), false));
    }

    /**
     * @throws ilCtrlException
     */
    public function applyFilter() : void
    {
        $class_name = static::TABLE_CLASS;
        /** @var xvmpTableGUI $table_gui */
        $table_gui = new $class_name($this, self::CMD_STANDARD);
        $table_gui->resetOffset();
        $table_gui->writeFilterToSession();
        $this->dic->ctrl()->redirect($this, self::CMD_SHOW_FILTERED);
    }

    /**
     * @throws ilCtrlException
     */
    public function resetFilter() : void
    {
        $class_name = static::TABLE_CLASS;
        /** @var xvmpTableGUI $table_gui */
        $table_gui = new $class_name($this, self::CMD_STANDARD);
        $table_gui->resetOffset();
        $table_gui->resetFilter();
        $this->dic->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    public function toggleVideo()
    {
        $mid = (int) $_GET[xvmpMedium::F_MID];
        $checked = (bool) $_GET['checked'];
        $visible = (bool) $_GET[xvmpSelectedMedia::F_VISIBLE];
        if ($checked) {
            xvmpSelectedMedia::addVideo($mid, $this->getObjId(), $visible);
        } else {
            xvmpSelectedMedia::removeVideo($mid, $this->getObjId());
        }
        echo "{\"success\": true}";
        exit;
    }

    /**
     * ajax
     */
    public function addVideo()
    {
        $mid = (int) $_GET[xvmpMedium::F_MID];
        $visible = (bool) $_GET[xvmpSelectedMedia::F_VISIBLE];
        xvmpSelectedMedia::addVideo($mid, $this->getObjId(), $visible);
        echo "{\"success\": true}";
        exit;
    }

    /**
     * ajax
     */
    public function removeVideo()
    {
        $mid = (int) $_GET[xvmpMedium::F_MID];
        xvmpSelectedMedia::removeVideo($mid, $this->getObjId());
        echo "{\"success\": true}";
        exit;
    }

}
