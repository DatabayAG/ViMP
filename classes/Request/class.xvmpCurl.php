<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xvmpCurl
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xvmpCurl
{
    public const FORMAT_JSON = 'json';
    public const REQ_TYPE_GET = 'GET';
    public const REQ_TYPE_POST = 'POST';
    public const REQ_TYPE_DELETE = 'DELETE';
    public const REQ_TYPE_PUT = 'PUT';
    protected static int $ssl_version = CURL_SSLVERSION_DEFAULT;
    protected static bool $ip_v4 = false;
    protected static ?string $api_key = '';
    protected static string $username = '';
    protected static string $password = '';
    protected static bool $verify_peer = true;
    protected static bool $verify_host = true;
    protected array $post_fields = array();
    protected string $url = '';
    protected string $request_type = self::REQ_TYPE_GET;
    protected array $headers = array();
    protected string $response_body = '';
    protected string $response_mime_type = '';
    protected string $response_content_size = '';
    protected int $response_status = 200;
    protected ?xvmpCurlError $response_error = null;
    protected string $put_file_path = '';
    protected string $post_body = '';
    protected string $request_content_type = '';
    protected array $files = array();
    protected int $timeout_MS = 0;

    /**
     * xvmpCurl constructor.
     * @param string $url
     */
    public function __construct(string $url = '')
    {
        global $DIC;
        $lng = $DIC['lng'];
        self::$api_key = xvmpConf::getConfig(xvmpConf::F_API_KEY);
        $this->url = str_contains($url, 'http') ? $url : xvmpConf::getConfig(xvmpConf::F_API_URL) . '/' . $url;
        $this->addPostField('apikey', xvmpConf::getConfig(xvmpConf::F_API_KEY));
        $this->addPostField('format', self::FORMAT_JSON);
        $this->addPostField('language', $lng->getLangKey());
    }

    /**
     * @param $key
     * @param $value
     */
    public function addPostField($key, $value) : void
    {
        $this->post_fields[$key] = $value;
    }

    /**
     * init password and username from config
     */
    public static function init() : void
    {
        self::$api_key = xvmpConf::getConfig(xvmpConf::F_API_KEY);
    }

    /**
     * @return int
     */
    public static function getSslVersion() : int
    {
        return self::$ssl_version;
    }

    /**
     * @param int $ssl_version
     */
    public static function setSslVersion(int $ssl_version) : void
    {
        self::$ssl_version = $ssl_version;
    }

    /**
     * @return bool
     */
    public static function isIpV4() : bool
    {
        return self::$ip_v4;
    }

    /**
     * @param bool $ip_v4
     */
    public static function setIpV4(bool $ip_v4) : void
    {
        self::$ip_v4 = $ip_v4;
    }

    /**
     * @throws xvmpException
     */
    public function get() : void
    {
        $this->setRequestType(self::REQ_TYPE_GET);
        $this->execute();
    }

    /**
     * @throws xvmpException
     */
    protected function execute() : void
    {
        static $ch;
        if (!isset($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            if (self::$ip_v4) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }

            if (self::$ssl_version) {
                curl_setopt($ch, CURLOPT_SSLVERSION, self::$ssl_version);
            }
            if (self::getUsername() and self::getPassword()) {
                curl_setopt($ch, CURLOPT_USERPWD, self::getUsername() . ':' . self::getPassword());
            }

            if (!self::isVerifyHost()) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            if (!self::isVerifyPeer()) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            }
        }

        if ($this->getTimeoutMS()) {
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->getTimeoutMS());
        }

        curl_setopt($ch, CURLOPT_URL, $this->getUrl());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->getRequestType());

        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, CLIENT_DATA_DIR . "/temp/vimp_cookie.txt");
        curl_setopt($ch, CURLOPT_COOKIEFILE, CLIENT_DATA_DIR . "/temp/vimp_cookie.txt");

        if(isset($_SERVER['REMOTE_ADDR'])) {
            $this->addHeader('X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR']);
        }

        $this->prepare($ch);

        if ($this->getRequestContentType()) {
            $this->addHeader('Content-Type: ' . $this->getRequestContentType());
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        $this->debug($ch);
        $resp_orig = curl_exec($ch);
        if ($resp_orig === false) {
            $this->setResponseError(new xvmpCurlError($ch));
        }
        $this->setResponseBody((string) $resp_orig);
        $this->setResponseMimeType((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
        $this->setResponseContentSize((string) curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD));
        $this->setResponseStatus((int) curl_getinfo($ch, CURLINFO_HTTP_CODE));

        $i = 1000;

        xvmpCurlLog::getInstance()->write('CURLINFO_CONNECT_TIME: ' . round(curl_getinfo($ch,
                    CURLINFO_CONNECT_TIME) * $i, 2) . ' ms', xvmpCurlLog::DEBUG_LEVEL_1);
        xvmpCurlLog::getInstance()->write('CURLINFO_NAMELOOKUP_TIME: ' . round(curl_getinfo($ch,
                    CURLINFO_NAMELOOKUP_TIME) * $i, 2) . ' ms', xvmpCurlLog::DEBUG_LEVEL_1);
        xvmpCurlLog::getInstance()->write('CURLINFO_REDIRECT_TIME: ' . round(curl_getinfo($ch,
                    CURLINFO_REDIRECT_TIME) * $i, 2) . ' ms', xvmpCurlLog::DEBUG_LEVEL_1);
        xvmpCurlLog::getInstance()->write('CURLINFO_STARTTRANSFER_TIME: ' . round(curl_getinfo($ch,
                    CURLINFO_STARTTRANSFER_TIME) * $i, 2) . ' ms', xvmpCurlLog::DEBUG_LEVEL_1);
        xvmpCurlLog::getInstance()->write('CURLINFO_PRETRANSFER_TIME: ' . round(curl_getinfo($ch,
                    CURLINFO_PRETRANSFER_TIME) * $i, 2) . ' ms', xvmpCurlLog::DEBUG_LEVEL_1);
        xvmpCurlLog::getInstance()->write('CURLINFO_TOTAL_TIME: ' . round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * $i,
                2) . ' ms', xvmpCurlLog::DEBUG_LEVEL_1);

        if ($this->getResponseStatus() > 299 || (isset($this->getResponseArray()['errors']) && is_array($this->getResponseArray()['errors']))) {
            xvmpCurlLog::getInstance()->write('ERROR ' . $this->getResponseStatus(), xvmpCurlLog::DEBUG_LEVEL_1);
            xvmpCurlLog::getInstance()->write('Response:' . $resp_orig, xvmpCurlLog::DEBUG_LEVEL_3);

            $response = $this->getResponseArray();
            $error_msg = '';
            if(isset($response['errors']['error'])) {
                $error_msg = $response['errors']['error'];
            }

            $error_msg = is_array($error_msg) ? implode(".\n", $error_msg) : $error_msg;

            if ($error_msg === "Medium doesn't exist") {
                throw new xvmpException(xvmpException::API_CALL_STATUS_404, $error_msg);
            }

            switch ($this->getResponseStatus()) {
                case 403:
                    throw new xvmpException(xvmpException::API_CALL_STATUS_403, $error_msg);
                case 401:
                    throw new xvmpException(xvmpException::API_CALL_BAD_CREDENTIALS);
                case 404:
                    throw new xvmpException(xvmpException::API_CALL_STATUS_404, $error_msg);
                default:
                    throw new xvmpException(xvmpException::API_CALL_STATUS_500, $error_msg);
            }
        }

        if (($this->getResponseStatus() == 0) && $this->getResponseError()->getErrorNr()) {
            $error = $this->getResponseError();
            throw new xvmpException(xvmpException::API_CALL_STATUS_500, $error->getMessage());
        }
        //		curl_close($ch);
    }

    /**
     * @return string
     */
    public static function getUsername() : string
    {
        return self::$username;
    }

    /**
     * @param string $username
     */
    public static function setUsername(string $username) : void
    {
        self::$username = $username;
    }

    /**
     * @return string
     */
    public static function getPassword() : string
    {
        return self::$password;
    }

    /**
     * @param string $password
     */
    public static function setPassword(string $password) : void
    {
        self::$password = $password;
    }

    /**
     * @return bool
     */
    public static function isVerifyHost() : bool
    {
        return self::$verify_host;
    }

    /**
     * @param bool $verify_host
     */
    public static function setVerifyHost(bool $verify_host) : void
    {
        self::$verify_host = $verify_host;
    }

    /**
     * @return bool
     */
    public static function isVerifyPeer() : bool
    {
        return !xvmpConf::getConfig(xvmpConf::F_DISABLE_VERIFY_PEER);
    }

    /**
     * @return int
     */
    public function getTimeoutMS() : int
    {
        return $this->timeout_MS;
    }

    /**
     * @param int $timeout_MS
     */
    public function setTimeoutMS(int $timeout_MS)
    {
        $this->timeout_MS = $timeout_MS;
    }

    /**
     * @return string
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url) : void
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getRequestType() : string
    {
        return $this->request_type;
    }

    /**
     * @param string $request_type
     */
    public function setRequestType(string $request_type) : void
    {
        $this->request_type = $request_type;
    }

    /**
     * @param $string
     */
    public function addHeader($string) : void
    {
        $this->headers[] = $string;
    }

    /**
     * @param $ch
     * @return void
     */
    protected function prepare($ch) : void
    {
        switch ($this->getRequestType()) {
            case self::REQ_TYPE_PUT:
                $this->preparePut($ch);
                break;
            case self::REQ_TYPE_POST:
                $this->preparePost($ch);
                break;
        }
    }

    /**
     * @param $ch
     */
    protected function preparePut($ch) : void
    {
        if ($this->getPostFields()) {
            $this->preparePost($ch);
        }
    }

    /**
     * @return array
     */
    public function getPostFields() : array
    {
        return $this->post_fields;
    }

    /**
     * @param array $post_fields
     */
    public function setPostFields(array $post_fields) : void
    {
        $this->post_fields = $post_fields;
    }

    /**
     * @param $ch
     */
    protected function preparePost($ch) : void
    {
        curl_getinfo($ch, CURLINFO_HEADER_OUT);
        if (count($this->getFiles()) > 0) {
            curl_getinfo($ch, CURLOPT_SAFE_UPLOAD);
            foreach ($this->getFiles() as $file) {
                $this->addPostField($file->getPostVar(), $file->getCURLFile());
            }
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostFields());

        xvmpCurlLog::getInstance()->write('POST-Body', xvmpCurlLog::DEBUG_LEVEL_3);
        xvmpCurlLog::getInstance()->write(print_r($this->getPostFields(), true), xvmpCurlLog::DEBUG_LEVEL_3);
    }

    /**
     * @return xvmpUploadFile[]
     */
    public function getFiles() : array
    {
        return $this->files;
    }

    /**
     * @param xvmpUploadFile[] $files
     */
    public function setFiles(array $files) : void
    {
        $this->files = $files;
    }

    /**
     * @return string
     */
    public function getRequestContentType() : string
    {
        return $this->request_content_type;
    }

    /**
     * @param string $request_content_type
     */
    public function setRequestContentType(string $request_content_type) : void
    {
        $this->request_content_type = $request_content_type;
    }

    /**
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers) : void
    {
        $this->headers = $headers;
    }

    /**
     * @param $ch
     */
    protected function debug($ch) : void
    {
        $xvmpCurlLog = xvmpCurlLog::getInstance();
        $xvmpCurlLog->write('execute *************************************************', xvmpCurlLog::DEBUG_LEVEL_1);
        $xvmpCurlLog->write($this->getUrl(), xvmpCurlLog::DEBUG_LEVEL_1);
        $xvmpCurlLog->write($this->getRequestType(), xvmpCurlLog::DEBUG_LEVEL_1);
        if ($this->getRequestType() == self::REQ_TYPE_POST) {
            $xvmpCurlLog->write(print_r($this->post_fields, true), xvmpCurlLog::DEBUG_LEVEL_1);
        }
        $backtrace = "Backtrace: \n";
        foreach (debug_backtrace() as $b) {
            if(isset($b['file']) && isset($b['function'])) {
                $backtrace .= $b['file'] . ': ' . $b["function"] . "\n";
            }
        }
        $xvmpCurlLog->write($backtrace, xvmpCurlLog::DEBUG_LEVEL_4);
        if (xvmpCurlLog::getLogLevel() >= xvmpCurlLog::DEBUG_LEVEL_3) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, fopen(xvmpCurlLog::getFullPath(), 'a'));
        }
    }

    /**
     * @return int
     */
    public function getResponseStatus() : int
    {
        return $this->response_status;
    }

    /**
     * @param int $response_status
     */
    public function setResponseStatus(int $response_status) : void
    {
        $this->response_status = $response_status;
    }

    public function getResponseArray()
    {
        return json_decode($this->response_body, true);
    }

    public function getResponseError() : ?xvmpCurlError
    {
        return $this->response_error;
    }

    /**
     * @param xvmpCurlError $response_error
     */
    public function setResponseError(xvmpCurlError $response_error) : void
    {
        $this->response_error = $response_error;
    }

    /**
     * @throws xvmpException
     */
    public function put() : void
    {
        $this->setRequestType(self::REQ_TYPE_PUT);
        $this->execute();
    }

    /**
     * @throws xvmpException
     */
    public function post() : void
    {
        $this->setRequestType(self::REQ_TYPE_POST);
        $this->execute();
    }

    /**
     * @throws xvmpException
     */
    public function delete() : void
    {
        $this->setRequestType(self::REQ_TYPE_DELETE);
        $this->execute();
    }

    /**
     * @return string
     */
    public function getResponseBody() : string
    {
        return $this->response_body;
    }

    /**
     * @param string $response_body
     */
    public function setResponseBody(string $response_body) : void
    {
        $this->response_body = $response_body;
    }

    /**
     * @return string
     */
    public function getResponseMimeType() : string
    {
        return $this->response_mime_type;
    }

    /**
     * @param string $response_mime_type
     */
    public function setResponseMimeType(string $response_mime_type) : void
    {
        $this->response_mime_type = $response_mime_type;
    }

    /**
     * @return string
     */
    public function getResponseContentSize() : string
    {
        return $this->response_content_size;
    }

    /**
     * @param string $response_content_size
     */
    public function setResponseContentSize(string $response_content_size) : void
    {
        $this->response_content_size = $response_content_size;
    }

    /**
     * @return string
     */
    public function getPutFilePath() : string
    {
        return $this->put_file_path;
    }

    /**
     * @param string $put_file_path
     */
    public function setPutFilePath(string $put_file_path) : void
    {
        $this->put_file_path = $put_file_path;
    }

    /**
     * @return string
     */
    public function getPostBody() : string
    {
        return $this->post_body;
    }

    /**
     * @param string $post_body
     */
    public function setPostBody(string $post_body) : void
    {
        $this->post_body = $post_body;
    }

}
