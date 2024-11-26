<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;

/**
 * Class xvmpFormGUI
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
abstract class xvmpFormGUI extends ilPropertyFormGUI
{
    protected ilViMPConfigGUI $parent_gui;
    protected ilViMPPlugin $pl;
    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected Container $dic;

    /**
     * xvmpFormGUI constructor.
     * @throws ilCtrlException
     */
    public function __construct($parent_gui)
    {
        global $DIC;
        $ilCtrl = $DIC['ilCtrl'];
        $lng = $DIC['lng'];
        $this->parent_gui = $parent_gui;
        $this->pl = ilViMPPlugin::getInstance();
        $this->ctrl = $ilCtrl;
        $this->lng = $lng;
        $this->dic = $DIC;

        parent::__construct();
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));

        $this->initForm();
    }

    abstract protected function initForm();

}
