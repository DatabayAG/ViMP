<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
const FILTER_SANITIZE_STRING2 = 513;
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
        global $DIC;
        $key = 'version';
        $existing = xvmpCacheFactory::getInstance()->get($key, $DIC->refinery()->to()->string());
        if ($existing) {
            xvmpCurlLog::getInstance()->write('CACHE: used cached: ' . $key, xvmpCurlLog::DEBUG_LEVEL_2);
            return $existing;
        }

        $response = xvmpRequest::version()->getResponseArray()['info']['version'];
        $vimp_version = substr($response, 0, strpos($response, ' '));

        xvmpCurlLog::getInstance()->write('CACHE: added to cache: ' . $key, xvmpCurlLog::DEBUG_LEVEL_1);
        xvmpCacheFactory::getInstance()->set($key, $vimp_version, (int) xvmpConf::getConfig(xvmpConf::F_CACHE_TTL_CONFIG));

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
        return ilObjUserTracking::_enabledLearningProgress();
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
        if (empty($ref_id)) {
            return false;
        }
        global $DIC;
        $tree = $DIC['tree'];
        /**
         * @var $tree ilTree
         */
        while ($ref_id > 1 && ilObject2::_lookupType($ref_id, true) !== 'crs') {
            $ref_id = $tree->getParentId($ref_id);
        }

        return ($ref_id > 1) ? $ref_id : false;
    }

    /**
     * @return bool
     */
    public static function isAllowedToSetPublic() : bool
    {
        global $DIC;
        $is_admin = $DIC->rbac()->review()->isAssigned($DIC->user()->getId(), 2);
        return $is_admin || (xvmpConf::getConfig(xvmpConf::F_ALLOW_PUBLIC) &&
                (ilObjViMPAccess::hasWriteAccess() || (ilObjViMPAccess::hasUploadPermission() && xvmpConf::getConfig(xvmpConf::F_ALLOW_PUBLIC_UPLOAD))));
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
        if ($ref_id && ilObject2::_exists($ref_id, true)) {
            global $DIC;
            $rbacreview = $DIC['rbacreview'];
            $crs = new ilObjCourse($ref_id);
            $member_role = $crs->getDefaultMemberRole();
            $members = $rbacreview->assignedUsers($member_role);
        }

        return $members;
    }

    public static function deliverMedium(xvmpMedium $medium) : void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        foreach (self::downloadUrlCandidates($medium) as $download_url) {
            if (self::streamDownload($download_url, $medium)) {
                exit;
            }
        }

        global $DIC;
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        echo $DIC->language()->txt('file_not_found');
        exit;
    }

    /**
     * Playable file first, original source second. Manifests are not a file download.
     * @return string[]
     */
    private static function downloadUrlCandidates(xvmpMedium $medium) : array
    {
        $urls = array();
        foreach (array($medium->getMedium(), $medium->getSource()) as $candidate) {
            if (is_array($candidate)) {
                $candidate = reset($candidate);
            }
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }
            $candidate = trim(html_entity_decode(urldecode($candidate)));
            if ($candidate === '' || preg_match('/\.(m3u8|smil)(\?|$)/i', $candidate)) {
                continue;
            }
            $urls[] = $candidate;
        }

        return array_values(array_unique($urls));
    }

    private static function streamDownload(string $download_url, xvmpMedium $medium) : bool
    {
        $response_headers = '';
        $reject = false;
        $headers_sent = false;
        $cookie_file = CLIENT_DATA_DIR . '/temp/vimp_cookie.txt';

        $ch = curl_init($download_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            static function ($curl, string $header) use (&$response_headers, &$reject) : int {
                if (str_starts_with($header, 'HTTP/')) {
                    $response_headers = '';
                    $reject = false;
                }
                $response_headers .= $header;
                if (trim($header) !== '') {
                    return strlen($header);
                }
                $status = 0;
                $content_type = '';
                if (preg_match('#HTTP/\d(?:\.\d)?\s+(\d+)#', $response_headers, $status_match)) {
                    $status = (int) $status_match[1];
                }
                if (preg_match('/Content-Type:\s*([^;\s]+)/i', $response_headers, $type_match)) {
                    $content_type = strtolower($type_match[1]);
                }
                $is_redirect = $status >= 300 && $status < 400;
                $reject = !$is_redirect && ($status >= 400 || str_starts_with($content_type, 'text/html'));

                return strlen($header);
            }
        );
        curl_setopt(
            $ch,
            CURLOPT_WRITEFUNCTION,
            static function ($curl, string $chunk) use (&$reject, &$headers_sent, &$response_headers, $medium) : int {
                if ($reject || (!$headers_sent && str_starts_with(ltrim($chunk), '<'))) {
                    return 0;
                }
                if (!$headers_sent) {
                    self::sendDownloadHeaders($response_headers, $medium);
                    $headers_sent = true;
                }
                echo $chunk;
                flush();

                return strlen($chunk);
            }
        );

        curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return $headers_sent && $http_code > 0 && $http_code < 400;
    }

    private static function sendDownloadHeaders(string $response_headers, xvmpMedium $medium) : void
    {
        $filename = $medium->getTitle() . '.mp4';
        $content_type = 'application/octet-stream';
        if (preg_match('/content-disposition:.*filename=["\']?([^"\']+)/i', $response_headers, $matches)) {
            $filename = str_replace(["\r", "\n"], '', $matches[1]);
        }
        if (preg_match('/Content-Type:\s*([^\s]+)/i', $response_headers, $matches)) {
            $content_type = $matches[1];
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        if (preg_match('/Content-Length:\s*(\d+)/i', $response_headers, $matches)) {
            header('Content-Length: ' . $matches[1]);
        }
        header('Cache-Control: post-check=0, pre-check=0, max-age=0');
        header('Pragma: public');
        header('Expires: 0');
    }

    /**
     * @return mixed
     */
    public static function getToken() : mixed
    {
        global $DIC;
        $token = xvmpCacheFactory::getInstance()->get(self::TOKEN, $DIC->refinery()->to()->string());
        if ($token) {
            xvmpCurlLog::getInstance()->write('CACHE: used cached: ' . self::TOKEN, xvmpCurlLog::DEBUG_LEVEL_2);

            return $token;
        }

        xvmpCurlLog::getInstance()->write('CACHE: cached not used: ' . self::TOKEN, xvmpCurlLog::DEBUG_LEVEL_2);

        $response = xvmpRequest::loginUser(xvmpConf::getConfig(xvmpConf::F_API_USER),
            xvmpConf::getConfig(xvmpConf::F_API_PASSWORD))
                               ->getResponseArray();
        $token = $response[self::TOKEN];
        xvmpCacheFactory::getInstance()->set(self::TOKEN, $token, (int) xvmpConf::getConfig(xvmpConf::F_CACHE_TTL_TOKEN));

        return $token;
    }
}
