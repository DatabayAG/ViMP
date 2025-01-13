<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\ViMP\UIComponents\Player\VideoPlayer;

/**
 * Class xvmpContentGUI
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @ilCtrl_isCalledBy xvmpContentGUI: ilObjViMPGUI
 */
class xvmpContentGUI extends xvmpGUI
{
    public const TAB_ACTIVE = ilObjViMPGUI::TAB_CONTENT;

    public const CMD_RENDER_LIST_ITEM = 'renderListItem';
    public const CMD_RENDER_TILE = 'renderTile';
    public const CMD_RENDER_TILE_SMALL = 'renderTileSmall';
    public const CMD_DELIVER_VIDEO = 'deliverVideo';
    public const CMD_PLAY_VIDEO = 'playVideo';
    public const GET_TEMPLATE = 'tpl';

    protected function performCommand($cmd) : void
    {
        switch ($cmd) {
            case self::CMD_RENDER_LIST_ITEM:
            case self::CMD_RENDER_TILE:
            case self::CMD_RENDER_TILE_SMALL:
                $mid = $_GET['mid'];
                if (!$mid || !xvmpSelectedMedia::isSelected($mid, $this->getObjId())) {
                    $this->accessDenied();
                }
                break;
            case self::CMD_DELIVER_VIDEO:
                $this->accessDenied();
                break;
        }
        parent::performCommand($cmd);
    }

    /**
     * used for goto link
     */
    public function playVideo() : void
    {
        $mid = filter_input(INPUT_GET, ilObjViMPGUI::GET_VIDEO_ID, FILTER_SANITIZE_NUMBER_INT);
        $play_video_id = xvmpMedium::find($mid)->isTranscoded() ? $mid : null;
        if ($play_video_id) {
            $this->dic->ui()->mainTemplate()->addOnLoadCode('$(\'#xvmp_modal_player\').modal(\'show\');');
        }
        $this->index($play_video_id);
    }

    protected function index($play_video_id = null) : void
    {
        /** @var xvmpSettings $settings */
        $settings = xvmpSettings::find($this->getObjId());
        VideoPlayer::loadVideoJSAndCSS($settings->getLpActive() && !xvmpConf::getConfig(xvmpConf::F_EMBED_PLAYER));
        $this->dic->ui()->mainTemplate()->addCss($this->pl->getAssetURL('default/content.css'));

        if (!$this->dic->ctrl()->isAsynch() && ilObjViMPAccess::hasWriteAccess()) {
            $this->addFlushCacheButton();
        }

        $layout_type = xvmpSettings::find($this->getObjId())->getLayoutType();

        switch ($layout_type) {
            case xvmpSettings::LAYOUT_TYPE_LIST:
                $xvmpContentListGUI = new xvmpContentListGUI($this);
                if (!is_null($play_video_id)) {
                    $this->dic->ui()->mainTemplate()->setContent($xvmpContentListGUI->getHTML() . $this->getFilledModalPlayer($play_video_id)->getHTML());
                } else {
                    $this->dic->ui()->mainTemplate()->setContent($xvmpContentListGUI->getHTML() . self::getModalPlayer()->getHTML());
                }
                break;
            case xvmpSettings::LAYOUT_TYPE_TILES:
                $xvmpContentTilesGUI = new xvmpContentTilesGUI($this);
                if (!is_null($play_video_id)) {
                    $this->dic->ui()->mainTemplate()->setContent($xvmpContentTilesGUI->getHTML() . $this->getFilledModalPlayer($play_video_id)->getHTML());
                } else {
                    $this->dic->ui()->mainTemplate()->setContent($xvmpContentTilesGUI->getHTML() . self::getModalPlayer()->getHTML());
                }
                break;
            case xvmpSettings::LAYOUT_TYPE_PLAYER:
                $xvmpContentPlayerGUI = new xvmpContentPlayerGUI($this);
                $this->dic->ui()->mainTemplate()->setContent((string) $xvmpContentPlayerGUI->getHTML());
                break;
        }
    }

    /**
     * @return void
     * @throws ilTemplateException
     */
    public function renderListItem()
    {
        $mid = filter_input(INPUT_GET, ilObjViMPGUI::GET_VIDEO_ID, FILTER_SANITIZE_NUMBER_INT);
        $medium = xvmpMedium::find($mid);
        if ($medium instanceof xvmpDeletedMedium) {
            echo 'deleted';
            exit;
        }
        echo $this->renderer_factory->listElement()->render(
            $this->metadata_builder->buildFromVimpMedium($medium, true, true)
        );
        exit;
    }

    /**
     * @return void
     * @throws ilTemplateException
     */
    public function renderTile()
    {
        $mid = filter_input(INPUT_GET, ilObjViMPGUI::GET_VIDEO_ID, FILTER_SANITIZE_NUMBER_INT);
        $medium = xvmpMedium::find($mid);
        if ($medium instanceof xvmpDeletedMedium) {
            echo 'deleted';
            exit;
        }
        echo $this->renderer_factory->tile()->render(
            $this->metadata_builder->buildFromVimpMedium($medium, true, true)
        );
        exit;
    }

    /**
     * @return void
     * @throws ilTemplateException
     */
    public function renderTileSmall()
    {
        $mid = filter_input(INPUT_GET, ilObjViMPGUI::GET_VIDEO_ID, FILTER_SANITIZE_NUMBER_INT);
        $medium = xvmpMedium::find($mid);
        if ($medium instanceof xvmpDeletedMedium) {
            echo 'deleted';
            exit;
        }
        echo $this->renderer_factory->tileSmall()->render(
            $this->metadata_builder->buildFromVimpMedium($medium, true, true)
        );
        exit;
    }
}
