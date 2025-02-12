<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;

/**
 * Class ilObjViMPGUI
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @ilCtrl_isCalledBy ilObjViMPGUI: ilRepositoryGUI, ilObjPluginDispatchGUI, ilAdministrationGUI, illmeditorgui
 * @ilCtrl_Calls      ilObjViMPGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilLearningProgressGUI
 */
class ilObjViMPGUI extends ilObjectPluginGUI
{
    public const CMD_SHOW_CONTENT = 'showContent';
    public const CMD_PLAY_VIDEO = 'playVideo';
    public const CMD_SEARCH_VIDEOS = 'searchVideos';
    public const CMD_SEARCH_USER_AJAX = 'searchUserAjax';

    public const TAB_CONTENT = 'content';
    public const TAB_INFO = 'info_short';
    public const TAB_VIDEOS = 'videos';
    public const TAB_LEARNING_PROGRESS = 'learning_progress';
    public const TAB_LOG = 'log';
    public const TAB_SETTINGS = 'settings';
    public const TAB_PERMISSION = 'permissions';
    public const GET_REF_ID = 'ref_id';
    public const GET_VIDEO_ID = 'mid';
    public const GET_TIME = 't';
    public const CMD_TRANSCODING_PROGRESS = 'getTranscodingProgress';
    /**
     * @var ilViMPPlugin
     */
    protected ilViMPPlugin $pl;
    /**
     * @var ilObjViMP
     */
    protected ilObjViMP $obj;
    /**
     * @var Container
     */
    protected Container $dic;

    /**
     * ilObjViMPGUI constructor.
     * @param int $a_ref_id
     * @param int $a_id_type
     * @param int $a_parent_node_id
     * @throws ilCtrlException
     */
    public function __construct($a_ref_id = 0, int $a_id_type = self::REPOSITORY_NODE_ID, $a_parent_node_id = 0)
    {
        global $DIC;
        $this->dic = $DIC;
        parent::__construct($a_ref_id, $a_id_type, $a_parent_node_id);
        $this->pl = ilViMPPlugin::getInstance();
    }

    /**
     * @param $a_target
     */
    public static function _goto($a_target) : void
    {
        global $DIC;
        $DIC->ctrl()->setTargetScript('ilias.php');
        $id = explode("_", $a_target[0]);

        $DIC->ctrl()->setParameterByClass(xvmpContentGUI::class, self::GET_REF_ID, $id[0]);

        if (isset($id[1])) {
            if (isset($id[3])) {
                // time
                $DIC->ctrl()->setParameterByClass(xvmpContentGUI::class, self::GET_TIME, (int) $id[3]);
            }
            $DIC->ctrl()->setParameterByClass(xvmpContentGUI::class, self::GET_VIDEO_ID, (int) $id[2]);
            $DIC->ctrl()->redirectByClass(
                [ilObjPluginDispatchGUI::class, self::class, xvmpContentGUI::class],
                xvmpContentGUI::CMD_PLAY_VIDEO
            );
        }
        parent::_goto($a_target);
    }

    protected function supportsCloning() : bool
    {
        return false;
    }

    protected function getTranscodingProgress() : void
    {
        try {
            $transcodingProgress = xvmpRequest::getTranscodingProgress(filter_input(
                INPUT_GET,
                'mid',
                FILTER_VALIDATE_INT
            ), 2);
            echo $transcodingProgress;
        } catch (xvmpException $e) {
            xvmpCurlLog::getInstance()->write($e->getMessage());
        }
        exit;
    }

    /**
     * @throws ilCtrlException
     */
    public function executeCommand() : void
    {
        $next_class = $this->ctrl->getNextClass();
        $cmd = $this->ctrl->getCmd();
        if (!ilObjViMPAccess::hasReadAccess() && $next_class != "ilinfoscreengui" && $cmd != "infoScreen" && $cmd != xvmpGUI::CMD_FILL_MODAL) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->pl->txt('access_denied'), true);
            parent::viewObject();
            return;
        }

        $this->tpl->loadStandardTemplate();

        try {
            switch ($next_class) {
                case 'xvmpcontentgui':
                    if (!$this->ctrl->isAsynch()) {
                        $this->initHeader();
                        $this->setTabs();
                    }
                    $xvmpGUI = new xvmpContentGUI($this);
                    $this->ctrl->forwardCommand($xvmpGUI);
                    $this->tpl->printToStdout();
                    break;
                case 'xvmpsearchvideosgui':
                    if (!$this->ctrl->isAsynch()) {
                        $this->initHeader();
                        $this->setTabs();
                    }
                    $xvmpGUI = new xvmpSearchVideosGUI($this);
                    $this->ctrl->forwardCommand($xvmpGUI);
                    $this->tpl->printToStdout();
                    break;
                case 'xvmpeventloggui':
                    if (!$this->ctrl->isAsynch()) {
                        $this->initHeader();
                        $this->setTabs();
                    }
                    $xvmpGUI = new xvmpEventLogGUI($this);
                    $this->ctrl->forwardCommand($xvmpGUI);
                    $this->tpl->printToStdout();
                    break;
                case 'xvmpsettingsgui':
                    if (!$this->ctrl->isAsynch()) {
                        $this->initHeader();
                        $this->setTabs();
                    }
                    $xvmpGUI = new xvmpSettingsGUI($this);
                    $this->ctrl->forwardCommand($xvmpGUI);
                    $this->tpl->printToStdout();
                    break;
                case 'xvmpselectedvideosgui':
                    if (!$this->ctrl->isAsynch()) {
                        $this->initHeader();
                        $this->setTabs();
                    }
                    $xvmpGUI = new xvmpSelectedVideosGUI($this);
                    $this->ctrl->forwardCommand($xvmpGUI);
                    $this->tpl->printToStdout();
                    break;
                case 'xvmpownvideosgui':
                    if (!$this->ctrl->isAsynch()) {
                        $this->initHeader();
                        $this->setTabs();
                    }
                    $xvmpGUI = new xvmpOwnVideosGUI($this);
                    $this->ctrl->forwardCommand($xvmpGUI);
                    $this->tpl->printToStdout();
                    break;
                case 'xvmplearningprogressgui':
                    if (!$this->ctrl->isAsynch()) {
                        $this->initHeader();
                        $this->setTabs();
                    }
                    $xvmpGUI = new xvmpLearningProgressGUI($this);
                    $this->ctrl->forwardCommand($xvmpGUI);
                    $this->tpl->printToStdout();
                    break;
                case "ilinfoscreengui":
                    if (!$this->ctrl->isAsynch()) {
                        $this->initHeader();
                        $this->setTabs();
                    }
                    $this->checkPermission("visible");
                    $this->infoScreen();    // forwards command
                    $this->tpl->printToStdout();
                    break;
                case 'ilpermissiongui':
                    $this->initHeader(false);
                    parent::executeCommand();
                    break;
                default:
                    // workaround for object deletion; 'parent::executeCommand()' shows the template and leads to "Headers already sent" error
                    if ($next_class == "" && $cmd == 'deleteObject') {
                        $this->deleteObject();
                        break;
                    }
                    parent::executeCommand();
                    break;
            }
        } catch (Exception $e) {
            $this->dic->logger()->root()->logStack(ilLogLevel::ERROR, $e->getMessage());
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $e->getMessage());
            $this->tpl->printToStdout();
        }

    }

    protected function initHeader(bool $render_locator = true) : void
    {
        if ($render_locator) {
            $this->setLocator();
        }

        $this->tpl->setTitleIcon(ilObjViMP::_getIcon($this->object_id));
        $this->tpl->setTitle($this->object->getTitle());
        $this->tpl->setDescription($this->object->getDescription());

        if (!xvmpSettings::find($this->obj_id)->getIsOnline()) {
            /**
             * @var $list_gui ilObjViMPListGUI
             */
            $list_gui = ilObjectListGUIFactory::_getListGUIByType('xvmp');
            $this->tpl->setAlertProperties($list_gui->getAlertProperties());
        }

        //		$this->tpl->setTitleIcon(ilObjViMP::_getIcon($this->object_id));
        $this->tpl->setPermanentLink('xvmp', (int) $_GET['ref_id']);
    }

    protected function setTabs() : void
    {
        $this->tabs_gui->addTab(
            self::TAB_CONTENT,
            $this->pl->txt(self::TAB_CONTENT),
            $this->ctrl->getLinkTargetByClass(xvmpContentGUI::class, xvmpGUI::CMD_STANDARD)
        );
        $this->tabs_gui->addTab(
            self::TAB_INFO,
            $this->pl->txt(self::TAB_INFO),
            $this->ctrl->getLinkTargetByClass(ilInfoScreenGUI::class)
        );

        if (ilObjViMPAccess::hasWriteAccess()) {
            $this->tabs_gui->addTab(
                self::TAB_VIDEOS,
                $this->pl->txt(self::TAB_VIDEOS),
                $this->ctrl->getLinkTargetByClass(xvmpSearchVideosGUI::class, xvmpGUI::CMD_STANDARD)
            );
        } else {
            if (ilObjViMPAccess::hasUploadPermission()) {
                $this->tabs_gui->addTab(
                    self::TAB_VIDEOS,
                    $this->pl->txt(self::TAB_VIDEOS),
                    $this->ctrl->getLinkTargetByClass(xvmpOwnVideosGUI::class, xvmpGUI::CMD_STANDARD)
                );
            }
        }

        if (ilLearningProgressAccess::checkAccess($this->object->getRefId()) && xvmpSettings::find($this->obj_id)->getLPActive()) {
            $this->tabs_gui->addTab(
                self::TAB_LEARNING_PROGRESS,
                $this->lng->txt(self::TAB_LEARNING_PROGRESS),
                $this->ctrl->getLinkTargetByClass(
                    xvmpLearningProgressGUI::class,
                    xvmpGUI::CMD_STANDARD
                )
            );

        }

        if (ilObjViMPAccess::hasWriteAccess()) {
            $this->tabs_gui->addTab(
                self::TAB_LOG,
                $this->pl->txt(self::TAB_LOG),
                $this->ctrl->getLinkTargetByClass(xvmpEventLogGUI::class, xvmpGUI::CMD_STANDARD)
            );
        }

        if (ilObjViMPAccess::hasWriteAccess()) {
            $this->tabs_gui->addTab(
                self::TAB_SETTINGS,
                $this->pl->txt(self::TAB_SETTINGS),
                $this->ctrl->getLinkTargetByClass(xvmpSettingsGUI::class, xvmpGUI::CMD_STANDARD)
            );
        }

        if ($this->checkPermissionBool("edit_permission")) {
            $this->tabs_gui->addTab(
                "perm_settings",
                $this->dic->language()->txt("perm_settings"),
                $this->ctrl->getLinkTargetByClass(array(
                    get_class($this),
                    "ilpermissiongui",
                ), "perm")
            );
        }
    }

    /**
     * @param $cmd
     */
    public function performCommand($cmd) : void
    {
        switch ($cmd) {
            default:
                $this->$cmd();
                break;
        }
    }

    public function initCreateForm(string $new_type) : ilPropertyFormGUI
    {
        $this->tpl->addCss($this->pl->getAssetURL('default/xvmp_settings.css'));

        $form = parent::initCreateForm($new_type);

        // ONLINE
        $input = new ilCheckboxInputGUI($this->lng->txt(xvmpSettingsFormGUI::F_ONLINE), xvmpSettingsFormGUI::F_ONLINE);
        $form->addItem($input);

        // LAYOUT
        $input = new ilRadioGroupInputGUI($this->pl->txt(xvmpSettingsFormGUI::F_LAYOUT), xvmpSettingsFormGUI::F_LAYOUT);
        $option = new ilRadioOption(
            ilUtil::img($this->pl->getImagePath(xvmpSettingsFormGUI::F_LAYOUT . '_' . xvmpSettings::LAYOUT_TYPE_LIST . '.png')),
            (string) xvmpSettings::LAYOUT_TYPE_LIST
        );
        $input->addOption($option);
        $option = new ilRadioOption(
            ilUtil::img($this->pl->getImagePath(xvmpSettingsFormGUI::F_LAYOUT . '_' . xvmpSettings::LAYOUT_TYPE_TILES . '.png')),
            (string) xvmpSettings::LAYOUT_TYPE_TILES
        );
        $input->addOption($option);
        $option = new ilRadioOption(
            ilUtil::img($this->pl->getImagePath(xvmpSettingsFormGUI::F_LAYOUT . '_' . xvmpSettings::LAYOUT_TYPE_PLAYER . '.png')),
            (string) xvmpSettings::LAYOUT_TYPE_PLAYER
        );
        $input->addOption($option);
        $input->setValue((string) xvmpSettings::LAYOUT_TYPE_LIST);
        $form->addItem($input);

        return $form;
    }

    public function afterSave(ilObject $new_object) : void
    {
        if (
            (isset($_POST[xvmpSettingsFormGUI::F_ONLINE]) && $_POST[xvmpSettingsFormGUI::F_ONLINE])
            ||
            (isset($_POST[xvmpSettingsFormGUI::F_LAYOUT]) && $_POST[xvmpSettingsFormGUI::F_LAYOUT])
        ) {
            /** @var xvmpSettings $settings */
            $settings = xvmpSettings::find($new_object->getId());
            if(isset($_POST[xvmpSettingsFormGUI::F_ONLINE])) {
                $settings->setIsOnline((int) $_POST[xvmpSettingsFormGUI::F_ONLINE]);
            } else {
                $settings->setIsOnline(0);
            }

            $settings->setLayoutType((int) $_POST[xvmpSettingsFormGUI::F_LAYOUT]) ?? 0;
            $settings->update();
        }
        parent::afterSave($new_object);
    }

    /**
     * called by the button to test connection inside the plugin config
     */
    public function testConnectionAjax() : void
    {
        $apikey = $_POST['apikey'];
        $apiurl = $_POST['apiurl'];

        $xvmpCurl = new xvmpCurl(rtrim($apiurl, '/') . '/' . ltrim(xvmpRequest::VERSION, '/'));
        $xvmpCurl->addPostField('apikey', $apikey);
        try {
            $xvmpCurl->post();
            echo "Connection OK";
            exit;
        } catch (Exception $e) {
            $message = 'No Connection, Status Code ' . $e->getCode();
            switch ($e->getCode()) {
                case 401:
                    $message .= ' - No Authorization, possibly wrong API-Key';
                    break;
                case 404:
                    $message .= ' - Not Found, possibly wrong relative URL';
                    break;
                case 500:
                    $message .= ' - Internal Server Error, possibly wrong URL';
                    break;
            }
            echo $message;
            exit;
        }
    }

    public function getType() : string
    {
        return ilViMPPlugin::XVMP;
    }

    public function getAfterCreationCmd() : string
    {
        return self::CMD_SHOW_CONTENT;
    }

    public function getStandardCmd() : string
    {
        return self::CMD_SHOW_CONTENT;
    }

    public function showContent() : void
    {
        $this->ctrl->redirectByClass(xvmpContentGUI::class, xvmpGUI::CMD_STANDARD);
    }

    public function searchVideos() : void
    {
        $this->ctrl->redirectByClass(xvmpSearchVideosGUI::class, xvmpGUI::CMD_STANDARD);
    }

    public function searchUserAjax()
    {
        $username = $_GET['username'];
        $response = xvmpRequest::extendedSearch(array(
            'searchrange' => 'user',
            'title' => $username,
        ))->getResponseBody();
        echo $response;
        exit;
    }

    public function getPicture() : void
    {
        $key = $_GET['key'];
        // TODO: implement picture wrapper, if api action is implemented
    }

    public function addUserAutoComplete()
    {
        $auto = new ilUserAutoComplete();
        $auto->setSearchFields(array('login', 'firstname', 'lastname', 'email'));
        $auto->setResultField('login');
        $auto->enableFieldSearchableCheck(false);
        $auto->setMoreLinkAvailable(true);

        if (($_REQUEST['fetchall'])) {
            $auto->setLimit(ilUserAutoComplete::MAX_ENTRIES);
        }

        $list = $auto->getList($_REQUEST['term']);

        echo $list;
        exit();
    }
}
