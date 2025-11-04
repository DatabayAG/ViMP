<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpSettingsGUI
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @ilCtrl_isCalledBy xvmpSettingsGUI: ilObjViMPGUI
 */
class xvmpSettingsGUI extends xvmpGUI
{
    public const TAB_ACTIVE = ilObjViMPGUI::TAB_SETTINGS;

    public const CMD_UPDATE = 'update';

    /**
     *
     */
    protected function index() : void
    {
        $this->dic->ui()->mainTemplate()->addCss($this->pl->getAssetURL('default/xvmp_settings.css'));
        $xvmpSettingsFormGUI = new xvmpSettingsFormGUI($this);
        $this->dic->ui()->mainTemplate()->setContent($xvmpSettingsFormGUI->getHTML());
    }

    /**
     *
     */
    public function executeCommand() : void
    {
        if (!ilObjViMPAccess::hasWriteAccess()) {
            xvmpCurlLog::getInstance()->write('Access denied: User has no write access. (xvmpSettingsGUI)');
            $this->accessDenied();
        }
        parent::executeCommand();
    }

    /**
     *
     */
    public function update() : void
    {
        $xvmpSettingsFormGUI = new xvmpSettingsFormGUI($this);
        $xvmpSettingsFormGUI->setValuesByPost();
        if (!$xvmpSettingsFormGUI->saveForm()) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->pl->txt('msg_incomplete'), true);
            $this->dic->ui()->mainTemplate()->setContent($xvmpSettingsFormGUI->getHTML());
        }

        $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->pl->txt('form_saved'), true);
        $this->dic->ctrl()->redirect($this, self::CMD_STANDARD);
    }

}
