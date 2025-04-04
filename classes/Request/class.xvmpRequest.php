<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpRequest
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpRequest
{
    public const TYPE_VIDEO = '0';

    // API ENDPOINTS
    public const VERSION = 'version';
    public const GET_USER_ROLES = 'getUserRoles';
    public const GET_CATEGORIES = 'getCategories';
    public const GET_CATEGORY = 'getCategory';
    public const GET_MEDIA = 'getMedia';
    public const GET_MEDIUM = 'getMedium';
    public const EDIT_MEDIUM = 'editMedium';
    public const DELETE_MEDIUM = 'deleteMedium';
    public const UPLOAD_MEDIUM = 'uploadMedium';
    public const LOGIN_USER = 'loginUser';
    public const GET_USERS = 'getUsers';
    public const GET_USER = 'getUser';
    public const REGISTER_USER = 'registerUser';
    public const GET_USER_MEDIA = 'getUserMedia';
    public const EXTENDED_SEARCH = 'extendedSearch';
    public const GET_PICTURE = 'getPicture';
    public const GET_VIDEOSOURCES = '../media/ajax';
    public const GET_CHAPTERS = '../webplayer/getchapters/key/';
    public const GET_MEDIUM_TRANSCODING_PROGRESS = 'getMediumTranscodingProgress';
    public const ADD_SUBTITLE = 'addSubtitle';
    public const REMOVE_SUBTITLES = 'removeSubtitles';
    public const REMOVE_SUBTITLE = 'removeSubtitle';
    public const CONFIG = 'config';
    public const ADD_MEDIUM_COUNT = 'addMediumCount';

    /**
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function version() : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::VERSION);
        $xvmpCurl->setTimeoutMS(20000);
        $xvmpCurl->post();
        return $xvmpCurl;
    }

    /**
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function getUserRoles() : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::GET_USER_ROLES);
        $xvmpCurl->post();
        return $xvmpCurl;
    }

    /**
     * @param array $params
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function getCategories(array $params = array()) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::GET_CATEGORIES);
        $xvmpCurl->post();
        return $xvmpCurl;
    }

    /**
     * @param int   $categoryid
     * @param array $params
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function getCategory(int $categoryid, array $params = array()) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::GET_CATEGORY);
        $xvmpCurl->addPostField('categoryid', $categoryid);
        $xvmpCurl->post();
        return $xvmpCurl;
    }

    /**
     * possible parameters:
     * $filterbyname
     * $filterbytype
     * $filterbycategory
     * $filterbyfilter
     * $offset
     * $limit
     * $thumbsize
     * $hidden
     * $chapters
     * $responsive
     * $language
     * @param array $params
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function getMedia(array $params = array()) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::GET_MEDIA);
        $params['filterbytype'] = self::TYPE_VIDEO;
        foreach ($params as $name => $value) {
            $xvmpCurl->addPostField($name, $value);
        }
        $xvmpCurl->post();
        return $xvmpCurl;
    }

    /**
     * possible parameters:
     * $thumbsize
     * $chapters
     * $responsive
     * $language
     * @param int   $mediumid
     * @param array $params
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function getMedium(int $mediumid, array $params = array()) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::GET_MEDIUM);
        $params['mediumid'] = $mediumid;
        $params['token'] = xvmp::getToken();
        //		$params['reponsive'] = 'true';
        foreach ($params as $name => $value) {
            $xvmpCurl->addPostField($name, $value);
        }
        $xvmpCurl->post();
        return $xvmpCurl;
    }

    /**
     * @param int $mediumid
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function addMediumCount(int $mediumid) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::ADD_MEDIUM_COUNT);
        $xvmpCurl->addPostField('mediumid', $mediumid);
        $xvmpCurl->addPostField('token', xvmp::getToken());
        $xvmpCurl->post();
        return $xvmpCurl;
    }

    /**
     * possible parameters:
     * $title
     * $description
     * $tags
     * $categories
     * $mediapermissions
     * @param int   $mediumid
     * @param array $params
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function editMedium(int $mediumid, array $params) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::EDIT_MEDIUM);
        $params['mediumid'] = $mediumid;
        $params['token'] = xvmp::getToken();
        foreach ($params as $name => $value) {
            $xvmpCurl->addPostField($name, $value);
        }
        $xvmpCurl->post();
        return $xvmpCurl;
    }

    /**
     * possible parameters:
     * $title
     * $description
     * $tags
     * $categories
     * $mediapermissions
     * @param array $params
     * @return xvmpCurl
     * @throws xvmpException
     * @internal param $mediumid
     */
    public static function uploadMedium(array $params) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::UPLOAD_MEDIUM);
        $params['token'] = xvmp::getToken();
        foreach ($params as $name => $value) {
            $xvmpCurl->addPostField($name, $value);
        }

        $xvmpCurl->post();

        return $xvmpCurl;
    }

    /**
     * @param int $mediumid
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function deleteMedium(int $mediumid) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::DELETE_MEDIUM);
        $xvmpCurl->addPostField('token', xvmp::getToken());
        $xvmpCurl->addPostField('mediumid', $mediumid);
        $xvmpCurl->post();
        return $xvmpCurl;
    }

    /**
     * @param String $username
     * @param String $password
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function loginUser(string $username, string $password) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::LOGIN_USER);
        $xvmpCurl->addPostField('username', $username);
        $xvmpCurl->addPostField('password', $password);
        $xvmpCurl->post();
        return $xvmpCurl;
    }

    /**
     * possible parameters:
     * filterbyname
     * offset
     * limit
     * thumbsize
     *      width x height (ex. 210x108)
     * format
     *      one of "xml" or "json"
     * @param array $params
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function getUsers(array $params = array()) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::GET_USERS);
        foreach ($params as $name => $value) {
            $xvmpCurl->addPostField($name, $value);
        }
        $xvmpCurl->post();

        return $xvmpCurl;
    }

    /**
     * @param int   $userid
     * @param array $params
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function getUser(int $userid, array $params = array()) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::GET_USER);

        $params['userid'] = $userid;
        foreach ($params as $name => $value) {
            $xvmpCurl->addPostField($name, $value);
        }
        $xvmpCurl->post();

        return $xvmpCurl;
    }

    /**
     * @param array $params
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function registerUser(array $params) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::REGISTER_USER);
        foreach ($params as $name => $value) {
            $xvmpCurl->addPostField($name, $value);
        }
        $xvmpCurl->post();

        return $xvmpCurl;
    }

    /**
     * @param int   $user_id
     * @param array $params
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function getUserMedia(int $user_id, array $params = array()) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::GET_USER_MEDIA);
        $xvmpCurl->addPostField('userid', $user_id);
        $xvmpCurl->addPostField('filterbytype', self::TYPE_VIDEO);
        foreach ($params as $name => $value) {
            $xvmpCurl->addPostField($name, $value);
        }
        $xvmpCurl->post();

        return $xvmpCurl;
    }

    /**
     * @param array $params
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function extendedSearch(array $params) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::EXTENDED_SEARCH);

        if (!isset($params['userid'])) {
            $xvmpCurl->addPostField('token', xvmp::getToken()); // with a token, the field userid is ignored by ViMP
        } else {
            // vimp sets a session id when logging in the api user - this session expires though, that's why we need to reload the token/session here
            xvmpCacheFactory::getInstance()->delete(xvmp::TOKEN);
            xvmp::getToken();
        }
        $xvmpCurl->addPostField('hidden', 'true');
        foreach ($params as $name => $value) {
            $xvmpCurl->addPostField($name, $value);
        }
        $xvmpCurl->post();

        return $xvmpCurl;
    }

    /**
     * @param $key
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function getPicture($key) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::GET_PICTURE);

        $xvmpCurl->addPostField('token', xvmp::getToken());
        $xvmpCurl->addPostField('key', $key);

        $xvmpCurl->post();

        return $xvmpCurl;
    }

    // Non-API request

    /**
     * @param        $key
     * @param String $url
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function getVideoSources($key, string $url) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::GET_VIDEOSOURCES);

        $xvmpCurl->addPostField('mediakey', $key);
        if (xvmp::ViMPVersionEquals('4.0.4')) {
            $xvmpCurl->addPostField('action', 'fetchMediaSources');
            $xvmpCurl->addPostField('sign', 'true');
            $xvmpCurl->addPostField('format', '');
        } else {
            $xvmpCurl->addPostField('action', 'embedMedia');
            $xvmpCurl->addPostField('url', $url);
        }

        $xvmpCurl->post();
        return $xvmpCurl;
    }

    // Non-API request

    /**
     * @param $key
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function getChapters($key) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::GET_CHAPTERS . $key . '?token=' . xvmp::getToken());
        $xvmpCurl->get();
        return $xvmpCurl;
    }

    // Non-API request

    /**
     * @param $url
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function get($url) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl($url);
        $xvmpCurl->get();
        return $xvmpCurl;
    }

    /**
     * @param String $name
     * @return xvmpCurl
     * @throws xvmpException
     */
    public static function config(string $name) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::CONFIG);

        $xvmpCurl->addPostField('name', $name);

        if (xvmp::ViMPVersionGreaterEquals("4.2.9")) {
            $xvmpCurl->addPostField('token', xvmp::getToken());
        }

        $xvmpCurl->post();
        return $xvmpCurl;
    }

    /**
     * @throws xvmpException
     */
    public static function addSubtitle(int $mediumid, array $params) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::ADD_SUBTITLE);
        $params['token'] = xvmp::getToken();
        $params['mediumid'] = $mediumid;
        foreach ($params as $name => $value) {
            $xvmpCurl->addPostField($name, $value);
        }

        $xvmpCurl->post();

        return $xvmpCurl;
    }

    /**
     * @throws xvmpException
     */
    public static function removeSubtitles(int $mediumid) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::REMOVE_SUBTITLES);
        $xvmpCurl->addPostField('token', xvmp::getToken());
        $xvmpCurl->addPostField('mediumid', $mediumid);

        $xvmpCurl->post();

        return $xvmpCurl;
    }

    /**
     * @throws xvmpException
     */
    public static function removeSubtitle(int $mediumid, string $lang_code, string $lang_file) : xvmpCurl
    {
        $xvmpCurl = new xvmpCurl(self::REMOVE_SUBTITLE);
        $params['token'] = xvmp::getToken();
        $params['mediumid'] = $mediumid;
        $params['subtitlelanguage'] = $lang_code;
        $params['subtitlefile'] = $lang_file;
        foreach ($params as $name => $value) {
            $xvmpCurl->addPostField($name, $value);
        }

        $xvmpCurl->post();

        return $xvmpCurl;
    }

    /**
     * @param int $mid
     * @param int $decimals
     * @return float
     * @throws xvmpException
     */
    public static function getTranscodingProgress(int $mid, int $decimals = 0) : float
    {
        $xvmpCurl = new xvmpCurl(self::GET_MEDIUM_TRANSCODING_PROGRESS . '?token=' . xvmp::getToken());
        $xvmpCurl->addPostField('mediumid', $mid);
        $xvmpCurl->post();
        $response = $xvmpCurl->getResponseArray();
        if (!isset($response['progress']) || !preg_match('/^\d+(\.\d+)?%$/', $response['progress'])) {
            throw new xvmpException(0, 'could not read progress of medium with id ' . $mid .
                ' - ' . (isset($response['progress']) ? "wrong format: {$response['progress']}" : "no response from server"));
        }
        return round((float) $response['progress'], $decimals);
    }

}
