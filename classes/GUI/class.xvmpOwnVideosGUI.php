<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpOwnVideosGUI
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @ilCtrl_isCalledBy xvmpOwnVideosGUI: ilObjViMPGUI
 */
class xvmpOwnVideosGUI extends xvmpVideosGUI
{
    public const SUBTAB_ACTIVE = xvmpVideosGUI::SUBTAB_OWN;

    public const TABLE_CLASS = 'xvmpOwnVideosTableGUI';

    public const CMD_EDIT_VIDEO = 'editVideo';
    public const CMD_CHANGE_OWNER = 'changeOwner';
    public const CMD_CONFIRMED_CHANGE_OWNER = 'confirmedChangeOwner';
    public const CMD_UPDATE_VIDEO = 'updateVideo';
    public const CMD_DELETE_VIDEO = 'deleteVideo';
    public const CMD_UPLOAD_VIDEO_FORM = 'uploadVideoForm';
    public const CMD_CREATE = 'create';
    public const CMD_CONFIRMED_DELETE_VIDEO = 'confirmedDeleteVideo';
    public const CMD_UPLOAD_CHUNKS = 'uploadChunks';

    /**
     * @throws ilCtrlException
     * @throws xvmpException
     */
    protected function performCommand($cmd) : void
    {
        switch ($cmd) {
            case self::CMD_EDIT_VIDEO:
            case self::CMD_CHANGE_OWNER:
            case self::CMD_UPDATE_VIDEO:
            case self::CMD_DELETE_VIDEO:
            case self::CMD_CONFIRMED_CHANGE_OWNER:
            case self::CMD_CONFIRMED_DELETE_VIDEO:
                $mid = $this->getMidFromPostOrGet();

                if($mid !== null){
                        $medium = xvmpMedium::find($mid);
                        if (!$medium instanceof xvmpDeletedMedium) {
                            ilObjViMPAccess::checkAction(ilObjViMPAccess::ACTION_MANIPULATE_VIDEO, $this, $medium);
                        }
                    }
                break;
            case self::CMD_FILL_MODAL:
                $mid = $this->getMidFromPostOrGet();
                $medium = xvmpMedium::find($mid);
                ilObjViMPAccess::checkAction(ilObjViMPAccess::ACTION_PLAY_VIDEO, $this, $medium);
                break;
            default:
                if (!ilObjViMPAccess::hasWriteAccess() && !ilObjViMPAccess::hasUploadPermission()) {
                    xvmpCurlLog::getInstance()->write('Access denied: User has no write access or upload permission. (xvmpLearningProgressGUI)');
                    $this->accessDenied();
                }
        }
        if ($cmd != self::CMD_UPLOAD_CHUNKS) {
            /**
             * this will find (and cache) or create a vimp user,
             * or throw an exception if no vimp user is found and no vimp user can be created.
             */
            xvmpUser::getOrCreateVimpUser($this->dic->user());
        }
        parent::performCommand($cmd);
    }

    /**
     *
     */
    protected function uploadChunks() : void
    {
        $filter_sanitize_string = 513;
        $xoctPlupload = new xoctPlupload();
        $tmp_id = filter_input(INPUT_GET, 'tmp_id', $filter_sanitize_string);

        $dir = ILIAS_ABSOLUTE_PATH . ltrim(ilFileUtils::getWebspaceDir(), '.') . '/vimp/' . $tmp_id;
        if (!is_dir($dir)) {
            ilFileUtils::makeDir($dir);
        }

        $xoctPlupload->setTargetDir($dir);
        $xoctPlupload->handleUpload();
    }

    /**
     * @return mixed|null
     */
    protected function getMidFromPostOrGet() : mixed
    {
        $get_mid = $_GET['mid'] ?? null;
        $post_mid = $_POST['mid'] ?? null;
        $mid = null;
        if ($get_mid !== null && $post_mid !== null) {
            $mid = max($_GET['mid'], $_POST['mid']);
        } elseif ($get_mid !== null) {
            $mid = $get_mid;
        }
        return $mid;
    }

    /**
     * @throws ilCtrlException
     */
    public function editVideo() : void
    {
        $mid = $_GET['mid'];
        $xvmpEditVideoFormGUI = new xvmpEditVideoFormGUI($this, $mid);
        $xvmpEditVideoFormGUI->fillForm();
        $this->dic->ui()->mainTemplate()->setContent($xvmpEditVideoFormGUI->getHTML());
    }

    /**
     * @throws ilCtrlException
     * @throws xvmpException
     */
    public function changeOwner() : void
    {
        $mid = filter_input(INPUT_GET, 'mid');
        $login = filter_input(INPUT_POST, 'login');
        $login_exists = ilObjUser::_loginExists((string) $login);
        if ($login && $login_exists) {
            $ilConfirmationGUI = new ilConfirmationGUI();
            $ilConfirmationGUI->setFormAction($this->dic->ctrl()->getFormAction($this));
            $ilConfirmationGUI->setHeaderText($this->pl->txt('msg_warning_change_owner'));
            $ilConfirmationGUI->addItem('mid', $mid, sprintf(
                $this->pl->txt('confirmation_new_owner'),
                xvmpMedium::find($mid)->getTitle(),
                $login
            ));
            $ilConfirmationGUI->addHiddenItem('login', $login);
            $ilConfirmationGUI->setConfirm($this->dic->language()->txt('confirm'), self::CMD_CONFIRMED_CHANGE_OWNER);
            $ilConfirmationGUI->setCancel($this->dic->language()->txt('cancel'), self::CMD_STANDARD);
            $this->dic->ui()->mainTemplate()->setContent($ilConfirmationGUI->getHTML());
        } else {
            if ($login && !$login_exists) {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure',
                    $this->pl->txt('msg_error_login_not_found'), true);
            }
            $xvmpChangeOwnerFormGUI = new xvmpChangeOwnerFormGUI($this, $mid);
            $this->dic->ui()->mainTemplate()->setContent($xvmpChangeOwnerFormGUI->getHTML());
        }
    }

    /**
     *
     */
    public function confirmedChangeOwner() : void
    {
        $mid = (int) filter_input(INPUT_POST, 'mid');
        $login = filter_input(INPUT_POST, 'login');

        $medium = xvmpMedium::getObjectAsArray($mid);
        $current_user_id = $medium['uid'];
        if ($medium['uid'] !== xvmpUser::getVimpUser($this->dic->user())['uid']) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->pl->txt('permission_denied'), true);
            $this->dic->ctrl()->redirect($this, self::CMD_STANDARD);
        }

        $xvmpUser = xvmpUser::getOrCreateVimpUser(new ilObjUser(ilObjUser::getUserIdByLogin($login)));
        $medium['uid'] = $xvmpUser->getUid();
        $mediapermissions = array();

        if (isset($medium['mediapermissions']['rid'])) {
            $mediapermissions[] = $medium['mediapermissions']['rid'];
        } elseif (isset($medium['mediapermissions'])) {
            foreach ($medium['mediapermissions'] as $rid) {
                $mediapermissions[] = $rid;
            }
        }

        $edit_fields = [
            'uid' => $xvmpUser->getUid(),
            'mediapermissions' => implode(',',
                array_filter(
                    $medium['mediapermissions'] ?? [],
                    'is_numeric')
            ),
        ];

        foreach (xvmpConf::getConfig(xvmpConf::F_FORM_FIELDS) as $form_field) {
            // workaround for vimp bug (see PLVIMP-53)
            if (
                isset($form_field[xvmpConf::F_FORM_FIELD_REQUIRED]) &&
                isset($form_field[xvmpConf::F_FORM_FIELD_TYPE]) &&
                $form_field[xvmpConf::F_FORM_FIELD_REQUIRED] == 1 &&
                $form_field[xvmpConf::F_FORM_FIELD_TYPE] == 1
            ) {
                $edit_fields[$form_field[xvmpConf::F_FORM_FIELD_ID]] = 1;
            }
        }

        $response = xvmpRequest::editMedium($mid, $edit_fields)->getResponseBody();
        if ($response) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->pl->txt('form_saved'), true);
            xvmpCacheFactory::getInstance()->delete(xvmpMedium::class . '-' . $mid);
            xvmpCacheFactory::getInstance()->delete(xvmpMedium::F_USER_MEDIA . '-' . $medium['uid']);
            xvmpCacheFactory::getInstance()->delete(xvmpMedium::F_USER_MEDIA . '-' . $current_user_id);
            xvmpMedium::cache(xvmpMedium::class . '-' . $mid, $medium);
            xvmpEventLog::logEvent(xvmpEventLog::ACTION_CHANGE_OWNER, $this->getObjId(), array(
                'owner' => $login,
                'mid' => $mid,
                'title' => $medium['title']
            ));
            /** @var xvmpUploadedMedia $xvmpUploadedMedia */
            foreach (xvmpUploadedMedia::where(['mid' => $mid,
                                               'user_id' => $this->dic->user()->getId()
            ])->get() as $xvmpUploadedMedia) {
                $new_user_id = ilObjUser::_lookupId($login);
                $xvmpUploadedMedia->setUserId($new_user_id);
                $xvmpUploadedMedia->setEmail(ilObjUser::_lookupEmail($new_user_id));
                $xvmpUploadedMedia->update();
            }
        } else {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->pl->txt('failure'));
        }

        $this->dic->ctrl()->redirect($this, self::CMD_STANDARD);
    }

    /**
     *
     */
    public function updateVideo() : void
    {
        $xvmpEditVideoFormGUI = new xvmpEditVideoFormGUI($this, $_POST['mid']);
        $xvmpEditVideoFormGUI->setValuesByPost();
        if ($xvmpEditVideoFormGUI->saveForm()) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->pl->txt('form_saved'), true);
            $this->dic->ctrl()->redirect($this, self::CMD_STANDARD);
        }
        $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->pl->txt('msg_incomplete'));
        $this->dic->ui()->mainTemplate()->setContent($xvmpEditVideoFormGUI->getHTML());
    }

    /**
     *
     */
    public function uploadVideoForm() : void
    {
        $xvmpUploadVideoFormGUI = new xvmpUploadVideoFormGUI($this);
        $xvmpUploadVideoFormGUI->fillForm();
        $this->dic->ui()->mainTemplate()->setContent($xvmpUploadVideoFormGUI->getHTML());
    }

    /**
     *
     */
    public function create() : void
    {
        $xvmpEditVideoFormGUI = new xvmpUploadVideoFormGUI($this);
        $xvmpEditVideoFormGUI->setValuesByPost();
        if ($xvmpEditVideoFormGUI->saveForm()) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->pl->txt('video_uploaded'), true);
            $this->dic->ctrl()->redirect($this, self::CMD_STANDARD);
        }

        $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->pl->txt('form_incomplete'));
        $xvmpEditVideoFormGUI->setValuesByPost();
        $this->dic->ui()->mainTemplate()->setContent($xvmpEditVideoFormGUI->getHTML());
    }

    /**
     *
     */
    public function deleteVideo() : void
    {
        $mid = $_GET['mid'];
        $video = xvmpMedium::find($mid);
        $confirmation_gui = new ilConfirmationGUI();
        $confirmation_gui->setFormAction($this->dic->ctrl()->getFormAction($this));
        $confirmation_gui->setHeaderText($this->pl->txt('confirm_delete_text'));
        $confirmation_gui->addItem('mid', $mid, $video->getTitle());
        $confirmation_gui->setConfirm($this->dic->language()->txt('delete'), self::CMD_CONFIRMED_DELETE_VIDEO);
        $confirmation_gui->setCancel($this->dic->language()->txt('cancel'), self::CMD_STANDARD);
        $this->dic->ui()->mainTemplate()->setContent($confirmation_gui->getHTML());
    }

    /**
     *
     */
    public function confirmedDeleteVideo() : void
    {
        $mid = (int) $_POST['mid'];

        // fetch the video for logging purposes
        $video = xvmpMedium::getObjectAsArray($mid);

        xvmpCacheFactory::getInstance()->delete(xvmpMedium::F_USER_MEDIA . '-' . $video['uid']);
        xvmpMedium::deleteObject($mid);

        xvmpEventLog::logEvent(xvmpEventLog::ACTION_DELETE, $this->getObjId(), $video);

        $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->pl->txt('video_deleted'), true);
        $this->dic->ctrl()->redirect($this, self::CMD_STANDARD);
    }

}
