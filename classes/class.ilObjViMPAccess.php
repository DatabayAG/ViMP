<?php

declare(strict_types=1);

/**
 * Class ilObjViMPAccess
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ilObjViMPAccess extends ilObjectPluginAccess
{
    public const ACTION_ADD_VIDEO = 'add_video';
    public const ACTION_REMOVE_VIDEO = 'remove_video';
    public const ACTION_PLAY_VIDEO = 'play_video';
    public const ACTION_DOWNLOAD_VIDEO = 'download_video';
    /**
     * delete / edit / change owner
     */
    public const ACTION_MANIPULATE_VIDEO = 'manipulate_video'; // delete, edit, change owner

    public const CONTEXT_OBJECT = 'context_object';
    public const CONTEXT_PAGE_EDITOR = 'context_page_editor';

    /**
     * @param                 $action
     * @param xvmpGUI         $GUI
     * @param xvmpMedium|NULL $medium
     */
    public static function checkAction($action, xvmpGUI $GUI, xvmpMedium $medium = null) : void
    {
        if (ilObject2::_lookupType((int) $_GET['ref_id'], true) == 'xvmp') {
            $context = self::CONTEXT_OBJECT;
        } else {
            $context = self::CONTEXT_PAGE_EDITOR;
        }

        if (!self::isActionAllowed($action, $GUI, $context, $medium)) {
            $GUI->accessDenied();
        }

    }

    /**
     * @param                 $action
     * @param                 $GUI
     * @param                 $context
     * @param xvmpMedium|NULL $medium
     * @return bool
     */
    public static function isActionAllowed($action, $GUI, $context, xvmpMedium $medium = null) : bool
    {
        switch ($action) {
            case self::ACTION_PLAY_VIDEO:
                if ($medium->isPublic() || $medium->isCurrentUserOwner()) {
                    return true;
                }
                if ($context == self::CONTEXT_OBJECT
                    && xvmpSelectedMedia::isSelected($medium->getId(), $GUI->getObjId())
                    && self::hasReadAccess()) {
                    return true;
                }
                break;
            case self::ACTION_DOWNLOAD_VIDEO:
                if ($medium->isPublic() || $medium->isCurrentUserOwner()) {
                    return true;
                }
                if ($context == self::CONTEXT_OBJECT
                    && xvmpSelectedMedia::isSelected($medium->getId(), $GUI->getObjId())
                    && self::hasReadAccess()
                    && $medium->isDownloadAllowed()) {
                    return true;
                }
                break;
            case self::ACTION_ADD_VIDEO:
                if ($medium->isPublic() || $medium->isCurrentUserOwner() && (self::hasWriteAccess() || self::hasUploadPermission())) {
                    return true;
                }
                break;
            case self::ACTION_REMOVE_VIDEO:
                if (self::hasWriteAccess() || self::hasUploadPermission()) {
                    return true;
                }
                break;
            case self::ACTION_MANIPULATE_VIDEO:
                if ($medium->isCurrentUserOwner() && (self::hasWriteAccess() || self::hasUploadPermission())) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * @param $ref_id
     * @return bool
     */
    public static function hasReadAccess($ref_id = null) : bool
    {
        if ($ref_id === null) {
            $ref_id = $_GET['ref_id'];
        }
        global $DIC;
        $ilAccess = $DIC['ilAccess'];

        /**
         * @var $ilAccess ilAccesshandler
         */

        return $ilAccess->checkAccess('read', '', (int) $ref_id);
    }

    /**
     * @param $ref_id
     * @return bool
     */
    public static function hasWriteAccess($ref_id = null) : bool
    {
        if ($ref_id === null) {
            $ref_id = $_GET['ref_id'];
        }
        global $DIC;
        $ilAccess = $DIC['ilAccess'];

        /**
         * @var $ilAccess ilAccesshandler
         */

        return $ilAccess->checkAccess('write', '', (int) $ref_id);
    }

    /**
     * @param $ref_id
     * @return bool
     */
    public static function hasUploadPermission($ref_id = null) : bool
    {
        if ($ref_id === null) {
            $ref_id = $_GET['ref_id'];
        }
        global $DIC;
        $ilAccess = $DIC['ilAccess'];

        /**
         * @var $ilAccess ilAccesshandler
         */

        return $ilAccess->checkAccess('rep_robj_xvmp_perm_upload', '', (int) $ref_id);
    }

     /**
     * @param $ref_id
     *
     * @return bool
     */
    public static function hasAccessToStreamingLink($ref_id = NULL) {
        if ($ref_id === NULL) {
            $ref_id =  $_GET['ref_id'];
        }
        global $DIC;
        $ilAccess = $DIC['ilAccess'];
        return $ilAccess->checkAccess('rep_robj_xvmp_perm_readlink', '',(int)$ref_id);
    }
    /**
     * @param string   $cmd
     * @param string   $permission
     * @param int      $ref_id
     * @param int|null $obj_id
     * @param int      $user_id
     * @return bool
     */
    public function _checkAccess(string $cmd, string $permission, int $ref_id, int $obj_id = null, $user_id = '') : bool
    {
        global $DIC;
        $ilUser = $DIC['ilUser'];
        $ilAccess = $DIC['ilAccess'];
        /**
         * @var $ilAccess ilAccessHandler
         */
        if ($user_id == '') {
            $user_id = $ilUser->getId();
        }
        if ($obj_id === null) {
            $obj_id = ilObject2::_lookupObjId($ref_id);
        }

        switch ($permission) {
            case 'read':
                if (!self::checkOnline($obj_id) and !$ilAccess->checkAccessOfUser((int) $user_id, 'write', '',
                        $ref_id)) {
                    return false;
                }
                break;
            case 'visible':
                if (!self::checkOnline($obj_id) and !$ilAccess->checkAccessOfUser($user_id, 'write', '', $ref_id)) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * @param $obj_id
     * @return bool
     */
    public function checkOnline($obj_id) : bool
    {
        return (bool) xvmpSettings::find($obj_id)->getIsOnline();
    }

}
