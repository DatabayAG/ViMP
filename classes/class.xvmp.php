<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
define('FILTER_SANITIZE_STRING2', 513);
/**
 * Class xvmp
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmp
{
    public const TOKEN = 'token';


    /**
     * @param $version
     * @return int|bool
     */
    public static function ViMPVersionEquals($version) : int|bool
    {
        $vimp_version = self::getViMPVersion();

        return version_compare($vimp_version, $version, '=');
    }

    /**
     * @return bool|mixed|string|null
     */
    public static function getViMPVersion() : mixed
    {
        $key = 'version';
        $existing = xvmpCacheFactory::getInstance()->get($key);
        if ($existing) {
            xvmpCurlLog::getInstance()->write('CACHE: used cached: ' . $key, xvmpCurlLog::DEBUG_LEVEL_2);
            return $existing;
        }

        $response = xvmpRequest::version()->getResponseArray()['info']['version'];
        $vimp_version = substr($response, 0, strpos($response, ' '));

        xvmpCurlLog::getInstance()->write('CACHE: added to cache: ' . $key, xvmpCurlLog::DEBUG_LEVEL_1);
        xvmpCacheFactory::getInstance()->set($key, $vimp_version, 0);

        return $vimp_version;
    }

    /**
     * @param $version
     * @return int|bool
     */
    public static function ViMPVersionGreaterEquals($version) : int|bool
    {
        $vimp_version = self::getViMPVersion();

        return version_compare($vimp_version, $version, '>=');
    }

    /**
     * @param $obj_id
     * @return bool
     */
    public static function isLearningProgressPossible($obj_id) : bool
    {
        $ref_id = self::lookupRefId($obj_id);

        return (ilObjUserTracking::_enabledLearningProgress() && self::getParentCourseRefId($ref_id));
    }

    /**
     * @param $obj_id
     * @return string|int|null
     */
    public static function lookupRefId($obj_id) : string|int|null
    {
        $refs = array_keys(ilObject2::_getAllReferences((int) $obj_id));
        return array_shift($refs);
    }

    /**
     * @param $ref_id
     * @return bool|int
     */
    public static function getParentCourseRefId($ref_id) : bool|int
    {
        global $DIC;
        $tree = $DIC['tree'];
        /**
         * @var $tree ilTree
         */
        while (ilObject2::_lookupType($ref_id, true) != 'crs') {
            if ($ref_id == 1) {
                return false;
            }
            $ref_id = $tree->getParentId($ref_id);
        }

        return $ref_id;
    }

    /**
     * @return bool
     */
    public static function isAllowedToSetPublic() : bool
    {
        global $DIC;
        $is_admin = $DIC->rbac()->review()->isAssigned($DIC->user()->getId(), 2);
        return $is_admin || xvmpConf::getConfig(xvmpConf::F_ALLOW_PUBLIC) &&
            (ilObjViMPAccess::hasWriteAccess() || (ilObjViMPAccess::hasUploadPermission() && xvmpConf::getConfig(xvmpConf::F_ALLOW_PUBLIC_UPLOAD)));
    }

    /**
     * @param $obj_id
     * @param $video
     * @return bool
     * @throws xvmpException
     */
    public static function showWatched($obj_id, $video) : bool
    {
        return !self::isUseEmbeddedPlayer($obj_id, $video);
    }

    /**
     * @param $obj_id
     * @param $video xvmpMedium|array
     * @return bool
     * @throws xvmpException
     */
    public static function isUseEmbeddedPlayer($obj_id, xvmpMedium|array $video) : bool
    {
        return (!xvmpSettings::find($obj_id)->getLpActive() && xvmpConf::getConfig(xvmpConf::F_EMBED_PLAYER))
            || xvmpMedium::isVimeoOrYoutube($video);
    }

    /**
     * @param      $id
     * @param bool $is_ref_id
     * @return array
     */
    public static function getCourseMembers($id, bool $is_ref_id = true) : array
    {
        $members = array();
        $ref_id = self::getParentCourseRefId($is_ref_id ? $id : self::lookupRefId($id));
        if ($ref_id) {
            global $DIC;
            $rbacreview = $DIC['rbacreview'];
            $crs = new ilObjCourse($ref_id);
            $member_role = $crs->getDefaultMemberRole();
            $members = $rbacreview->assignedUsers($member_role);
        }

        return $members;
    }

    public static function deliverMedium(xvmpMedium $medium)
    {
        $medium_url = $medium->getMedium();
        if (is_array($medium_url)) {
            $medium_url = $medium_url[0];
        }
        $download_url = $medium_url . '?token=' . xvmp::getToken();
        $extension = pathinfo($medium_url)['extension'];
        // get filesize
        $ch = curl_init($download_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);

        // deliver file
        header('Content-Description: File Transfer');
        header('Content-Type: video/' . $extension);
        header('Content-Disposition: attachment; filename="' . $medium->getTitle() . '.' . $extension);
        header('Content-Length: ' . $size);
        readfile($download_url);
        exit;
    }

    /**
     * @return mixed
     */
    public static function getToken() : mixed
    {
        $token = xvmpCacheFactory::getInstance()->get(self::TOKEN);
        if ($token) {
            xvmpCurlLog::getInstance()->write('CACHE: used cached: ' . self::TOKEN, xvmpCurlLog::DEBUG_LEVEL_2);

            return $token;
        }

        xvmpCurlLog::getInstance()->write('CACHE: cached not used: ' . self::TOKEN, xvmpCurlLog::DEBUG_LEVEL_2);

        $response = xvmpRequest::loginUser(xvmpConf::getConfig(xvmpConf::F_API_USER),
            xvmpConf::getConfig(xvmpConf::F_API_PASSWORD))
                               ->getResponseArray();
        $token = $response[self::TOKEN];
        xvmpCacheFactory::getInstance()->set(self::TOKEN, $token,
            (int) xvmpConf::getConfig(xvmpConf::F_CACHE_TTL_TOKEN));

        return $token;
    }
}
