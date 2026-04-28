<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpSelectedVideosGUI
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @ilCtrl_isCalledBy xvmpSelectedVideosGUI: ilObjViMPGUI
 */
class xvmpSelectedVideosGUI extends xvmpVideosGUI
{
    public const SUBTAB_ACTIVE = xvmpVideosGUI::SUBTAB_SELECTED;

    public const TABLE_CLASS = 'xvmpSelectedVideosTableGUI';

    public const CMD_MOVE_UP = 'moveUp';
    public const CMD_MOVE_DOWN = 'moveDown';
    public const CMD_SET_VISIBILITY = 'setVisibility';

    public function executeCommand() : void
    {
        if (!ilObjViMPAccess::hasWriteAccess()) {
            xvmpCurlLog::getInstance()->write('Access denied: User has no write access. (xvmpSelectedVideoGUI)');
            $this->accessDenied();
        }

        if (!$this->dic->ctrl()->isAsynch()) {
            $this->addFlushCacheButton();
        }

        parent::executeCommand();
    }

    /**
     * ajax
     */
    public function reorder()
    {
        $ids = $_POST['ids'] ?? [];
        $media = xvmpSelectedMedia::where(['mid' => $ids, 'obj_id' => $this->getObjId()])->get();

        if (empty($media)) {
            echo json_encode(['success' => true]);
            exit;
        }

        $mediaByMid = [];
        foreach ($media as $obj) {
            $mediaByMid[$obj->getMid()] = $obj;
        }

        $orderedMedia = [];
        foreach ($ids as $id) {
            if (isset($mediaByMid[$id])) {
                $orderedMedia[] = $mediaByMid[$id];
            }
        }

        $sort = min(array_map(fn($o) => $o->getSort(), $orderedMedia));
        foreach ($orderedMedia as $obj) {
            $obj->setSort($sort);
            $obj->update();
            $sort += 10;
        }

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * ajax
     */
    public function moveUp()
    {
        $mid = $_GET['mid'];
        xvmpSelectedMedia::moveUp($mid, $this->getObjId());
        exit;
    }

    /**
     * ajax
     */
    public function moveDown()
    {
        $mid = $_GET['mid'];
        xvmpSelectedMedia::moveDown($mid, $this->getObjId());
        exit;
    }

    /**
     * ajax
     */
    public function setVisibility()
    {
        $mid = $_GET['mid'];
        $visible = $_GET['visible'];
        /** @var xvmpSelectedMedia $video */
        $video = xvmpSelectedMedia::where(array('mid' => $mid, 'obj_id' => $this->getObjId()))->first();
        $video->setVisible((int) $visible);
        $video->update();
        exit;
    }
}
