<?php

declare(strict_types=1);

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

    protected ilGlobalTemplateInterface $tpl;
    protected ilCtrlInterface $ctrl;
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
        $this->toolbar = $DIC['ilToolbar'];
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl =  $DIC->ctrl();
        $this->pl = ilViMPPlugin::getInstance();
        $this->tabs = $DIC['ilTabs'];
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
    protected function addSubTabs() : void
    {
        $this->tabs->addSubTab(self::SUBTAB_SETTINGS, $this->pl->txt(self::SUBTAB_SETTINGS), $this->ctrl->getLinkTarget($this, self::CMD_STANDARD));
        $this->tabs->addSubTab(self::SUBTAB_LOG, $this->pl->txt(self::SUBTAB_LOG), $this->ctrl->getLinkTarget($this, self::CMD_SHOW_LOG));
    }

    /**
     * @throws arException
     * @throws Exception
     */
    protected function showLog() : void
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
    protected function applyFilter() : void
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
    protected function resetFilter() : void
    {
        $xvmpEventLogTableGUI = new xvmpEventLogTableGUI($this, self::CMD_SHOW_LOG);
        $xvmpEventLogTableGUI->resetFilter();
        $xvmpEventLogTableGUI->resetOffset();
        $this->ctrl->redirect($this, self::CMD_SHOW_LOG);
    }

    /**
     * @throws ilCtrlException
     */
    public function addFlushCacheButton() : void
    {
        $button = ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, self::CMD_FLUSH_CACHE));
        $button->setCaption($this->pl->txt('flush_cache'), false);
        $this->toolbar->addButtonInstance($button);
    }

    /**
     *
     */
    public function flushCache() : void
    {
        xvmpCacheFactory::getInstance()->flush();

        $this->ctrl->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     * @throws ilCtrlException
     */
    protected function configure() : void
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
    protected function update() : void
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
