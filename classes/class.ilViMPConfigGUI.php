<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class ilViMPConfigGUI
 * @ilCtrl_IsCalledBy ilViMPConfigGUI: ilObjComponentSettingsGUI
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ilViMPConfigGUI extends ilPluginConfigGUI
{
    public const CMD_STANDARD = 'configure';
    public const CMD_UPDATE = 'update';
    public const CMD_FLUSH_CACHE = 'flushCache';
    public const CMD_SHOW_LOG = 'showLog';

    public const SUBTAB_SETTINGS = 'settings';
    public const SUBTAB_LOG = 'log';

    /**
     * @var ilTemplate
     */
    protected $tpl;
    /**
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var ilViMPPlugin
     */
    protected ilViMPPlugin $pl;
    /**
     * @var ilToolbarGUI
     */
    protected $toolbar;
    /**
     * @var ilTabsGUI
     */
    protected $tabs;

    /* @var Container
     */
    protected $dic;
    /**
     * ilViMPConfigGUI constructor.
     */
    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $tpl = $DIC['tpl'];
        $ilCtrl = $DIC['ilCtrl'];
        $ilToolbar = $DIC['ilToolbar'];
        $ilTabs = $DIC['ilTabs'];
        $this->toolbar = $ilToolbar;
        $this->tpl = $tpl;
        $this->ctrl = $ilCtrl;
        $this->pl = ilViMPPlugin::getInstance();
        $this->tabs = $ilTabs;
    }


    /**
     * @param string $cmd
     * @throws ilCtrlException
     */
    public function performCommand(string $cmd): void
    {
        $this->addSubTabs();
        switch ($cmd) {
            default:
                $this->{$cmd}();
                break;
        }
    }

    /**
     * @throws ilCtrlException
     */
    protected function addSubTabs()
    {
        $this->tabs->addSubTab(self::SUBTAB_SETTINGS, $this->pl->txt(self::SUBTAB_SETTINGS), $this->ctrl->getLinkTarget($this, self::CMD_STANDARD));
        $this->tabs->addSubTab(self::SUBTAB_LOG, $this->pl->txt(self::SUBTAB_LOG), $this->ctrl->getLinkTarget($this, self::CMD_SHOW_LOG));
    }

    /**
     * @throws arException
     * @throws Exception
     */
    protected function showLog()
    {
        $this->tabs->activateSubTab(self::SUBTAB_LOG);
        $xvmpEventLogTableGUI = new xvmpEventLogTableGUI($this, self::CMD_SHOW_LOG);
        $xvmpEventLogTableGUI->parseData();
        $this->tpl->setContent($xvmpEventLogTableGUI->getHTML());
    }

    /**
     * @throws ilCtrlException
     * @throws JsonException
     * @throws Exception
     */
    protected function applyFilter()
    {
        $xvmpEventLogTableGUI = new xvmpEventLogTableGUI($this, self::CMD_SHOW_LOG);
        $xvmpEventLogTableGUI->writeFilterToSession();
        $xvmpEventLogTableGUI->resetOffset();
        $this->ctrl->redirect($this, self::CMD_SHOW_LOG);
    }

    /**
     * @throws ilCtrlException
     * @throws JsonException
     * @throws Exception
     */
    protected function resetFilter()
    {
        $xvmpEventLogTableGUI = new xvmpEventLogTableGUI($this, self::CMD_SHOW_LOG);
        $xvmpEventLogTableGUI->resetFilter();
        $xvmpEventLogTableGUI->resetOffset();
        $this->ctrl->redirect($this, self::CMD_SHOW_LOG);
    }

    /**
     * @throws ilCtrlException
     */
    public function addFlushCacheButton()
    {
        $button = ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, self::CMD_FLUSH_CACHE));
        $button->setCaption($this->pl->txt('flush_cache'), false);
        $this->toolbar->addButtonInstance($button);
    }

    /**
     *
     */
    public function flushCache()
    {
        xvmpCacheFactory::getInstance()->flush();

        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     * @throws ilCtrlException
     */
    protected function configure()
    {
        $this->tabs->activateSubTab(self::SUBTAB_SETTINGS);
        $this->addFlushCacheButton();
        $xvmpConfFormGUI = new xvmpConfFormGUI($this);
        $xvmpConfFormGUI->fillForm();
        $this->tpl->setContent($xvmpConfFormGUI->getHTML());
    }


    /**
     *
     * @throws ilCtrlException|JsonException
     */
    protected function update()
    {
        $xvmpConfFormGUI = new xvmpConfFormGUI($this);
        $xvmpConfFormGUI->setValuesByPost();
        if ($xvmpConfFormGUI->saveObject()) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->pl->txt('msg_success'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
        $this->tpl->setContent($xvmpConfFormGUI->getHTML());
    }
}
