<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\ViMP\UIComponents\Player\VideoPlayer;

/**
 * Class xvmpLearningProgressGUI
 * @ilCtrl_isCalledBy xvmpLearningProgressGUI: ilObjViMPGUI
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpLearningProgressGUI extends xvmpGUI
{
    public const TAB_ACTIVE = ilObjViMPGUI::TAB_LEARNING_PROGRESS;

    public const CMD_SAVE = 'save';

    protected function index() : void
    {
        $this->dic->ui()->mainTemplate()->setOnScreenMessage('info', $this->pl->txt('hint_learning_progress_gui'));
        $xvmpLearningProgressTableGUI = new xvmpLearningProgressTableGUI($this, self::CMD_STANDARD);
        $this->dic->ui()->mainTemplate()->setContent($xvmpLearningProgressTableGUI->getHTML() . $this->getModalPlayer()->getHTML());
    }

    protected function save() : void
    {
        foreach (filter_input(INPUT_POST, 'lp_required_percentage', FILTER_DEFAULT,
            FILTER_REQUIRE_ARRAY) as $mid => $percentage) {
            /** @var xvmpSelectedMedia $selected_medium */
            $selected_medium = xvmpSelectedMedia::where(array('mid' => $mid, 'obj_id' => $this->getObjId()))->first();
            $selected_medium->setLpReqPercentage((int) $percentage);
            $selected_medium->setLpIsRequired((int) isset($_POST['lp_required'][$mid]));
            $selected_medium->update();
        }
        xvmpUserLPStatus::updateLPStatuses($this->getObjId(), false);
        ilLPStatusWrapper::_refreshStatus($this->getObjId());
        $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->pl->txt('form_saved'), true);
        $this->dic->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    public function executeCommand() : void
    {
        VideoPlayer::loadVideoJSAndCSS(false);
        if (!ilObjViMPAccess::hasWriteAccess()) {
            $this->accessDenied();
        }
        parent::executeCommand();
    }
}
