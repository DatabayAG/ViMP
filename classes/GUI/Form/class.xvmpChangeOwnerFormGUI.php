<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpChangeOwnerFormGUI
 * @ilCtrl_isCalledBy xvmpChangeOwnerFormGUI: ilObjViMPGUI
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpChangeOwnerFormGUI extends xvmpFormGUI
{
    /**
     * @var int
     */
    protected int $mid;
    /**
     * @var mixed
     */
    protected ?ilGlobalTemplateInterface $global_tpl;

    protected $parent_gui;

    /**
     * xvmpChangeOwnerFormGUI constructor.
     * @param $parent_gui xvmpOwnVideosGUI
     * @param $mid
     * @throws ilCtrlException
     */
    public function __construct($parent_gui, $mid)
    {
        global $DIC;
        $this->global_tpl = $DIC['tpl'];
        parent::__construct($parent_gui);
        $this->mid = (int) $mid;

        $this->setTitle($this->pl->txt('form_title_change_owner'));

        $this->ctrl->setParameterByClass(xvmpOwnVideosGUI::class, 'mid', $this->mid);
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $this->addCommandButton(xvmpOwnVideosGUI::CMD_CHANGE_OWNER, $this->lng->txt('save'));
        $this->addCommandButton(xvmpGUI::CMD_STANDARD, $this->lng->txt('cancel'));
    }

    /**
     * @throws ilCtrlException
     */
    protected function initForm() : void
    {
        $input = new ilTextInputGUI($this->pl->txt('username'), 'login');
        $input->setRequired(true);
        #$input->setInfo($this->pl->txt('info_autocomplete'));
        $input->setDataSource($this->dic->ctrl()->getLinkTarget($this, 'addUserAutoComplete', "", true));
        $this->addItem($input);
    }
}
